<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$role = isset($_SESSION['role']) ? strtolower(trim((string)$_SESSION['role'])) : '';
if ($role !== 'athletic_trainer') {
    header("Location: index.php");
    exit();
}

$activeTab = $_GET['tab'] ?? 'profile';
$allowedTabs = ['profile', 'athletes', 'medical', 'communication', 'schedule', 'performance'];
if (!in_array($activeTab, $allowedTabs, true)) {
    $activeTab = 'profile';
}

require 'db.php';

$atUserId = (int)$_SESSION['user_id'];

// ── Profile ──────────────────────────────────────────────────────────────────
$atProfileRow = null;
$atProfileStmt = $conn->prepare(
    "SELECT u.name, u.email, at2.specialty, at2.certification
     FROM users u
     LEFT JOIN athletic_trainers at2 ON at2.user_id = u.user_id
     WHERE u.user_id = ?
     LIMIT 1"
);
if ($atProfileStmt) {
    $atProfileStmt->bind_param("i", $atUserId);
    $atProfileStmt->execute();
    $atProfileRow = $atProfileStmt->get_result()->fetch_assoc();
}

// ── All athletes (Athletes tab) ───────────────────────────────────────────────
$allAthletes = [];
$allAthletesStmt = $conn->prepare(
    "SELECT a.athlete_id, a.full_name, a.age, a.sport, a.position, a.jersey_number
     FROM athletes a
     ORDER BY a.full_name ASC"
);
if ($allAthletesStmt) {
    $allAthletesStmt->execute();
    $allAthletesResult = $allAthletesStmt->get_result();
    while ($row = $allAthletesResult->fetch_assoc()) {
        $allAthletes[] = $row;
    }
}

// ── Medical records (Medical tab) ────────────────────────────────────────────
$medicalRecords = [];
$medicalStmt = $conn->prepare(
    "SELECT m.medical_record_id, m.injury_title, m.injury_details, m.reported_date,
            m.status, m.clearance_status, m.expected_return_date, m.cleared_date,
            m.notes, a.full_name AS athlete_name, a.athlete_id
     FROM medical_records m
     INNER JOIN athletes a ON m.athlete_id = a.athlete_id
     ORDER BY m.reported_date DESC"
);
if ($medicalStmt) {
    $medicalStmt->execute();
    $medicalResult = $medicalStmt->get_result();
    while ($row = $medicalResult->fetch_assoc()) {
        $medicalRecords[] = $row;
    }
}

// ── Communication ─────────────────────────────────────────────────────────────
$atAthletes = $allAthletes; // reuse
$selectedAthleteId   = isset($_GET['athlete_id']) ? (int)$_GET['athlete_id'] : 0;
$selectedAthleteUserId = null;
$atDirectMessages    = [];

if ($selectedAthleteId > 0) {
    $selUserStmt = $conn->prepare("SELECT user_id FROM athletes WHERE athlete_id = ? LIMIT 1");
    if ($selUserStmt) {
        $selUserStmt->bind_param("i", $selectedAthleteId);
        $selUserStmt->execute();
        $selUserRow = $selUserStmt->get_result()->fetch_assoc();
        if ($selUserRow && !empty($selUserRow['user_id'])) {
            $selectedAthleteUserId = (int)$selUserRow['user_id'];

            $dmStmt = $conn->prepare(
                "SELECT u.name AS sender_name, m.content, m.sent_at
                 FROM messages m
                 INNER JOIN users u ON m.sender_user_id = u.user_id
                 WHERE m.message_type = 'direct'
                   AND m.athlete_id = ?
                   AND (
                       (m.sender_user_id = ? AND m.recipient_user_id = ?)
                       OR
                       (m.sender_user_id = ? AND m.recipient_user_id = ?)
                   )
                 ORDER BY m.sent_at ASC"
            );
            if ($dmStmt) {
                $dmStmt->bind_param(
                    "iiiii",
                    $selectedAthleteId,
                    $atUserId, $selectedAthleteUserId,
                    $selectedAthleteUserId, $atUserId
                );
                $dmStmt->execute();
                $dmResult = $dmStmt->get_result();
                while ($row = $dmResult->fetch_assoc()) {
                    $atDirectMessages[] = $row;
                }
            }
        }
    }
}

