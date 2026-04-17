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

function coachRedirectGameSchedule(string $weekStart): void
{
    header("Location: coach_game_schedule.php?week_start=" . urlencode($weekStart));
    exit();
}

$gameId = isset($_POST['game_id']) ? (int)$_POST['game_id'] : 0;
$weekStartInput = trim((string)($_POST['week_start'] ?? ''));

if ($gameId <= 0) {
    $fallbackWeek = (new DateTime('monday this week'))->format('Y-m-d');
    coachRedirectGameSchedule($fallbackWeek);
}

$weekStartDate = DateTime::createFromFormat('Y-m-d', $weekStartInput);
$weekStartParam = ($weekStartDate && $weekStartDate->format('Y-m-d') === $weekStartInput)
    ? $weekStartDate->format('Y-m-d')
    : (new DateTime('monday this week'))->format('Y-m-d');

$existingStmt = $conn->prepare(
    "SELECT game_id, athlete_id
     FROM game_schedule
     WHERE game_id = ?"
);

if (!$existingStmt) {
    header("Location: coach_game_edit.php?game_id=" . urlencode((string)$gameId) . "&week_start=" . urlencode($weekStartParam) . "&error=" . urlencode("Unable to update game."));
    exit();
}

$existingStmt->bind_param("i", $gameId);
$existingStmt->execute();
$existing = $existingStmt->get_result()->fetch_assoc();

if (!$existing) {
    coachRedirectGameSchedule($weekStartParam);
}

$athleteId = (int)$existing['athlete_id'];
$athleteExistsStmt = $conn->prepare("SELECT athlete_id FROM athletes WHERE athlete_id = ? LIMIT 1");
if (!$athleteExistsStmt) {
    header("Location: coach_game_edit.php?game_id=" . urlencode((string)$gameId) . "&week_start=" . urlencode($weekStartParam) . "&error=" . urlencode("Unable to validate athlete."));
    exit();
}

$athleteExistsStmt->bind_param("i", $athleteId);
$athleteExistsStmt->execute();
$athleteExists = $athleteExistsStmt->get_result()->fetch_assoc();

if (!$athleteExists) {
    coachRedirectGameSchedule($weekStartParam);
}

$opponent = trim($_POST['opponent'] ?? '');
$gameDatetimeInput = trim($_POST['game_datetime'] ?? '');
$location = trim($_POST['location'] ?? '');
$notes = trim($_POST['notes'] ?? '');

if ($opponent === '' || $gameDatetimeInput === '' || $location === '') {
    header("Location: coach_game_edit.php?game_id=" . urlencode((string)$gameId) . "&week_start=" . urlencode($weekStartParam) . "&error=" . urlencode("Opponent, game date/time, and location are required."));
    exit();
}

$gameTimestamp = strtotime($gameDatetimeInput);
if ($gameTimestamp === false) {
    header("Location: coach_game_edit.php?game_id=" . urlencode((string)$gameId) . "&week_start=" . urlencode($weekStartParam) . "&error=" . urlencode("Invalid game date/time."));
    exit();
}

$gameDatetime = date('Y-m-d H:i:s', $gameTimestamp);

$stmt = $conn->prepare(
    "UPDATE game_schedule
     SET opponent = ?, game_datetime = ?, location = ?, notes = NULLIF(?, '')
     WHERE game_id = ? AND athlete_id = ?"
);

if (!$stmt) {
    header("Location: coach_game_edit.php?game_id=" . urlencode((string)$gameId) . "&week_start=" . urlencode($weekStartParam) . "&error=" . urlencode("Unable to update game."));
    exit();
}

$stmt->bind_param("ssssii", $opponent, $gameDatetime, $location, $notes, $gameId, $athleteId);

if ($stmt->execute()) {
    coachRedirectGameSchedule($weekStartParam);
}

header("Location: coach_game_edit.php?game_id=" . urlencode((string)$gameId) . "&week_start=" . urlencode($weekStartParam) . "&error=" . urlencode("Unable to update game."));
exit();
