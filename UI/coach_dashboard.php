<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$role = isset($_SESSION['role']) ? strtolower(trim((string)$_SESSION['role'])) : '';
if ($role !== 'coach') {
    header("Location: index.php");
    exit();
}

$activeTab = $_GET['tab'] ?? 'schedule';
$allowedTabs = ['profile', 'schedule', 'communication', 'medical'];
if (!in_array($activeTab, $allowedTabs, true)) {
    $activeTab = 'schedule';
}

require 'db.php';

$coachUserId = (int)$_SESSION['user_id'];
$coachProfileRow = null;

$coachProfileStmt = $conn->prepare(
    "SELECT u.name, u.email, c.sport, c.title
     FROM users u
     LEFT JOIN coaches c ON c.user_id = u.user_id
     WHERE u.user_id = ?
     LIMIT 1"
);
if ($coachProfileStmt) {
    $coachProfileStmt->bind_param("i", $coachUserId);
    $coachProfileStmt->execute();
    $coachProfileRow = $coachProfileStmt->get_result()->fetch_assoc();
}

$upcomingPractices = [];
$upcomingGames     = [];

$practicePreviewStmt = $conn->prepare(
    "SELECT p.title, p.start_time, p.end_time, p.location, a.full_name AS athlete_name
     FROM practice_schedule p
     INNER JOIN athletes a ON p.athlete_id = a.athlete_id
     WHERE p.start_time >= NOW()
     ORDER BY p.start_time ASC
     LIMIT 1"
);
if ($practicePreviewStmt) {
    $practicePreviewStmt->execute();
    $r = $practicePreviewStmt->get_result();
    while ($row = $r->fetch_assoc()) { $upcomingPractices[] = $row; }
}

$gamesPreviewStmt = $conn->prepare(
    "SELECT g.opponent, g.game_datetime, g.location, g.notes, a.full_name AS athlete_name
     FROM game_schedule g
     INNER JOIN athletes a ON g.athlete_id = a.athlete_id
     WHERE g.game_datetime >= NOW()
     ORDER BY g.game_datetime ASC
     LIMIT 3"
);
if ($gamesPreviewStmt) {
    $gamesPreviewStmt->execute();
    $r = $gamesPreviewStmt->get_result();
    while ($row = $r->fetch_assoc()) { $upcomingGames[] = $row; }
}

$coachMedicalRecords = [];
$coachMedicalStmt = $conn->prepare(
    "SELECT m.injury_title, m.injury_details, m.reported_date, m.status, m.clearance_status,
            m.expected_return_date, m.cleared_date, m.notes, a.full_name AS athlete_name
     FROM medical_records m
     INNER JOIN athletes a ON m.athlete_id = a.athlete_id
     ORDER BY m.reported_date DESC"
);
if ($coachMedicalStmt) {
    $coachMedicalStmt->execute();
    $r = $coachMedicalStmt->get_result();
    while ($row = $r->fetch_assoc()) { $coachMedicalRecords[] = $row; }
}

$coachAthletes       = [];
$teamAnnouncementsCoach = [];
$coachDirectMessages = [];
$selectedAthleteId   = isset($_GET['athlete_id']) ? (int)$_GET['athlete_id'] : 0;
$selectedAthleteUserId = null;

$coachAthletesStmt = $conn->prepare("SELECT athlete_id, full_name FROM athletes ORDER BY full_name ASC");
if ($coachAthletesStmt) {
    $coachAthletesStmt->execute();
    $r = $coachAthletesStmt->get_result();
    while ($row = $r->fetch_assoc()) { $coachAthletes[] = $row; }
}

$coachAnnStmt = $conn->prepare(
    "SELECT u.name AS sender_name, m.content, m.sent_at
     FROM messages m
     INNER JOIN users u ON m.sender_user_id = u.user_id
     WHERE m.message_type = 'announcement' AND m.recipient_group = 'team'
     ORDER BY m.sent_at DESC"
);
if ($coachAnnStmt) {
    $coachAnnStmt->execute();
    $r = $coachAnnStmt->get_result();
    while ($row = $r->fetch_assoc()) { $teamAnnouncementsCoach[] = $row; }
}

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
                $dmStmt->bind_param("iiiii", $selectedAthleteId, $coachUserId, $selectedAthleteUserId, $selectedAthleteUserId, $coachUserId);
                $dmStmt->execute();
                $r = $dmStmt->get_result();
                while ($row = $r->fetch_assoc()) { $coachDirectMessages[] = $row; }
            }
        }
    }
}