// ── Performance records (Performance tab) ────────────────────────────────────
$performanceByAthlete = [];
$performanceStmt = $conn->prepare(
    "SELECT p.category, p.metric_name, p.metric_value, p.record_date, p.notes,
            a.full_name AS athlete_name, a.athlete_id
     FROM performance_records p
     INNER JOIN athletes a ON p.athlete_id = a.athlete_id
     ORDER BY a.full_name ASC, p.category ASC, p.record_date DESC, p.metric_name ASC"
);
if ($performanceStmt) {
    $performanceStmt->execute();
    $performanceResult = $performanceStmt->get_result();
    while ($row = $performanceResult->fetch_assoc()) {
        $aid = $row['athlete_id'];
        if (!isset($performanceByAthlete[$aid])) {
            $performanceByAthlete[$aid] = [
                'athlete_name' => $row['athlete_name'],
                'fitness'      => [],
                'match'        => []
            ];
        }
        $performanceByAthlete[$aid][$row['category']][] = $row;
    }
}

// ── Game stats (Schedule tab) ─────────────────────────────────────────────────
$allGames = [];
$allGamesStmt = $conn->prepare(
    "SELECT g.opponent, g.game_datetime, g.location, g.notes, a.full_name AS athlete_name
     FROM game_schedule g
     INNER JOIN athletes a ON g.athlete_id = a.athlete_id
     ORDER BY g.game_datetime DESC"
);
if ($allGamesStmt) {
    $allGamesStmt->execute();
    $allGamesResult = $allGamesStmt->get_result();
    while ($row = $allGamesResult->fetch_assoc()) {
        $allGames[] = $row;
    }
}

// ── Schedule (view-only) ──────────────────────────────────────────────────────
$upcomingPractices = [];
$upcomingGames     = [];

$schedPracticeStmt = $conn->prepare(
    "SELECT p.title, p.start_time, p.end_time, p.location, a.full_name AS athlete_name
     FROM practice_schedule p
     INNER JOIN athletes a ON p.athlete_id = a.athlete_id
     WHERE p.start_time >= NOW()
     ORDER BY p.start_time ASC
     LIMIT 5"
);
if ($schedPracticeStmt) {
    $schedPracticeStmt->execute();
    $schedPracticeResult = $schedPracticeStmt->get_result();
    while ($row = $schedPracticeResult->fetch_assoc()) {
        $upcomingPractices[] = $row;
    }
}

