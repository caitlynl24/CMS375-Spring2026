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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Practice</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

<div class="content">
    <div class="topbar" style="border-radius: 12px;">
        <h1>Add Practice</h1>
        <div class="user">
            <?php echo htmlspecialchars($_SESSION['name']); ?> |
            <a href="practice.php">Back to Schedule</a> |
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="card">
        <?php if (isset($_GET['error'])): ?>
            <p class="error"><?php echo htmlspecialchars($_GET['error']); ?></p>
        <?php endif; ?>

        <form method="POST" action="practice_create_handler.php">
            <input type="text" name="title" placeholder="Practice Title" required>
            <label for="start_time">Start Time</label>
            <input type="datetime-local" id="start_time" name="start_time" required>
            <label for="end_time">End Time (optional)</label>
            <input type="datetime-local" id="end_time" name="end_time">
            <input type="text" name="location" placeholder="Location">
            <textarea name="notes" placeholder="Notes" rows="4" style="width:100%; margin:10px 0; padding:10px; border:1px solid #ccc; border-radius:6px;"></textarea>
            <button type="submit">Save Practice</button>
        </form>
    </div>
</div>

</body>
</html>

