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

$gameId = isset($_GET['game_id']) ? (int)$_GET['game_id'] : 0;
if ($gameId <= 0) {
    header("Location: coach_game_schedule.php");
    exit();
}

$gameStmt = $conn->prepare(
    "SELECT g.game_id, g.athlete_id, a.full_name AS athlete_name, g.opponent, g.game_datetime, g.location, g.notes
     FROM game_schedule g
     INNER JOIN athletes a ON g.athlete_id = a.athlete_id
     WHERE g.game_id = ?"
);

if (!$gameStmt) {
    die("Unable to load game.");
}

$gameStmt->bind_param("i", $gameId);
$gameStmt->execute();
$game = $gameStmt->get_result()->fetch_assoc();

if (!$game) {
    header("Location: coach_game_schedule.php");
    exit();
}

$athleteId = (int)$game['athlete_id'];
$athleteExistsStmt = $conn->prepare("SELECT athlete_id FROM athletes WHERE athlete_id = ? LIMIT 1");
if (!$athleteExistsStmt) {
    die("Unable to validate athlete.");
}

$athleteExistsStmt->bind_param("i", $athleteId);
$athleteExistsStmt->execute();
$athleteExists = $athleteExistsStmt->get_result()->fetch_assoc();

if (!$athleteExists) {
    header("Location: coach_game_schedule.php");
    exit();
}

$weekStartInput = trim((string)($_GET['week_start'] ?? ''));
$weekStartDate = DateTime::createFromFormat('Y-m-d', $weekStartInput);
if ($weekStartDate && $weekStartDate->format('Y-m-d') === $weekStartInput) {
    $weekStartParam = $weekStartDate->format('Y-m-d');
} else {
    $startDt = new DateTime($game['game_datetime']);
    if ($startDt->format('N') !== '1') {
        $startDt->modify('monday this week');
    }
    $weekStartParam = $startDt->format('Y-m-d');
}

$gameDatetimeValue = date('Y-m-d\TH:i', strtotime($game['game_datetime']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Game (Coach)</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

<div class="content">
    <div class="topbar" style="border-radius: 12px;">
        <h1>Edit Game (Coach)</h1>
        <div class="user">
            <?php echo htmlspecialchars($_SESSION['name']); ?> |
            <a href="coach_game_schedule.php?week_start=<?php echo urlencode($weekStartParam); ?>">Back</a> |
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="card">
        <?php if (isset($_GET['error'])): ?>
            <p class="error"><?php echo htmlspecialchars($_GET['error']); ?></p>
        <?php endif; ?>

        <form method="POST" action="coach_game_update_handler.php">
            <input type="hidden" name="game_id" value="<?php echo htmlspecialchars((string)$game['game_id']); ?>">
            <input type="hidden" name="week_start" value="<?php echo htmlspecialchars($weekStartParam); ?>">

            <label for="athlete_id">Athlete</label>
            <select id="athlete_id" name="athlete_id" disabled>
                <option value="<?php echo htmlspecialchars((string)$game['athlete_id']); ?>" selected>
                    <?php echo htmlspecialchars($game['athlete_name']); ?>
                </option>
            </select>

            <input type="text" name="opponent" placeholder="Opponent" required value="<?php echo htmlspecialchars($game['opponent']); ?>">
            <label for="game_datetime">Game Date/Time</label>
            <input type="datetime-local" id="game_datetime" name="game_datetime" required value="<?php echo htmlspecialchars($gameDatetimeValue); ?>">
            <input type="text" name="location" placeholder="Location" required value="<?php echo htmlspecialchars($game['location']); ?>">
            <textarea name="notes" placeholder="Notes (optional)" rows="4" style="width:100%; margin:10px 0; padding:10px; border:1px solid #ccc; border-radius:6px;"><?php echo htmlspecialchars($game['notes'] ?? ''); ?></textarea>
            <button type="submit">Update Game</button>
        </form>
    </div>
</div>

</body>
</html>
