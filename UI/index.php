<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'];

require 'db.php';

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

if ($athlete) {
    $athleteId = (int)$athlete['athlete_id'];

    $practiceStmt = $conn->prepare(
        "SELECT title, start_time, end_time, location
         FROM practice_schedule
         WHERE athlete_id = ? AND start_time >= NOW()
         ORDER BY start_time ASC
         LIMIT 3"
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

    $messagesStmt = $conn->prepare(
        "SELECT u.name AS sender_name, m.recipient_role, m.content, m.sent_at
         FROM messages m
         INNER JOIN users u ON m.sender_user_id = u.user_id
         WHERE m.athlete_id = ?
         ORDER BY sent_at ASC"
    );

    if ($messagesStmt) {
        $messagesStmt->bind_param("i", $athleteId);
        $messagesStmt->execute();
        $messagesResult = $messagesStmt->get_result();

        while ($msg = $messagesResult->fetch_assoc()) {
            $messages[] = $msg;
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

        <select id="roleSelect" onchange="switchRole()">
            <option value="athlete">Athlete</option>
            <option value="coach">Coach</option>
        </select>

        <ul>
            <li onclick="showPage(event, 'profile')" class="active">Profile</li>
            <li onclick="showPage(event, 'performance')">Performance</li>
            <li onclick="showPage(event, 'schedule')">Schedule</li>
            <li onclick="showPage(event, 'communication')">Communication</li>
            <li onclick="showPage(event, 'medical')">Medical</li>
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
            <div id="profile" class="page active">
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
            <div id="performance" class="page">
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
            <div id="schedule" class="page">
                <h1>Schedule</h1>

                <?php if (!$athlete): ?>
                    <div class="card">
                        <p>No athlete profile found for your account.</p>
                        <a href="athlete_create.php"><button>Create Athlete Profile</button></a>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div style="display:flex; gap:10px; margin-bottom:15px;">
                            <a href="practice_create.php"><button>Add Practice</button></a>
                            <a href="practice.php"><button>View Full Schedule</button></a>
                        </div>

                        <h3>Next 3 Upcoming Practices</h3>
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
                <?php endif; ?>
            </div>

            <!-- Communication -->
            <div id="communication" class="page">
                <h1>Communication</h1>

                <?php if (!$athlete): ?>
                    <div class="card">
                        <p>No athlete profile found for your account.</p>
                        <a href="athlete_create.php"><button>Create Athlete Profile</button></a>
                    </div>
                <?php else: ?>
                    <div class="chat-box" id="chat">
                        <?php if (empty($messages)): ?>
                            <p>No messages yet.</p>
                        <?php else: ?>
                            <?php foreach ($messages as $msg): ?>
                                <p>
                                    <strong><?php echo htmlspecialchars($msg['sender_name']); ?></strong>
                                    <small>(to <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $msg['recipient_role']))); ?>)</small>:
                                    <?php echo htmlspecialchars($msg['content']); ?>
                                    <br>
                                    <small><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($msg['sent_at']))); ?></small>
                                </p>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <form class="chat-input" method="POST" action="message_create_handler.php">
                        <select name="recipient_role" required>
                            <option value="coach">Coach</option>
                            <option value="athletic_trainer">Athletic Trainer</option>
                        </select>
                        <input type="text" name="content" id="messageInput" placeholder="Type a message..." required>
                        <button type="submit">Send</button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Medical -->
            <div id="medical" class="page">
                <h1>Medical Records</h1>

                <div class="card">
                    <p><strong>Injury:</strong> Ankle Sprain</p>
                    <p class="status recovering">Status: Recovering</p>

                    <div id="coachRestriction" class="hidden">
                        <p>Access Restricted: Request permission from athlete.</p>
                        <button>Request Access</button>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>

<script src="assets/js/app.js"></script>
</body>
</html>