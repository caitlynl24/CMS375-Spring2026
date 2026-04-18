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

$athleteId = isset($_POST['athlete_id']) ? (int)$_POST['athlete_id'] : 0;
$opponent = trim($_POST['opponent'] ?? '');
$gameDatetimeInput = trim($_POST['game_datetime'] ?? '');
$location = trim($_POST['location'] ?? '');
$notes = trim($_POST['notes'] ?? '');

if ($athleteId <= 0) {
    header("Location: coach_game_create.php?error=" . urlencode("Please select an athlete."));
    exit();
}

$verifyStmt = $conn->prepare("SELECT athlete_id FROM athletes WHERE athlete_id = ?");
if (!$verifyStmt) {
    header("Location: coach_game_create.php?error=" . urlencode("Unable to validate athlete."));
    exit();
}

$verifyStmt->bind_param("i", $athleteId);
$verifyStmt->execute();
$verified = $verifyStmt->get_result()->fetch_assoc();

if (!$verified) {
    header("Location: coach_game_create.php?error=" . urlencode("Invalid athlete selected."));
    exit();
}

if ($opponent === '' || $gameDatetimeInput === '' || $location === '') {
    header("Location: coach_game_create.php?error=" . urlencode("Opponent, game date/time, and location are required."));
    exit();
}

$gameTimestamp = strtotime($gameDatetimeInput);
if ($gameTimestamp === false) {
    header("Location: coach_game_create.php?error=" . urlencode("Invalid game date/time."));
    exit();
}

$gameDatetime = date('Y-m-d H:i:s', $gameTimestamp);

$stmt = $conn->prepare(
    "INSERT INTO game_schedule (athlete_id, opponent, game_datetime, location, notes)
     VALUES (?, ?, ?, ?, NULLIF(?, ''))"
);

if (!$stmt) {
    header("Location: coach_game_create.php?error=" . urlencode("Unable to save game."));
    exit();
}

$stmt->bind_param("issss", $athleteId, $opponent, $gameDatetime, $location, $notes);

if ($stmt->execute()) {
    header("Location: coach_dashboard.php?tab=schedule");
    exit();
}

header("Location: coach_game_create.php?error=" . urlencode("Unable to save game."));
exit();
