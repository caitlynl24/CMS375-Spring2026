<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$sessionRoleNorm = isset($_SESSION['role']) ? strtolower(trim((string)$_SESSION['role'])) : '';
if ($sessionRoleNorm === 'coach') {
    header("Location: coach_dashboard.php");
    exit();
}
if ($sessionRoleNorm === 'athletic_trainer') {
    header("Location: at_dashboard.php");
    exit();
}

$role = $_SESSION['role'];

require 'db.php';

$activeTab = $_GET['tab'] ?? 'profile';
$allowedTabs = ['profile', 'performance', 'schedule', 'communication', 'medical'];
if (!in_array($activeTab, $allowedTabs, true)) {
    $activeTab = 'profile';
}

$conversation = $_GET['conversation'] ?? 'coach';
$allowedConversations = ['coach', 'athletic_trainer'];
if (!in_array($conversation, $allowedConversations, true)) {
    $conversation = 'coach';
}

$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT athlete_id, full_name, age, sport, position FROM athletes WHERE user_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $athlete = $stmt->get_result()->fetch_assoc();
} else {
    $athlete = null;
}

$upcomingPractices       = [];
$fitnessRecords          = [];
$matchRecords            = [];
$hasAnyPerformanceRecords = false;
$messages                = [];
$teamAnnouncements       = [];
$medicalRecords          = [];
$upcomingGames           = [];
$todaysClasses           = [];

