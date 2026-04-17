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

require 'db.php';

$upcomingPractices = [];
$upcomingGames = [];

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
    $practicePreviewResult = $practicePreviewStmt->get_result();

    while ($row = $practicePreviewResult->fetch_assoc()) {
        $upcomingPractices[] = $row;
    }
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
    $gamesPreviewResult = $gamesPreviewStmt->get_result();

    while ($row = $gamesPreviewResult->fetch_assoc()) {
        $upcomingGames[] = $row;
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
                        <a href="coach_practice_schedule.php"><button>View Full Practice Schedule</button></a>
                        <a href="coach_practice_create.php"><button>Add Practice</button></a>
                        <a href="coach_practice_schedule.php"><button>Manage Practices</button></a>
                    </div>

                    <h3>Next Practice</h3>
                    <?php if (empty($upcomingPractices)): ?>
                        <p>No upcoming practices scheduled.</p>
                    <?php else: ?>
                        <?php foreach ($upcomingPractices as $practice): ?>
                            <p><strong><?php echo htmlspecialchars($practice['title']); ?></strong></p>
                            <p><strong>Athlete:</strong> <?php echo htmlspecialchars($practice['athlete_name'] ?? ''); ?></p>
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
                    <div style="display:flex; gap:10px; margin-bottom:15px; flex-wrap:wrap;">
                        <a href="coach_game_schedule.php"><button>View Full Game Schedule</button></a>
                        <a href="coach_game_create.php"><button>Add Game</button></a>
                        <a href="coach_game_schedule.php"><button>Manage Games</button></a>
                    </div>

                    <h3>Next 3 Upcoming Games</h3>

                    <?php if (empty($upcomingGames)): ?>
                        <p>No upcoming games scheduled.</p>
                    <?php else: ?>
                        <?php foreach ($upcomingGames as $game): ?>
                            <p><strong>Opponent:</strong> <?php echo htmlspecialchars($game['opponent']); ?></p>
                            <p><strong>Athlete:</strong> <?php echo htmlspecialchars($game['athlete_name'] ?? ''); ?></p>
                            <p><strong>Date/Time:</strong> <?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($game['game_datetime']))); ?></p>
                            <p><strong>Location:</strong> <?php echo htmlspecialchars($game['location']); ?></p>
                            <?php if (!empty($game['notes'])): ?>
                                <p><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($game['notes'])); ?></p>
                            <?php endif; ?>
                            <hr>
                        <?php endforeach; ?>
                    <?php endif; ?>
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
