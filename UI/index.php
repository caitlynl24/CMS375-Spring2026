<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'];
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
                Welcome, <?php echo $_SESSION['name']; ?>
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
                    <p><strong>Name:</strong> John Doe</p>
                    <p><strong>Age:</strong> 20</p>
                    <p><strong>Sport:</strong> Basketball</p>
                    <p><strong>Position:</strong> Guard</p>
                    <button>Edit Profile</button>
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

                <div class="calendar">
                    <div class="day">Mon<br><span class="practice">Practice</span></div>
                    <div class="day">Tue<br><span class="lift">Lift</span></div>
                    <div class="day">Wed<br><span class="practice">Practice</span></div>
                    <div class="day">Thu<br><span class="rest">Rest</span></div>
                    <div class="day">Fri<br><span class="game">Game</span></div>
                    <div class="day">Sat</div>
                    <div class="day">Sun</div>
                </div>
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