// ── Stat counts ───────────────────────────────────────────────────────────────
$totalAthletes   = count($coachAthletes);
$totalPractices  = count($upcomingPractices);
$totalGames      = count($upcomingGames);
$activeInjuries  = count(array_filter($coachMedicalRecords, fn($r) => $r['status'] === 'active'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Coach Dashboard — Rollins Athletics</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

<div class="app">

    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Rollins Athletics</h2>
        <div class="sidebar-role">Coach</div>
        <ul>
            <li onclick="showPage(event, 'profile')" class="<?php echo ($activeTab === 'profile') ? 'active' : ''; ?>">
                <svg class="nav-icon" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                Profile
            </li>
            <li onclick="showPage(event, 'schedule')" class="<?php echo ($activeTab === 'schedule') ? 'active' : ''; ?>">
                <svg class="nav-icon" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Schedule
            </li>
            <li onclick="showPage(event, 'communication')" class="<?php echo ($activeTab === 'communication') ? 'active' : ''; ?>">
                <svg class="nav-icon" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                Communication
            </li>
            <li onclick="showPage(event, 'medical')" class="<?php echo ($activeTab === 'medical') ? 'active' : ''; ?>">
                <svg class="nav-icon" viewBox="0 0 24 24"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                Medical
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

            <!-- ── STAT CARDS ──────────────────────────────────────────────── -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-card__icon stat-card__icon--blue">
                        <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <div class="stat-card__body">
                        <div class="stat-card__value"><?php echo $totalAthletes; ?></div>
                        <div class="stat-card__label">Athletes</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card__icon stat-card__icon--gold">
                        <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </div>
                    <div class="stat-card__body">
                        <div class="stat-card__value"><?php echo $totalPractices; ?></div>
                        <div class="stat-card__label">Next Practice</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card__icon stat-card__icon--blue">
                        <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    </div>
                    <div class="stat-card__body">
                        <div class="stat-card__value"><?php echo $totalGames; ?></div>
                        <div class="stat-card__label">Upcoming Games</div>
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
            </div>

            <!-- ── PROFILE ────────────────────────────────────────────────── -->
            <div id="profile" class="page <?php echo ($activeTab === 'profile') ? 'active' : ''; ?>">
                <h1>Profile</h1>
                <div class="card">
                    <?php if ($coachProfileRow): ?>
                        <p><strong>Name:</strong>  <?php echo htmlspecialchars($coachProfileRow['name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($coachProfileRow['email']); ?></p>
                        <p><strong>Sport:</strong> <?php echo (isset($coachProfileRow['sport']) && trim((string)$coachProfileRow['sport']) !== '') ? htmlspecialchars($coachProfileRow['sport']) : 'Not set'; ?></p>
                        <p><strong>Title:</strong> <?php echo (isset($coachProfileRow['title']) && trim((string)$coachProfileRow['title']) !== '') ? htmlspecialchars($coachProfileRow['title']) : 'Not set'; ?></p>
                    <?php else: ?>
                        <p>Unable to load profile.</p>
                    <?php endif; ?>
                    <div style="margin-top:15px;">
                        <a href="coach_profile_edit.php"><button>Edit Profile</button></a>
                    </div>
                </div>
            </div>

            <!-- ── SCHEDULE ───────────────────────────────────────────────── -->
            <div id="schedule" class="page <?php echo ($activeTab === 'schedule') ? 'active' : ''; ?>">
                <h1>Schedule</h1>
                <div class="card">
                    <div class="btn-row">
                        <a href="coach_practice_schedule.php"><button>View Full Practice Schedule</button></a>
                        <a href="coach_practice_create.php"><button class="btn--ghost">Add Practice</button></a>
                    </div>
                    <h3>Next Practice</h3>
                    <?php if (empty($upcomingPractices)): ?>
                        <p>No upcoming practices scheduled.</p>
                    <?php else: ?>
                        <?php foreach ($upcomingPractices as $practice): ?>
                            <p><strong><?php echo htmlspecialchars($practice['title']); ?></strong></p>
                            <p><strong>Athlete:</strong> <?php echo htmlspecialchars($practice['athlete_name'] ?? ''); ?></p>
                            <p><?php echo htmlspecialchars($practice['start_time']); ?><?php if (!empty($practice['end_time'])): ?> – <?php echo htmlspecialchars($practice['end_time']); ?><?php endif; ?></p>
                            <p><strong>Location:</strong> <?php echo htmlspecialchars($practice['location'] ?? ''); ?></p>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <div class="btn-row">
                        <a href="coach_game_schedule.php"><button>View Full Game Schedule</button></a>
                        <a href="coach_game_create.php"><button class="btn--ghost">Add Game</button></a>
                    </div>
                    <h3>Next 3 Upcoming Games</h3>
                    <?php if (empty($upcomingGames)): ?>
                        <p>No upcoming games scheduled.</p>
                    <?php else: ?>
                        <?php foreach ($upcomingGames as $game): ?>
                            <p><strong>Opponent:</strong>  <?php echo htmlspecialchars($game['opponent']); ?></p>
                            <p><strong>Athlete:</strong>   <?php echo htmlspecialchars($game['athlete_name'] ?? ''); ?></p>
                            <p><strong>Date/Time:</strong> <?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($game['game_datetime']))); ?></p>
                            <p><strong>Location:</strong>  <?php echo htmlspecialchars($game['location']); ?></p>
                            <?php if (!empty($game['notes'])): ?><p><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($game['notes'])); ?></p><?php endif; ?>
                            <hr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ── COMMUNICATION ──────────────────────────────────────────── -->
            <div id="communication" class="page <?php echo ($activeTab === 'communication') ? 'active' : ''; ?>">
                <h1>Communication</h1>

                <div class="card">
                    <h3>Team Announcements</h3>
                    <p style="margin-top:0;color:#4b5563;font-size:13px;">One-way posts visible to all athletes.</p>
                    <?php if (empty($teamAnnouncementsCoach)): ?>
                        <p>No team announcements yet.</p>
                    <?php else: ?>
                        <?php foreach ($teamAnnouncementsCoach as $ann): ?>
                            <p><strong><?php echo htmlspecialchars($ann['sender_name']); ?></strong> <small><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($ann['sent_at']))); ?></small><br><?php echo nl2br(htmlspecialchars($ann['content'])); ?></p>
                            <hr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <form method="POST" action="coach_message_handler.php" style="margin-top:15px;">
                        <input type="hidden" name="action" value="announcement">
                        <textarea name="content" rows="3" placeholder="Post a team announcement..." required style="width:100%; margin:10px 0; padding:10px; border:1px solid #e4e7ed; border-radius:6px;"></textarea>
                        <button type="submit">Post Announcement</button>
                    </form>
                </div>

                <div class="card">
                    <h3>Direct Messages</h3>
                    <form method="get" action="coach_dashboard.php" style="margin-bottom:15px;">
                        <input type="hidden" name="tab" value="communication">
                        <label for="coach_athlete_select">Athlete</label>
                        <select id="coach_athlete_select" name="athlete_id" onchange="this.form.submit()" style="width:100%; max-width:360px; margin:8px 0;">
                            <option value="">Select an athlete…</option>
                            <?php foreach ($coachAthletes as $ca): ?>
                                <option value="<?php echo htmlspecialchars((string)$ca['athlete_id']); ?>" <?php echo ($selectedAthleteId === (int)$ca['athlete_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ca['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>

                    <?php if ($selectedAthleteId <= 0 || $selectedAthleteUserId === null): ?>
                        <p>Select an athlete to view and send direct messages.</p>
                    <?php else: ?>
                        <div class="chat-box" id="coach-chat">
                            <?php if (empty($coachDirectMessages)): ?>
                                <p>No messages yet.</p>
                            <?php else: ?>
                                <?php foreach ($coachDirectMessages as $msg): ?>
                                    <p><strong><?php echo htmlspecialchars($msg['sender_name']); ?></strong>: <?php echo htmlspecialchars($msg['content']); ?><br><small><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($msg['sent_at']))); ?></small></p>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <form class="chat-input" method="POST" action="coach_message_handler.php">
                            <input type="hidden" name="action" value="direct">
                            <input type="hidden" name="athlete_id" value="<?php echo htmlspecialchars((string)$selectedAthleteId); ?>">
                            <input type="text" name="content" placeholder="Message this athlete..." required>
                            <button type="submit">Send</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ── MEDICAL ────────────────────────────────────────────────── -->
            <div id="medical" class="page <?php echo ($activeTab === 'medical') ? 'active' : ''; ?>">
                <h1>Medical Records</h1>
                <?php if (empty($coachMedicalRecords)): ?>
                    <div class="card"><p>No medical records on file for any athlete.</p></div>
                <?php else: ?>
                    <?php foreach ($coachMedicalRecords as $record): ?>
                        <div class="card">
                            <p><strong>Athlete:</strong>   <?php echo htmlspecialchars($record['athlete_name'] ?? ''); ?></p>
                            <p><strong>Injury:</strong>    <?php echo htmlspecialchars($record['injury_title']); ?></p>
                            <p><strong>Reported:</strong>  <?php echo htmlspecialchars(date('M j, Y', strtotime($record['reported_date']))); ?></p>
                            <p><strong>Status:</strong>    <?php echo htmlspecialchars(ucwords($record['status'])); ?></p>
                            <p><strong>Clearance:</strong> <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $record['clearance_status']))); ?></p>
                            <?php if (!empty($record['expected_return_date'])): ?><p><strong>Expected Return:</strong> <?php echo htmlspecialchars(date('M j, Y', strtotime($record['expected_return_date']))); ?></p><?php endif; ?>
                            <?php if (!empty($record['cleared_date'])): ?><p><strong>Cleared Date:</strong> <?php echo htmlspecialchars(date('M j, Y', strtotime($record['cleared_date']))); ?></p><?php endif; ?>
                            <?php if (!empty($record['injury_details'])): ?><p><strong>Details:</strong> <?php echo nl2br(htmlspecialchars($record['injury_details'])); ?></p><?php endif; ?>
                            <?php if (!empty($record['notes'])): ?><p><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($record['notes'])); ?></p><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div><!-- /.content -->
    </div><!-- /.main -->
</div><!-- /.app -->

<script src="assets/js/app.js"></script>
</body>
</html>
