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

if ($athlete) {
    $practiceStmt = $conn->prepare(
        "SELECT title, start_time, end_time, location
         FROM practice_schedule
         WHERE athlete_id = ? AND start_time >= NOW()
         ORDER BY start_time ASC
         LIMIT 3"
    );

    if ($practiceStmt) {
        $athleteId = (int)$athlete['athlete_id'];
        $practiceStmt->bind_param("i", $athleteId);
        $practiceStmt->execute();
        $practiceResult = $practiceStmt->get_result();

        while ($row = $practiceResult->fetch_assoc()) {
            $upcomingPractices[] = $row;
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

                <div class="grid">

                    <div class="card">
                        <h3>Game Statistics</h3>
                        <p>Points: 18</p>
                        <p>Assists: 6</p>
                    </div>

                    <div class="card">
                        <h3>Training Metrics</h3>
                        <p>Speed: High</p>
                        <p>Endurance: Medium</p>
                    </div>

                </div>
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

                <div class="chat-box" id="chat"></div>

                <div class="chat-input">
                    <input type="text" id="messageInput" placeholder="Type a message...">
                    <button onclick="sendMessage()">Send</button>
                </div>
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