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

function coachRedirectPracticeSchedule(string $weekStart): void
{
    header("Location: coach_practice_schedule.php?week_start=" . urlencode($weekStart));
    exit();
}

$practiceId = isset($_POST['practice_id']) ? (int)$_POST['practice_id'] : 0;
$postedAthleteId = isset($_POST['athlete_id']) ? (int)$_POST['athlete_id'] : 0;
$weekStartInput = trim((string)($_POST['week_start'] ?? ''));

$weekStartDate = DateTime::createFromFormat('Y-m-d', $weekStartInput);
$weekStartParam = ($weekStartDate && $weekStartDate->format('Y-m-d') === $weekStartInput)
    ? $weekStartDate->format('Y-m-d')
    : (new DateTime('monday this week'))->format('Y-m-d');

if ($practiceId <= 0 || $postedAthleteId <= 0) {
    coachRedirectPracticeSchedule($weekStartParam);
}

$existingStmt = $conn->prepare(
    "SELECT practice_id, athlete_id
     FROM practice_schedule
     WHERE practice_id = ?"
);

if (!$existingStmt) {
    coachRedirectPracticeSchedule($weekStartParam);
}

$existingStmt->bind_param("i", $practiceId);
$existingStmt->execute();
$existing = $existingStmt->get_result()->fetch_assoc();

if (!$existing) {
    coachRedirectPracticeSchedule($weekStartParam);
}

$athleteId = (int)$existing['athlete_id'];
if ($athleteId !== $postedAthleteId) {
    coachRedirectPracticeSchedule($weekStartParam);
}

$athleteExistsStmt = $conn->prepare("SELECT athlete_id FROM athletes WHERE athlete_id = ? LIMIT 1");
if (!$athleteExistsStmt) {
    coachRedirectPracticeSchedule($weekStartParam);
}

$athleteExistsStmt->bind_param("i", $athleteId);
$athleteExistsStmt->execute();
$athleteExists = $athleteExistsStmt->get_result()->fetch_assoc();

if (!$athleteExists) {
    coachRedirectPracticeSchedule($weekStartParam);
}

$deleteStmt = $conn->prepare("DELETE FROM practice_schedule WHERE practice_id = ? AND athlete_id = ?");
if (!$deleteStmt) {
    coachRedirectPracticeSchedule($weekStartParam);
}

$deleteStmt->bind_param("ii", $practiceId, $athleteId);
$deleteStmt->execute();

coachRedirectPracticeSchedule($weekStartParam);
