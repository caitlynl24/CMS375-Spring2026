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
$allowedTabs = ['schedule', 'communication', 'medical'];
if (!in_array($activeTab, $allowedTabs, true)) {
    $activeTab = 'schedule';
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

            <!-- Schedule -->
            <div id="schedule" class="page <?php echo ($activeTab === 'schedule') ? 'active' : ''; ?>">
                <h1>Schedule</h1>

                <div class="card">
                    <div style="display:flex; gap:10px; margin-bottom:15px; flex-wrap:wrap;">
                        <a href="coach_practice_create.php"><button>Add Practice</button></a>
                        <a href="coach_game_create.php"><button>Add Game</button></a>
                        <a href="coach_practice_schedule.php"><button>Manage Practices</button></a>
                        <a href="coach_game_schedule.php"><button>Manage Games</button></a>
                    </div>

                    <p><strong>Coach tools</strong></p>
                    <p>Create practices and games for athletes, or manage existing items in the weekly views.</p>
                </div>
            </div>

            <!-- Communication -->
            <div id="communication" class="page <?php echo ($activeTab === 'communication') ? 'active' : ''; ?>">
                <h1>Communication</h1>

                <div class="card">
                    <p><strong>Coach view (placeholder)</strong></p>
                    <p>Athlete messaging and announcements will go here.</p>
                </div>
            </div>

            <!-- Medical -->
            <div id="medical" class="page <?php echo ($activeTab === 'medical') ? 'active' : ''; ?>">
                <h1>Medical Records</h1>

                <div class="card">
                    <p><strong>Coach view (placeholder)</strong></p>
                    <p>Medical clearance and injury visibility controls will go here.</p>
                </div>
            </div>

        </div>
    </div>

</div>

<script src="assets/js/app.js"></script>
</body>
</html>
