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
    <title>Add Performance Record</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

<div class="content">
    <div class="topbar" style="border-radius: 12px;">
        <h1>Add Performance Record</h1>
        <div class="user">
            <?php echo htmlspecialchars($_SESSION['name']); ?> |
            <a href="index.php">Back to Dashboard</a> |
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="card">
        <?php if (isset($_GET['error'])): ?>
            <p class="error"><?php echo htmlspecialchars($_GET['error']); ?></p>
        <?php endif; ?>

        <form method="POST" action="performance_create_handler.php">
            <label for="category">Category</label>
            <select id="category" name="category" required>
                <option value="">Select Category</option>
                <option value="fitness">Fitness</option>
                <option value="match">Match</option>
            </select>

            <label for="metric_name">Metric</label>
            <select id="metric_name" name="metric_name" required>
                <option value="">Select Metric</option>
                <optgroup label="Fitness">
                    <option value="beep_test_score">Beep Test Score</option>
                    <option value="deac_test_score">DEAC Test Score</option>
                    <option value="max_push_ups">Max Push Ups</option>
                    <option value="bench_press">Bench Press</option>
                    <option value="trapbar_deadlift">Trapbar Deadlift</option>
                </optgroup>
                <optgroup label="Match">
                    <option value="wins">Wins</option>
                    <option value="losses">Losses</option>
                    <option value="unfinished">Unfinished</option>
                    <option value="matches_2_sets">Matches 2 Sets</option>
                    <option value="matches_3_sets">Matches 3 Sets</option>
                </optgroup>
            </select>

            <label for="metric_value">Metric Value</label>
            <input type="number" id="metric_value" name="metric_value" step="0.01" required>

            <label for="record_date">Record Date</label>
            <input type="date" id="record_date" name="record_date" required>

            <label for="notes">Notes (optional)</label>
            <textarea id="notes" name="notes" rows="4" placeholder="Notes" style="width:100%; margin:10px 0; padding:10px; border:1px solid #ccc; border-radius:6px;"></textarea>

            <button type="submit">Save Performance Record</button>
        </form>
    </div>
</div>

</body>
</html>

