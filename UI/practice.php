<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require 'db.php';

$userId = $_SESSION['user_id'];

$athleteStmt = $conn->prepare("SELECT athlete_id FROM athletes WHERE user_id = ?");
if (!$athleteStmt) {
    die("Unable to load athlete profile.");
}

$athleteStmt->bind_param("i", $userId);
$athleteStmt->execute();
$athlete = $athleteStmt->get_result()->fetch_assoc();

if (!$athlete) {
    header("Location: athlete_create.php");
    exit();
}

$athleteId = (int)$athlete['athlete_id'];

$practiceStmt = $conn->prepare(
    "SELECT practice_id, title, start_time, end_time, location, notes
     FROM practice_schedule
     WHERE athlete_id = ?
     ORDER BY start_time ASC"
);

if (!$practiceStmt) {
    die("Unable to load practice schedule.");
}

$practiceStmt->bind_param("i", $athleteId);
$practiceStmt->execute();
$practices = $practiceStmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Practice Schedule</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

<div class="content">
    <div class="topbar" style="border-radius: 12px;">
        <h1>Practice Schedule</h1>
        <div class="user">
            <?php echo htmlspecialchars($_SESSION['name']); ?> |
            <a href="index.php">Dashboard</a> |
            <a href="practice_create.php">Add Practice</a> |
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <?php if ($practices->num_rows === 0): ?>
        <div class="card">
            <p>No practices scheduled yet.</p>
            <a href="practice_create.php"><button>Add Your First Practice</button></a>
        </div>
    <?php else: ?>
        <?php while ($practice = $practices->fetch_assoc()): ?>
            <div class="card">
                <p><strong>Title:</strong> <?php echo htmlspecialchars($practice['title']); ?></p>
                <p><strong>Start:</strong> <?php echo htmlspecialchars($practice['start_time']); ?></p>
                <p><strong>End:</strong> <?php echo htmlspecialchars($practice['end_time'] ?? ''); ?></p>
                <p><strong>Location:</strong> <?php echo htmlspecialchars($practice['location'] ?? ''); ?></p>
                <p><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($practice['notes'] ?? '')); ?></p>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>
</div>

</body>
</html>

