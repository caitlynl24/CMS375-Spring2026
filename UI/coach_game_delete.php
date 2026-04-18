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
$postedAthleteId = isset($_POST['athlete_id']) ? (int)$_POST['athlete_id'] : 0;
$weekStartInput = trim((string)($_POST['week_start'] ?? ''));

$weekStartDate = DateTime::createFromFormat('Y-m-d', $weekStartInput);
$weekStartParam = ($weekStartDate && $weekStartDate->format('Y-m-d') === $weekStartInput)
    ? $weekStartDate->format('Y-m-d')
    : (new DateTime('monday this week'))->format('Y-m-d');

if ($gameId <= 0 || $postedAthleteId <= 0) {
    coachRedirectGameSchedule($weekStartParam);
}

$existingStmt = $conn->prepare(
    "SELECT game_id, athlete_id
     FROM game_schedule
     WHERE game_id = ?"
);

if (!$existingStmt) {
    coachRedirectGameSchedule($weekStartParam);
}

$existingStmt->bind_param("i", $gameId);
$existingStmt->execute();
$existing = $existingStmt->get_result()->fetch_assoc();

if (!$existing) {
    coachRedirectGameSchedule($weekStartParam);
}

$athleteId = (int)$existing['athlete_id'];
if ($athleteId !== $postedAthleteId) {
    coachRedirectGameSchedule($weekStartParam);
}

$athleteExistsStmt = $conn->prepare("SELECT athlete_id FROM athletes WHERE athlete_id = ? LIMIT 1");
if (!$athleteExistsStmt) {
    coachRedirectGameSchedule($weekStartParam);
}

$athleteExistsStmt->bind_param("i", $athleteId);
$athleteExistsStmt->execute();
$athleteExists = $athleteExistsStmt->get_result()->fetch_assoc();

if (!$athleteExists) {
    coachRedirectGameSchedule($weekStartParam);
}

$deleteStmt = $conn->prepare("DELETE FROM game_schedule WHERE game_id = ? AND athlete_id = ?");
if (!$deleteStmt) {
    coachRedirectGameSchedule($weekStartParam);
}

$deleteStmt->bind_param("ii", $gameId, $athleteId);
$deleteStmt->execute();

coachRedirectGameSchedule($weekStartParam);
