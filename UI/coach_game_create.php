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

$athletesStmt = $conn->prepare("SELECT athlete_id, full_name FROM athletes ORDER BY full_name ASC");
if (!$athletesStmt) {
    die("Unable to load athletes.");
}

$athletesStmt->execute();
$athletesResult = $athletesStmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Game (Coach)</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

<div class="content">
    <div class="topbar" style="border-radius: 12px;">
        <h1>Add Game (Coach)</h1>
        <div class="user">
            <?php echo htmlspecialchars($_SESSION['name']); ?> |
            <a href="coach_dashboard.php?tab=schedule">Back</a> |
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="card">
        <?php if (isset($_GET['error'])): ?>
            <p class="error"><?php echo htmlspecialchars($_GET['error']); ?></p>
        <?php endif; ?>

        <?php if ($athletesResult->num_rows === 0): ?>
            <p>No athletes found. Create athlete profiles first.</p>
        <?php else: ?>
            <form method="POST" action="coach_game_create_handler.php">
                <label for="athlete_id">Athlete</label>
                <select id="athlete_id" name="athlete_id" required>
                    <option value="">Select Athlete</option>
                    <?php while ($athleteRow = $athletesResult->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars((string)$athleteRow['athlete_id']); ?>">
                            <?php echo htmlspecialchars($athleteRow['full_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <input type="text" name="opponent" placeholder="Opponent" required>
                <label for="game_datetime">Game Date/Time</label>
                <input type="datetime-local" id="game_datetime" name="game_datetime" required>
                <input type="text" name="location" placeholder="Location" required>
                <textarea name="notes" placeholder="Notes (optional)" rows="4" style="width:100%; margin:10px 0; padding:10px; border:1px solid #ccc; border-radius:6px;"></textarea>
                <button type="submit">Save Game</button>
            </form>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