$schedGameStmt = $conn->prepare(
    "SELECT g.opponent, g.game_datetime, g.location, g.notes, a.full_name AS athlete_name
     FROM game_schedule g
     INNER JOIN athletes a ON g.athlete_id = a.athlete_id
     WHERE g.game_datetime >= NOW()
     ORDER BY g.game_datetime ASC
     LIMIT 5"
);
if ($schedGameStmt) {
    $schedGameStmt->execute();
    $schedGameResult = $schedGameStmt->get_result();
    while ($row = $schedGameResult->fetch_assoc()) {
        $upcomingGames[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Athletic Trainer Dashboard</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

<div class="app">

    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Rollins Athletics</h2>
        <div class="sidebar-role">Athletic Trainer</div>
        <ul>
            <li onclick="showPage(event, 'profile')" class="<?php echo ($activeTab === 'profile') ? 'active' : ''; ?>">
                <svg class="nav-icon" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                Profile
            </li>
            <li onclick="showPage(event, 'athletes')" class="<?php echo ($activeTab === 'athletes') ? 'active' : ''; ?>">
                <svg class="nav-icon" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Athletes
            </li>
            <li onclick="showPage(event, 'medical')" class="<?php echo ($activeTab === 'medical') ? 'active' : ''; ?>">
                <svg class="nav-icon" viewBox="0 0 24 24"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                Medical
            </li>
            <li onclick="showPage(event, 'communication')" class="<?php echo ($activeTab === 'communication') ? 'active' : ''; ?>">
                <svg class="nav-icon" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                Communication
            </li>
            <li onclick="showPage(event, 'performance')" class="<?php echo ($activeTab === 'performance') ? 'active' : ''; ?>">
                <svg class="nav-icon" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                Performance
            </li>
            <li onclick="showPage(event, 'schedule')" class="<?php echo ($activeTab === 'schedule') ? 'active' : ''; ?>">
                <svg class="nav-icon" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Schedule
            </li>
        </ul>
    </div>

    <!-- Main -->
    <div class="main">

        <!-- Topbar -->
        <div class="topbar">
            <h1>Rollins Athletics Dashboard</h1>
            <div class="user">
                Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>
                | <a href="logout.php">Logout</a>
            </div>
        </div>

        <div class="content">

            <!-- ── STAT CARDS ─────────────────────────────────────────────── -->
            <?php
            $totalAthletes   = count($allAthletes);
            $activeInjuries  = count(array_filter($medicalRecords, fn($r) => $r['status'] === 'active'));
            $recovering      = count(array_filter($medicalRecords, fn($r) => $r['status'] === 'recovering'));
            $cleared         = count(array_filter($medicalRecords, fn($r) => $r['status'] === 'cleared'));
            $upcomingCount   = count($upcomingPractices) + count($upcomingGames);
            ?>
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-card__icon stat-card__icon--blue">
                        <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <div class="stat-card__body">
                        <div class="stat-card__value"><?php echo $totalAthletes; ?></div>
                        <div class="stat-card__label">Total Athletes</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card__icon stat-card__icon--red">
                        <svg viewBox="0 0 24 24"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                    </div>
                    <div class="stat-card__body">
                        <div class="stat-card__value"><?php echo $activeInjuries; ?></div>
                        <div class="stat-card__label">Active Injuries</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card__icon stat-card__icon--orange">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div class="stat-card__body">
                        <div class="stat-card__value"><?php echo $recovering; ?></div>
                        <div class="stat-card__label">Recovering</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card__icon stat-card__icon--green">
                        <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <div class="stat-card__body">
                        <div class="stat-card__value"><?php echo $cleared; ?></div>
                        <div class="stat-card__label">Cleared</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card__icon stat-card__icon--gold">
                        <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </div>
                    <div class="stat-card__body">
                        <div class="stat-card__value"><?php echo $upcomingCount; ?></div>
                        <div class="stat-card__label">Upcoming Events</div>
                    </div>
                </div>
            </div>

            <!-- ── PROFILE ────────────────────────────────────────────────── -->
            <div id="profile" class="page <?php echo ($activeTab === 'profile') ? 'active' : ''; ?>">
                <h1>Profile</h1>
                <div class="card">
                    <?php if ($atProfileRow): ?>
                        <p><strong>Name:</strong>          <?php echo htmlspecialchars($atProfileRow['name']); ?></p>
                        <p><strong>Email:</strong>         <?php echo htmlspecialchars($atProfileRow['email']); ?></p>
                        <p><strong>Specialty:</strong>     <?php echo (isset($atProfileRow['specialty'])     && trim((string)$atProfileRow['specialty'])     !== '') ? htmlspecialchars($atProfileRow['specialty'])     : 'Not set'; ?></p>
                        <p><strong>Certification:</strong> <?php echo (isset($atProfileRow['certification']) && trim((string)$atProfileRow['certification']) !== '') ? htmlspecialchars($atProfileRow['certification']) : 'Not set'; ?></p>
                    <?php else: ?>
                        <p>Unable to load profile.</p>
                    <?php endif; ?>
                    <div style="margin-top:15px;">
                        <a href="at_profile_edit.php"><button>Edit Profile</button></a>
                    </div>
                </div>
            </div>

            <!-- ── ATHLETES ───────────────────────────────────────────────── -->
            <div id="athletes" class="page <?php echo ($activeTab === 'athletes') ? 'active' : ''; ?>">
                <h1>Athletes</h1>
                <?php if (empty($allAthletes)): ?>
                    <div class="card"><p>No athlete profiles found.</p></div>
                <?php else: ?>
                    <?php foreach ($allAthletes as $ath): ?>
                        <div class="card">
                            <p><strong>Name:</strong>     <?php echo htmlspecialchars($ath['full_name']); ?></p>
                            <p><strong>Age:</strong>      <?php echo htmlspecialchars($ath['age'] ?? '—'); ?></p>
                            <p><strong>Sport:</strong>    <?php echo htmlspecialchars($ath['sport'] ?? '—'); ?></p>
                            <p><strong>Position:</strong> <?php echo htmlspecialchars($ath['position'] ?? '—'); ?></p>
                            <p><strong>Jersey #:</strong> <?php echo htmlspecialchars($ath['jersey_number'] ?? '—'); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- ── MEDICAL ────────────────────────────────────────────────── -->
            <div id="medical" class="page <?php echo ($activeTab === 'medical') ? 'active' : ''; ?>">
                <h1>Medical Records</h1>

                <div class="card">
                    <a href="at_medical_create.php"><button>Add Medical Record</button></a>
                </div>

                <?php if (empty($medicalRecords)): ?>
                    <div class="card"><p>No medical records on file.</p></div>
                <?php else: ?>
                    <?php foreach ($medicalRecords as $record): ?>
                        <div class="card">
                            <p><strong>Athlete:</strong>   <?php echo htmlspecialchars($record['athlete_name']); ?></p>
                            <p><strong>Injury:</strong>    <?php echo htmlspecialchars($record['injury_title']); ?></p>
                            <p><strong>Reported:</strong>  <?php echo htmlspecialchars(date('M j, Y', strtotime($record['reported_date']))); ?></p>
                            <p><strong>Status:</strong>    <?php echo htmlspecialchars(ucwords($record['status'])); ?></p>
                            <p><strong>Clearance:</strong> <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $record['clearance_status']))); ?></p>

                            <?php if (!empty($record['expected_return_date'])): ?>
                                <p><strong>Expected Return:</strong> <?php echo htmlspecialchars(date('M j, Y', strtotime($record['expected_return_date']))); ?></p>
                            <?php endif; ?>

                            <?php if (!empty($record['cleared_date'])): ?>
                                <p><strong>Cleared Date:</strong> <?php echo htmlspecialchars(date('M j, Y', strtotime($record['cleared_date']))); ?></p>
                            <?php endif; ?>

                            <?php if (!empty($record['injury_details'])): ?>
                                <p><strong>Details:</strong> <?php echo nl2br(htmlspecialchars($record['injury_details'])); ?></p>
                            <?php endif; ?>

                            <?php if (!empty($record['notes'])): ?>
                                <p><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($record['notes'])); ?></p>
                            <?php endif; ?>

                            <div style="display:flex; gap:10px; margin-top:15px;">
                                <a href="at_medical_edit.php?medical_record_id=<?php echo urlencode((string)$record['medical_record_id']); ?>">
                                    <button>Edit</button>
                                </a>
                                <form method="POST" action="at_medical_delete.php"
                                      onsubmit="return confirm('Delete this medical record? This cannot be undone.');"
                                      style="margin:0;">
                                    <input type="hidden" name="medical_record_id" value="<?php echo htmlspecialchars((string)$record['medical_record_id']); ?>">
                                    <button type="submit">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- ── COMMUNICATION ──────────────────────────────────────────── -->
            <div id="communication" class="page <?php echo ($activeTab === 'communication') ? 'active' : ''; ?>">
                <h1>Communication</h1>

                <div class="card">
                    <h3>Direct messages</h3>
                    <form method="get" action="at_dashboard.php" style="margin-bottom:15px;">
                        <input type="hidden" name="tab" value="communication">
                        <label for="at_athlete_select">Athlete</label>
                        <select id="at_athlete_select" name="athlete_id" onchange="this.form.submit()"
                                style="width:100%; max-width:360px; margin:8px 0;">
                            <option value="">Select an athlete…</option>
                            <?php foreach ($atAthletes as $aa): ?>
                                <option value="<?php echo htmlspecialchars((string)$aa['athlete_id']); ?>"
                                    <?php echo ($selectedAthleteId === (int)$aa['athlete_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($aa['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>

                    <?php if ($selectedAthleteId <= 0 || $selectedAthleteUserId === null): ?>
                        <p>Select an athlete to view and send direct messages.</p>
                    <?php else: ?>
                        <div class="chat-box" id="at-chat">
                            <?php if (empty($atDirectMessages)): ?>
                                <p>No messages yet.</p>
                            <?php else: ?>
                                <?php foreach ($atDirectMessages as $msg): ?>
                                    <p>
                                        <strong><?php echo htmlspecialchars($msg['sender_name']); ?></strong>:
                                        <?php echo htmlspecialchars($msg['content']); ?>
                                        <br>
                                        <small><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($msg['sent_at']))); ?></small>
                                    </p>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <form class="chat-input" method="POST" action="at_message_handler.php">
                            <input type="hidden" name="athlete_id" value="<?php echo htmlspecialchars((string)$selectedAthleteId); ?>">
                            <input type="text" name="content" placeholder="Message this athlete..." required>
                            <button type="submit">Send</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ── PERFORMANCE ────────────────────────────────────────────── -->
            <div id="performance" class="page <?php echo ($activeTab === 'performance') ? 'active' : ''; ?>">
                <h1>Performance Metrics</h1>

                <?php if (empty($performanceByAthlete)): ?>
                    <div class="card"><p>No performance records on file for any athlete.</p></div>
                <?php else: ?>
                    <?php foreach ($performanceByAthlete as $aid => $data): ?>
                        <div class="card">
                            <h3><?php echo htmlspecialchars($data['athlete_name']); ?></h3>

                            <?php if (!empty($data['fitness'])): ?>
                                <h4 style="margin-top:15px; color:#0033A0;">Fitness</h4>
                                <?php foreach ($data['fitness'] as $record): ?>
                                    <p>
                                        <strong><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $record['metric_name']))); ?>:</strong>
                                        <?php echo htmlspecialchars((string)$record['metric_value']); ?>
                                        <br>
                                        <small>
                                            <?php echo htmlspecialchars(date('M j, Y', strtotime($record['record_date']))); ?>
                                            <?php if (!empty($record['notes'])): ?>
                                                — <?php echo htmlspecialchars($record['notes']); ?>
                                            <?php endif; ?>
                                        </small>
                                    </p>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <?php if (!empty($data['match'])): ?>
                                <h4 style="margin-top:15px; color:#0033A0;">Match Statistics</h4>
                                <?php foreach ($data['match'] as $record): ?>
                                    <p>
                                        <strong><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $record['metric_name']))); ?>:</strong>
                                        <?php echo htmlspecialchars((string)$record['metric_value']); ?>
                                        <br>
                                        <small>
                                            <?php echo htmlspecialchars(date('M j, Y', strtotime($record['record_date']))); ?>
                                            <?php if (!empty($record['notes'])): ?>
                                                — <?php echo htmlspecialchars($record['notes']); ?>
                                            <?php endif; ?>
                                        </small>
                                    </p>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <?php if (empty($data['fitness']) && empty($data['match'])): ?>
                                <p>No performance records yet.</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- ── SCHEDULE (view-only) ───────────────────────────────────── -->
            <div id="schedule" class="page <?php echo ($activeTab === 'schedule') ? 'active' : ''; ?>">
                <h1>Schedule</h1>

                <div class="card">
                    <h3>Upcoming Practices</h3>
                    <?php if (empty($upcomingPractices)): ?>
                        <p>No upcoming practices scheduled.</p>
                    <?php else: ?>
                        <?php foreach ($upcomingPractices as $practice): ?>
                            <p><strong><?php echo htmlspecialchars($practice['title']); ?></strong></p>
                            <p><strong>Athlete:</strong> <?php echo htmlspecialchars($practice['athlete_name']); ?></p>
                            <p>
                                <?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($practice['start_time']))); ?>
                                <?php if (!empty($practice['end_time'])): ?>
                                    – <?php echo htmlspecialchars(date('g:i A', strtotime($practice['end_time']))); ?>
                                <?php endif; ?>
                            </p>
                            <p><strong>Location:</strong> <?php echo htmlspecialchars($practice['location'] ?? ''); ?></p>
                            <hr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <h3>Upcoming Games</h3>
                    <?php if (empty($upcomingGames)): ?>
                        <p>No upcoming games scheduled.</p>
                    <?php else: ?>
                        <?php foreach ($upcomingGames as $game): ?>
                            <p><strong>Opponent:</strong>   <?php echo htmlspecialchars($game['opponent']); ?></p>
                            <p><strong>Athlete:</strong>    <?php echo htmlspecialchars($game['athlete_name']); ?></p>
                            <p><strong>Date/Time:</strong>  <?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($game['game_datetime']))); ?></p>
                            <p><strong>Location:</strong>   <?php echo htmlspecialchars($game['location']); ?></p>
                            <?php if (!empty($game['notes'])): ?>
                                <p><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($game['notes'])); ?></p>
                            <?php endif; ?>
                            <hr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <h3>Game Statistics (All Athletes)</h3>
                    <?php if (empty($allGames)): ?>
                        <p>No games on record.</p>
                    <?php else: ?>
                        <?php foreach ($allGames as $game): ?>
                            <p><strong>Opponent:</strong>  <?php echo htmlspecialchars($game['opponent']); ?></p>
                            <p><strong>Athlete:</strong>   <?php echo htmlspecialchars($game['athlete_name']); ?></p>
                            <p><strong>Date/Time:</strong> <?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($game['game_datetime']))); ?></p>
                            <p><strong>Location:</strong>  <?php echo htmlspecialchars($game['location']); ?></p>
                            <?php if (!empty($game['notes'])): ?>
                                <p><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($game['notes'])); ?></p>
                            <?php endif; ?>
                            <hr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /.content -->
    </div><!-- /.main -->
</div><!-- /.app -->

<script src="assets/js/app.js"></script>
</body>
</html>
