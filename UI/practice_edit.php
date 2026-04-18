<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require 'db.php';

$userId = $_SESSION['user_id'];
$practiceId = isset($_GET['practice_id']) ? (int)$_GET['practice_id'] : 0;

if ($practiceId <= 0) {
    header("Location: practice.php");
    exit();
}

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
     WHERE practice_id = ? AND athlete_id = ?"
);

if (!$practiceStmt) {
    die("Unable to load practice.");
}

$practiceStmt->bind_param("ii", $practiceId, $athleteId);
$practiceStmt->execute();
$practice = $practiceStmt->get_result()->fetch_assoc();

if (!$practice) {
    header("Location: practice.php");
    exit();
}

$startTimeValue = date('Y-m-d\TH:i', strtotime($practice['start_time']));
$endTimeValue = '';
if (!empty($practice['end_time'])) {
    $endTimeValue = date('Y-m-d\TH:i', strtotime($practice['end_time']));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Practice</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

<div class="content">
    <div class="topbar" style="border-radius: 12px;">
        <h1>Edit Practice</h1>
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

        <form method="POST" action="practice_update_handler.php">
            <input type="hidden" name="practice_id" value="<?php echo htmlspecialchars((string)$practice['practice_id']); ?>">

            <input type="text" name="title" placeholder="Practice Title" required value="<?php echo htmlspecialchars($practice['title']); ?>">
            <label for="start_time">Start Time</label>
            <input type="datetime-local" id="start_time" name="start_time" required value="<?php echo htmlspecialchars($startTimeValue); ?>">
            <label for="end_time">End Time (optional)</label>
            <input type="datetime-local" id="end_time" name="end_time" value="<?php echo htmlspecialchars($endTimeValue); ?>">
            <input type="text" name="location" placeholder="Location" value="<?php echo htmlspecialchars($practice['location'] ?? ''); ?>">
            <textarea name="notes" placeholder="Notes" rows="4" style="width:100%; margin:10px 0; padding:10px; border:1px solid #ccc; border-radius:6px;"><?php echo htmlspecialchars($practice['notes'] ?? ''); ?></textarea>
            <button type="submit">Update Practice</button>
        </form>
    </div>
</div>

</body>
</html>

