<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Coaches go to coach dashboard. athletic_trainer (and athletes) stay here until a staff dashboard exists.
$sessionRoleNorm = isset($_SESSION['role']) ? strtolower(trim((string)$_SESSION['role'])) : '';
if ($sessionRoleNorm === 'coach') {
    header("Location: coach_dashboard.php");
    exit();
}

$role = $_SESSION['role'];

require 'db.php';

$activeTab = $_GET['tab'] ?? 'profile';
$allowedTabs = ['profile', 'performance', 'schedule', 'communication', 'medical'];
if (!in_array($activeTab, $allowedTabs, true)) {
    $activeTab = 'profile';
}

// Athlete Communication: coach direct messages only (no conversation switcher).
$conversation = 'coach';

$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT athlete_id, full_name, age, sport, position FROM athletes WHERE user_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $athlete = $stmt->get_result()->fetch_assoc();
} else {
    $athlete = null;
}

$upcomingPractices = [];
$fitnessRecords = [];
$matchRecords = [];
$hasAnyPerformanceRecords = false;
$messages = [];
$teamAnnouncements = [];
$medicalRecords = [];
$games = [];
$upcomingGames = [];

if ($athlete) {
    $athleteId = (int)$athlete['athlete_id'];

    $practiceStmt = $conn->prepare(
        "SELECT title, start_time, end_time, location
         FROM practice_schedule
         WHERE athlete_id = ? AND start_time >= NOW()
         ORDER BY start_time ASC
         LIMIT 1"
    );

    if ($practiceStmt) {
        $practiceStmt->bind_param("i", $athleteId);
        $practiceStmt->execute();
        $practiceResult = $practiceStmt->get_result();

        while ($row = $practiceResult->fetch_assoc()) {
            $upcomingPractices[] = $row;
        }
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
        $performanceResult = $performanceStmt->get_result();

        while ($record = $performanceResult->fetch_assoc()) {
            $hasAnyPerformanceRecords = true;

            if ($record['category'] === 'fitness') {
                $fitnessRecords[] = $record;
            } elseif ($record['category'] === 'match') {
                $matchRecords[] = $record;
            }
        }
    }

    $peerRole = 'coach';
    $communicationPeerUserId = null;
    $peerLookupStmt = $conn->prepare("SELECT user_id FROM users WHERE LOWER(TRIM(role)) = ? ORDER BY user_id ASC LIMIT 1");
    if ($peerLookupStmt) {
        $peerLookupStmt->bind_param("s", $peerRole);
        $peerLookupStmt->execute();
        $peerLookupRow = $peerLookupStmt->get_result()->fetch_assoc();
        if ($peerLookupRow) {
            $communicationPeerUserId = (int)$peerLookupRow['user_id'];
        }
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
            $messagesStmt->bind_param(
                "iiiii",
                $athleteId,
                $userId,
                $communicationPeerUserId,
                $communicationPeerUserId,
                $userId
            );
            $messagesStmt->execute();
            $messagesResult = $messagesStmt->get_result();

            while ($msg = $messagesResult->fetch_assoc()) {
                $messages[] = $msg;
            }
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
        $teamAnnResult = $teamAnnStmt->get_result();
        while ($row = $teamAnnResult->fetch_assoc()) {
            $teamAnnouncements[] = $row;
        }
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
        $medicalResult = $medicalStmt->get_result();

        while ($row = $medicalResult->fetch_assoc()) {
            $medicalRecords[] = $row;
        }
    }

    $gamesStmt = $conn->prepare(
        "SELECT opponent, game_datetime, location, notes
         FROM game_schedule
         WHERE athlete_id = ? AND game_datetime >= NOW()
         ORDER BY game_datetime ASC
         LIMIT 3"
    );

    if ($gamesStmt) {
        $gamesStmt->bind_param("i", $athleteId);
        $gamesStmt->execute();
        $gamesResult = $gamesStmt->get_result();

        while ($row = $gamesResult->fetch_assoc()) {
            $upcomingGames[] = $row;
        }
    }
}
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

        <ul>
            <li onclick="showPage(event, 'profile')" class="<?php echo ($activeTab === 'profile') ? 'active' : ''; ?>">Profile</li>
            <li onclick="showPage(event, 'performance')" class="<?php echo ($activeTab === 'performance') ? 'active' : ''; ?>">Performance</li>
            <li onclick="showPage(event, 'schedule')" class="<?php echo ($activeTab === 'schedule') ? 'active' : ''; ?>">Schedule</li>
            <li onclick="showPage(event, 'communication')" class="<?php echo ($activeTab === 'communication') ? 'active' : ''; ?>">Communication</li>
            <li onclick="showPage(event, 'medical')" class="<?php echo ($activeTab === 'medical') ? 'active' : ''; ?>">Medical</li>
        </ul>
    </div>

    <!-- Main -->
    <div class="main">

        <!-- Topbar -->
        <div class="topbar">
            <h1>Rollins Athletics Dashboard</h1>

            <div class="user">
                Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>
                |
                <a href="logout.php">Logout</a>
            </div>
        </div>

        <!-- Content -->
        <div class="content">

            <!-- Profile -->
            <div id="profile" class="page <?php echo ($activeTab === 'profile') ? 'active' : ''; ?>">
                <h1>Profile</h1>

                <div class="card">
                    <?php if (!$athlete): ?>
                        <p>No athlete profile found for your account.</p>
                        <a href="athlete_create.php"><button>Create Athlete Profile</button></a>
                    <?php else: ?>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($athlete['full_name']); ?></p>
                        <p><strong>Age:</strong> <?php echo htmlspecialchars($athlete['age'] ?? ''); ?></p>
                        <p><strong>Sport:</strong> <?php echo htmlspecialchars($athlete['sport'] ?? ''); ?></p>
                        <p><strong>Position:</strong> <?php echo htmlspecialchars($athlete['position'] ?? ''); ?></p>
                        <a href="athletes.php"><button>Manage Athlete Profile</button></a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Performance -->
            <div id="performance" class="page <?php echo ($activeTab === 'performance') ? 'active' : ''; ?>">
                <h1>Performance Tracking</h1>
                <?php if ($athlete): ?>
                    <div class="card">
                        <a href="performance_create.php"><button>Add Performance Record</button></a>
                    </div>
                <?php endif; ?>

                <?php if (!$athlete): ?>
                    <div class="card">
                        <p>No athlete profile found for your account.</p>
                        <a href="athlete_create.php"><button>Create Athlete Profile</button></a>
                    </div>
                <?php elseif (!$hasAnyPerformanceRecords): ?>
                    <div class="card">
                        <p>No performance records found yet.</p>
                        <p>Add mock data to <code>performance_records</code> for your athlete profile to view statistics here.</p>
                    </div>
                <?php else: ?>
                    <div class="grid">
                        <div class="card">
                            <h3>Fitness Statistics</h3>
                            <?php if (empty($fitnessRecords)): ?>
                                <p>No fitness records yet.</p>
                            <?php else: ?>
                                <?php foreach ($fitnessRecords as $record): ?>
                                    <p>
                                        <strong><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $record['metric_name']))); ?>:</strong>
                                        <?php echo htmlspecialchars((string)$record['metric_value']); ?>
                                        <br>
                                        <small>
                                            <?php echo htmlspecialchars(date('M j, Y', strtotime($record['record_date']))); ?>
                                            <?php if (!empty($record['notes'])): ?>
                                                - <?php echo htmlspecialchars($record['notes']); ?>
                                            <?php endif; ?>
                                        </small>
                                    </p>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <div class="card">
                            <h3>Match Statistics</h3>
                            <?php if (empty($matchRecords)): ?>
                                <p>No match records yet.</p>
                            <?php else: ?>
                                <?php foreach ($matchRecords as $record): ?>
                                    <p>
                                        <strong><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $record['metric_name']))); ?>:</strong>
                                        <?php echo htmlspecialchars((string)$record['metric_value']); ?>
                                        <br>
                                        <small>
                                            <?php echo htmlspecialchars(date('M j, Y', strtotime($record['record_date']))); ?>
                                            <?php if (!empty($record['notes'])): ?>
                                                - <?php echo htmlspecialchars($record['notes']); ?>
                                            <?php endif; ?>
                                        </small>
                                    </p>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Schedule -->
            <div id="schedule" class="page <?php echo ($activeTab === 'schedule') ? 'active' : ''; ?>">
                <h1>Schedule</h1>

                <?php if (!$athlete): ?>
                    <div class="card">
                        <p>No athlete profile found for your account.</p>
                        <a href="athlete_create.php"><button>Create Athlete Profile</button></a>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div style="display:flex; gap:10px; margin-bottom:15px;">
                            <a href="practice.php"><button>View Full Schedule</button></a>
                        </div>

                        <h3>Next Practice</h3>
                        <?php if (empty($upcomingPractices)): ?>
                            <p>No upcoming practices scheduled.</p>
                        <?php else: ?>
                            <?php foreach ($upcomingPractices as $practice): ?>
                                <p><strong><?php echo htmlspecialchars($practice['title']); ?></strong></p>
                                <p>
                                    <?php echo htmlspecialchars($practice['start_time']); ?>
                                    <?php if (!empty($practice['end_time'])): ?>
                                        - <?php echo htmlspecialchars($practice['end_time']); ?>
                                    <?php endif; ?>
                                </p>
                                <p><strong>Location:</strong> <?php echo htmlspecialchars($practice['location'] ?? ''); ?></p>
                                <hr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="card">
                        <div style="display:flex; gap:10px; margin-bottom:15px;">
                            <a href="game_schedule.php"><button>View Full Game Schedule</button></a>
                        </div>

                        <h3>Next 3 Upcoming Games</h3>

                        <?php if (empty($upcomingGames)): ?>
                            <p>No upcoming games scheduled.</p>
                        <?php else: ?>
                            <?php foreach ($upcomingGames as $game): ?>
                                <p><strong>Opponent:</strong> <?php echo htmlspecialchars($game['opponent']); ?></p>
                                <p><strong>Date/Time:</strong> <?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($game['game_datetime']))); ?></p>
                                <p><strong>Location:</strong> <?php echo htmlspecialchars($game['location']); ?></p>
                                <?php if (!empty($game['notes'])): ?>
                                    <p><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($game['notes'])); ?></p>
                                <?php endif; ?>
                                <hr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Communication -->
            <div id="communication" class="page <?php echo ($activeTab === 'communication') ? 'active' : ''; ?>">
                <h1>Communication</h1>

                <?php if (!$athlete): ?>
                    <div class="card">
                        <p>No athlete profile found for your account.</p>
                        <a href="athlete_create.php"><button>Create Athlete Profile</button></a>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <h3>Team announcements</h3>
                        <p style="margin-top:0;color:#4b5563;font-size:13px;">From your coach (not a reply thread).</p>
                        <?php if (empty($teamAnnouncements)): ?>
                            <p>No team announcements yet.</p>
                        <?php else: ?>
                            <?php foreach ($teamAnnouncements as $ann): ?>
                                <p>
                                    <strong><?php echo htmlspecialchars($ann['sender_name']); ?></strong>
                                    <small> — <?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($ann['sent_at']))); ?></small><br>
                                    <?php echo nl2br(htmlspecialchars($ann['content'])); ?>
                                </p>
                                <hr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="card card--coach-direct">
                        <h3>Coach</h3>
                        <?php if (isset($_GET['error'])): ?>
                            <p class="error error--inline" style="margin-top:10px;"><?php echo htmlspecialchars($_GET['error']); ?></p>
                        <?php endif; ?>

                        <div class="chat-box" id="chat">
                            <?php if (empty($messages)): ?>
                                <p>No messages yet.</p>
                            <?php else: ?>
                                <?php foreach ($messages as $msg): ?>
                                    <p>
                                        <strong><?php echo htmlspecialchars($msg['sender_name']); ?></strong>
                                        <small>(Coach)</small>:
                                        <?php echo htmlspecialchars($msg['content']); ?>
                                        <br>
                                        <small><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($msg['sent_at']))); ?></small>
                                    </p>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <form class="chat-input" method="POST" action="message_create_handler.php">
                            <input type="hidden" name="conversation" value="<?php echo htmlspecialchars($conversation); ?>">
                            <input type="text" name="content" id="messageInput" placeholder="Type a message..." required>
                            <button type="submit">Send</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Medical -->
            <div id="medical" class="page <?php echo ($activeTab === 'medical') ? 'active' : ''; ?>">
                <h1>Medical Records</h1>

                <?php if (!$athlete): ?>
                    <div class="card">
                        <p>No athlete profile found for your account.</p>
                        <a href="athlete_create.php"><button>Create Athlete Profile</button></a>
                    </div>
                <?php elseif (empty($medicalRecords)): ?>
                    <div class="card">
                        <p>No medical records found yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($medicalRecords as $record): ?>
                        <div class="card">
                            <p><strong>Injury:</strong> <?php echo htmlspecialchars($record['injury_title']); ?></p>
                            <p><strong>Reported:</strong> <?php echo htmlspecialchars(date('M j, Y', strtotime($record['reported_date']))); ?></p>
                            <p><strong>Status:</strong> <?php echo htmlspecialchars(ucwords($record['status'])); ?></p>
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
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </div>

</div>

<script src="assets/js/app.js"></script>
</body>
</html>