if ($athlete) {
    $athleteId = (int)$athlete['athlete_id'];

    $practiceStmt = $conn->prepare(
        "SELECT title, start_time, end_time, location
         FROM practice_schedule
         WHERE athlete_id = ? AND start_time >= NOW()
         ORDER BY start_time ASC LIMIT 1"
    );
    if ($practiceStmt) {
        $practiceStmt->bind_param("i", $athleteId);
        $practiceStmt->execute();
        $r = $practiceStmt->get_result();
        while ($row = $r->fetch_assoc()) { $upcomingPractices[] = $row; }
    }

    $performanceStmt = $conn->prepare(
        "SELECT category, metric_name, metric_value, record_date, notes
         FROM performance_records
         WHERE athlete_id = ?
         ORDER BY category ASC, record_date DESC, metric_name ASC"
    );
    if ($performanceStmt) {
        $performanceStmt->bind_param("i", $athleteId);
        $performanceStmt->execute();
        $r = $performanceStmt->get_result();
        while ($record = $r->fetch_assoc()) {
            $hasAnyPerformanceRecords = true;
            if ($record['category'] === 'fitness') { $fitnessRecords[] = $record; }
            elseif ($record['category'] === 'match') { $matchRecords[] = $record; }
        }
    }

    $peerRole = $conversation === 'coach' ? 'coach' : 'athletic_trainer';
    $communicationPeerUserId = null;
    $peerLookupStmt = $conn->prepare("SELECT user_id FROM users WHERE LOWER(TRIM(role)) = ? ORDER BY user_id ASC LIMIT 1");
    if ($peerLookupStmt) {
        $peerLookupStmt->bind_param("s", $peerRole);
        $peerLookupStmt->execute();
        $peerRow = $peerLookupStmt->get_result()->fetch_assoc();
        if ($peerRow) { $communicationPeerUserId = (int)$peerRow['user_id']; }
    }

    if ($communicationPeerUserId !== null) {
        $messagesStmt = $conn->prepare(
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
        if ($messagesStmt) {
            $messagesStmt->bind_param("iiiii", $athleteId, $userId, $communicationPeerUserId, $communicationPeerUserId, $userId);
            $messagesStmt->execute();
            $r = $messagesStmt->get_result();
            while ($msg = $r->fetch_assoc()) { $messages[] = $msg; }
        }
    }

    $teamAnnStmt = $conn->prepare(
        "SELECT u.name AS sender_name, m.content, m.sent_at
         FROM messages m
         INNER JOIN users u ON m.sender_user_id = u.user_id
         WHERE m.message_type = 'announcement' AND m.recipient_group = 'team'
         ORDER BY m.sent_at DESC"
    );
    if ($teamAnnStmt) {
        $teamAnnStmt->execute();
        $r = $teamAnnStmt->get_result();
        while ($row = $r->fetch_assoc()) { $teamAnnouncements[] = $row; }
    }

    $medicalStmt = $conn->prepare(
        "SELECT injury_title, injury_details, reported_date, status, clearance_status,
                expected_return_date, cleared_date, notes
         FROM medical_records
         WHERE athlete_id = ?
         ORDER BY reported_date DESC"
    );
    if ($medicalStmt) {
        $medicalStmt->bind_param("i", $athleteId);
        $medicalStmt->execute();
        $r = $medicalStmt->get_result();
        while ($row = $r->fetch_assoc()) { $medicalRecords[] = $row; }
    }

    $gamesStmt = $conn->prepare(
        "SELECT opponent, game_datetime, location, notes
         FROM game_schedule
         WHERE athlete_id = ? AND game_datetime >= NOW()
         ORDER BY game_datetime ASC LIMIT 3"
    );
    if ($gamesStmt) {
        $gamesStmt->bind_param("i", $athleteId);
        $gamesStmt->execute();
        $r = $gamesStmt->get_result();
        while ($row = $r->fetch_assoc()) { $upcomingGames[] = $row; }
    }

    $todayDow = (int)(new DateTime())->format('N');
    $classTodayStmt = $conn->prepare(
        "SELECT course_name, start_time, end_time, location, notes
         FROM class_schedule
         WHERE athlete_id = ? AND day_of_week = ?
         ORDER BY start_time ASC"
    );
    if ($classTodayStmt) {
        $classTodayStmt->bind_param("ii", $athleteId, $todayDow);
        $classTodayStmt->execute();
        $r = $classTodayStmt->get_result();
        while ($row = $r->fetch_assoc()) {
            $todaysClasses[] = $row;
        }
    }
}

// ── Stat counts ───────────────────────────────────────────────────────────────
$totalPractices = count($upcomingPractices);
$totalGames     = count($upcomingGames);
$totalMedical   = count($medicalRecords);
$totalPerf      = count($fitnessRecords) + count($matchRecords);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rollins Athletics Dashboard</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

<div class="app">

    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Rollins Athletics</h2>
        <div class="sidebar-role">Athlete</div>
        <ul>
            <li onclick="showPage(event, 'profile')" class="<?php echo ($activeTab === 'profile') ? 'active' : ''; ?>">
                <svg class="nav-icon" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                Profile
            </li>
            <li onclick="showPage(event, 'performance')" class="<?php echo ($activeTab === 'performance') ? 'active' : ''; ?>">
                <svg class="nav-icon" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                Performance
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
            <?php if ($athlete): ?>
            <div class="stats-row">
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
                    <div class="stat-card__icon stat-card__icon--green">
                        <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                    </div>
                    <div class="stat-card__body">
                        <div class="stat-card__value"><?php echo $totalPerf; ?></div>
                        <div class="stat-card__label">Performance Records</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card__icon stat-card__icon--red">
                        <svg viewBox="0 0 24 24"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                    </div>
                    <div class="stat-card__body">
                        <div class="stat-card__value"><?php echo $totalMedical; ?></div>
                        <div class="stat-card__label">Medical Records</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- ── PROFILE ────────────────────────────────────────────────── -->
            <div id="profile" class="page <?php echo ($activeTab === 'profile') ? 'active' : ''; ?>">
                <h1>Profile</h1>
                <div class="card">
                    <?php if (!$athlete): ?>
                        <p>No athlete profile found for your account.</p>
                        <a href="athlete_create.php"><button>Create Athlete Profile</button></a>
                    <?php else: ?>
                        <p><strong>Name:</strong>     <?php echo htmlspecialchars($athlete['full_name']); ?></p>
                        <p><strong>Age:</strong>      <?php echo htmlspecialchars($athlete['age'] ?? ''); ?></p>
                        <p><strong>Sport:</strong>    <?php echo htmlspecialchars($athlete['sport'] ?? ''); ?></p>
                        <p><strong>Position:</strong> <?php echo htmlspecialchars($athlete['position'] ?? ''); ?></p>
                        <a href="athletes.php"><button>Manage Athlete Profile</button></a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ── PERFORMANCE ────────────────────────────────────────────── -->
            <div id="performance" class="page <?php echo ($activeTab === 'performance') ? 'active' : ''; ?>">
                <h1>Performance Tracking</h1>
                <?php if ($athlete): ?>
                    <div class="card card--flat">
                        <a href="performance_create.php"><button>Add Performance Record</button></a>
                    </div>
                <?php endif; ?>

                <?php if (!$athlete): ?>
                    <div class="card"><p>No athlete profile found.</p><a href="athlete_create.php"><button>Create Athlete Profile</button></a></div>
                <?php elseif (!$hasAnyPerformanceRecords): ?>
                    <div class="card"><p>No performance records found yet.</p></div>
                <?php else: ?>
                    <div class="grid">
                        <div class="card">
                            <h3>Fitness Statistics</h3>
                            <?php if (empty($fitnessRecords)): ?>
                                <p>No fitness records yet.</p>
                            <?php else: ?>
                                <?php foreach ($fitnessRecords as $record): ?>
                                    <p><strong><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $record['metric_name']))); ?>:</strong> <?php echo htmlspecialchars((string)$record['metric_value']); ?><br><small><?php echo htmlspecialchars(date('M j, Y', strtotime($record['record_date']))); ?><?php if (!empty($record['notes'])): ?> — <?php echo htmlspecialchars($record['notes']); ?><?php endif; ?></small></p>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="card">
                            <h3>Match Statistics</h3>
                            <?php if (empty($matchRecords)): ?>
                                <p>No match records yet.</p>
                            <?php else: ?>
                                <?php foreach ($matchRecords as $record): ?>
                                    <p><strong><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $record['metric_name']))); ?>:</strong> <?php echo htmlspecialchars((string)$record['metric_value']); ?><br><small><?php echo htmlspecialchars(date('M j, Y', strtotime($record['record_date']))); ?><?php if (!empty($record['notes'])): ?> — <?php echo htmlspecialchars($record['notes']); ?><?php endif; ?></small></p>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ── SCHEDULE ───────────────────────────────────────────────── -->
            <div id="schedule" class="page <?php echo ($activeTab === 'schedule') ? 'active' : ''; ?>">
                <h1>Schedule</h1>
                <?php if (!$athlete): ?>
                    <div class="card"><p>No athlete profile found.</p><a href="athlete_create.php"><button>Create Athlete Profile</button></a></div>
                <?php else: ?>
                    <div class="card">
                        <div class="btn-row"><a href="practice.php"><button>View Full Schedule</button></a></div>
                        <h3>Next Practice</h3>
                        <?php if (empty($upcomingPractices)): ?>
                            <p>No upcoming practices scheduled.</p>
                        <?php else: ?>
                            <?php foreach ($upcomingPractices as $practice): ?>
                                <p><strong><?php echo htmlspecialchars($practice['title']); ?></strong></p>
                                <p><?php echo htmlspecialchars($practice['start_time']); ?><?php if (!empty($practice['end_time'])): ?> – <?php echo htmlspecialchars($practice['end_time']); ?><?php endif; ?></p>
                                <p><strong>Location:</strong> <?php echo htmlspecialchars($practice['location'] ?? ''); ?></p>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="card">
                        <div class="btn-row"><a href="game_schedule.php"><button>View Full Game Schedule</button></a></div>
                        <h3>Next 3 Upcoming Games</h3>
                        <?php if (empty($upcomingGames)): ?>
                            <p>No upcoming games scheduled.</p>
                        <?php else: ?>
                            <?php foreach ($upcomingGames as $game): ?>
                                <p><strong>Opponent:</strong>  <?php echo htmlspecialchars($game['opponent']); ?></p>
                                <p><strong>Date/Time:</strong> <?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($game['game_datetime']))); ?></p>
                                <p><strong>Location:</strong>  <?php echo htmlspecialchars($game['location']); ?></p>
                                <?php if (!empty($game['notes'])): ?><p><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($game['notes'])); ?></p><?php endif; ?>
                                <hr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="card">
                        <div class="btn-row"><a href="class_schedule.php"><button>View Full Class Schedule</button></a></div>
                        <h3>Today's Classes</h3>
                        <?php if (empty($todaysClasses)): ?>
                            <p>No classes scheduled for today.</p>
                        <?php else: ?>
                            <?php foreach ($todaysClasses as $class): ?>
                                <p><strong><?php echo htmlspecialchars($class['course_name']); ?></strong></p>
                                <p>
                                    <?php echo htmlspecialchars(date('g:i A', strtotime('2000-01-01 ' . $class['start_time']))); ?>
                                    –
                                    <?php echo htmlspecialchars(date('g:i A', strtotime('2000-01-01 ' . $class['end_time']))); ?>
                                </p>
                                <?php if (!empty($class['location'])): ?>
                                    <p><strong>Location:</strong> <?php echo htmlspecialchars($class['location']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($class['notes'])): ?>
                                    <p><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($class['notes'])); ?></p>
                                <?php endif; ?>
                                <hr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ── COMMUNICATION ──────────────────────────────────────────── -->
            <div id="communication" class="page <?php echo ($activeTab === 'communication') ? 'active' : ''; ?>">
                <h1>Communication</h1>
                <?php if (!$athlete): ?>
                    <div class="card"><p>No athlete profile found.</p><a href="athlete_create.php"><button>Create Athlete Profile</button></a></div>
                <?php else: ?>
                    <div class="card">
                        <h3>Team Announcements</h3>
                        <p style="margin-top:0;color:#4b5563;font-size:13px;">From your coach.</p>
                        <?php if (empty($teamAnnouncements)): ?>
                            <p>No team announcements yet.</p>
                        <?php else: ?>
                            <?php foreach ($teamAnnouncements as $ann): ?>
                                <p><strong><?php echo htmlspecialchars($ann['sender_name']); ?></strong> <small>— <?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($ann['sent_at']))); ?></small><br><?php echo nl2br(htmlspecialchars($ann['content'])); ?></p>
                                <hr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="card card--coach-direct">
                        <div class="btn-row" style="margin-bottom:12px; flex-wrap:wrap;">
                            <a href="index.php?tab=communication&conversation=coach">
                                <button class="<?php echo ($conversation === 'coach') ? '' : 'btn--ghost'; ?>" <?php echo ($conversation === 'coach') ? 'style="opacity:1;"' : 'style="opacity:0.85;"'; ?>>Coach</button>
                            </a>
                            <a href="index.php?tab=communication&conversation=athletic_trainer">
                                <button class="<?php echo ($conversation === 'athletic_trainer') ? '' : 'btn--ghost'; ?>" <?php echo ($conversation === 'athletic_trainer') ? 'style="opacity:1;"' : 'style="opacity:0.85;"'; ?>>Athletic Trainer</button>
                            </a>
                        </div>
                        <h3><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $conversation))); ?></h3>
                        <?php if (isset($_GET['error'])): ?><p class="error"><?php echo htmlspecialchars($_GET['error']); ?></p><?php endif; ?>
                        <div class="chat-box" id="chat">
                            <?php if (empty($messages)): ?>
                                <p>No messages yet.</p>
                            <?php else: ?>
                                <?php foreach ($messages as $msg): ?>
                                    <p><strong><?php echo htmlspecialchars($msg['sender_name']); ?></strong>: <?php echo htmlspecialchars($msg['content']); ?><br><small><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($msg['sent_at']))); ?></small></p>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <form class="chat-input" method="POST" action="message_create_handler.php">
                            <input type="hidden" name="conversation" value="<?php echo htmlspecialchars($conversation); ?>">
                            <input type="text" name="content" placeholder="Type a message..." required>
                            <button type="submit">Send</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ── MEDICAL ────────────────────────────────────────────────── -->
            <div id="medical" class="page <?php echo ($activeTab === 'medical') ? 'active' : ''; ?>">
                <h1>Medical Records</h1>
                <?php if (!$athlete): ?>
                    <div class="card"><p>No athlete profile found.</p><a href="athlete_create.php"><button>Create Athlete Profile</button></a></div>
                <?php elseif (empty($medicalRecords)): ?>
                    <div class="card"><p>No medical records found yet.</p></div>
                <?php else: ?>
                    <?php foreach ($medicalRecords as $record): ?>
                        <div class="card">
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
