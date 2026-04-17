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

require 'db.php';

$practiceId = isset($_GET['practice_id']) ? (int)$_GET['practice_id'] : 0;
if ($practiceId <= 0) {
    header("Location: coach_practice_schedule.php");
    exit();
}

$practiceStmt = $conn->prepare(
    "SELECT practice_id, athlete_id, title, start_time, end_time, location, notes
     FROM practice_schedule
     WHERE practice_id = ?"
);

if (!$practiceStmt) {
    die("Unable to load practice.");
}

$practiceStmt->bind_param("i", $practiceId);
$practiceStmt->execute();
$practice = $practiceStmt->get_result()->fetch_assoc();

if (!$practice) {
    header("Location: coach_practice_schedule.php");
    exit();
}

$athleteId = (int)$practice['athlete_id'];
$athleteExistsStmt = $conn->prepare("SELECT athlete_id FROM athletes WHERE athlete_id = ? LIMIT 1");
if (!$athleteExistsStmt) {
    die("Unable to validate athlete.");
}

$athleteExistsStmt->bind_param("i", $athleteId);
$athleteExistsStmt->execute();
$athleteExists = $athleteExistsStmt->get_result()->fetch_assoc();

if (!$athleteExists) {
    header("Location: coach_practice_schedule.php");
    exit();
}

$weekStartInput = trim((string)($_GET['week_start'] ?? ''));
$weekStartDate = DateTime::createFromFormat('Y-m-d', $weekStartInput);
if ($weekStartDate && $weekStartDate->format('Y-m-d') === $weekStartInput) {
    $weekStartParam = $weekStartDate->format('Y-m-d');
} else {
    $startDt = new DateTime($practice['start_time']);
    if ($startDt->format('N') !== '1') {
        $startDt->modify('monday this week');
    }
    $weekStartParam = $startDt->format('Y-m-d');
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
    <title>Edit Practice (Coach)</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

<div class="content">
    <div class="topbar" style="border-radius: 12px;">
        <h1>Edit Practice (Coach)</h1>
        <div class="user">
            <?php echo htmlspecialchars($_SESSION['name']); ?> |
            <a href="coach_practice_schedule.php?week_start=<?php echo urlencode($weekStartParam); ?>">Back</a> |
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="card">
        <?php if (isset($_GET['error'])): ?>
            <p class="error"><?php echo htmlspecialchars($_GET['error']); ?></p>
        <?php endif; ?>

        <form method="POST" action="coach_practice_update_handler.php">
            <input type="hidden" name="practice_id" value="<?php echo htmlspecialchars((string)$practice['practice_id']); ?>">
            <input type="hidden" name="week_start" value="<?php echo htmlspecialchars($weekStartParam); ?>">

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
