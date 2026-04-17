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
$weekStartInput = trim((string)($_POST['week_start'] ?? ''));

if ($practiceId <= 0) {
    $fallbackWeek = (new DateTime('monday this week'))->format('Y-m-d');
    coachRedirectPracticeSchedule($fallbackWeek);
}

$weekStartDate = DateTime::createFromFormat('Y-m-d', $weekStartInput);
$weekStartParam = ($weekStartDate && $weekStartDate->format('Y-m-d') === $weekStartInput)
    ? $weekStartDate->format('Y-m-d')
    : (new DateTime('monday this week'))->format('Y-m-d');

$existingStmt = $conn->prepare(
    "SELECT practice_id, athlete_id
     FROM practice_schedule
     WHERE practice_id = ?"
);

if (!$existingStmt) {
    header("Location: coach_practice_edit.php?practice_id=" . urlencode((string)$practiceId) . "&week_start=" . urlencode($weekStartParam) . "&error=" . urlencode("Unable to update practice."));
    exit();
}

$existingStmt->bind_param("i", $practiceId);
$existingStmt->execute();
$existing = $existingStmt->get_result()->fetch_assoc();

if (!$existing) {
    coachRedirectPracticeSchedule($weekStartParam);
}

$athleteId = (int)$existing['athlete_id'];
$athleteExistsStmt = $conn->prepare("SELECT athlete_id FROM athletes WHERE athlete_id = ? LIMIT 1");
if (!$athleteExistsStmt) {
    header("Location: coach_practice_edit.php?practice_id=" . urlencode((string)$practiceId) . "&week_start=" . urlencode($weekStartParam) . "&error=" . urlencode("Unable to validate athlete."));
    exit();
}

$athleteExistsStmt->bind_param("i", $athleteId);
$athleteExistsStmt->execute();
$athleteExists = $athleteExistsStmt->get_result()->fetch_assoc();

if (!$athleteExists) {
    coachRedirectPracticeSchedule($weekStartParam);
}

$title = trim($_POST['title'] ?? '');
$startTimeInput = trim($_POST['start_time'] ?? '');
$endTimeInput = trim($_POST['end_time'] ?? '');
$location = trim($_POST['location'] ?? '');
$notes = trim($_POST['notes'] ?? '');

if ($title === '' || $startTimeInput === '') {
    header("Location: coach_practice_edit.php?practice_id=" . urlencode((string)$practiceId) . "&week_start=" . urlencode($weekStartParam) . "&error=" . urlencode("Title and start time are required."));
    exit();
}

$startTimestamp = strtotime($startTimeInput);
if ($startTimestamp === false) {
    header("Location: coach_practice_edit.php?practice_id=" . urlencode((string)$practiceId) . "&week_start=" . urlencode($weekStartParam) . "&error=" . urlencode("Invalid start time."));
    exit();
}

$startTime = date('Y-m-d H:i:s', $startTimestamp);
$endTime = null;

if ($endTimeInput !== '') {
    $endTimestamp = strtotime($endTimeInput);
    if ($endTimestamp === false) {
        header("Location: coach_practice_edit.php?practice_id=" . urlencode((string)$practiceId) . "&week_start=" . urlencode($weekStartParam) . "&error=" . urlencode("Invalid end time."));
        exit();
    }

    if ($endTimestamp < $startTimestamp) {
        header("Location: coach_practice_edit.php?practice_id=" . urlencode((string)$practiceId) . "&week_start=" . urlencode($weekStartParam) . "&error=" . urlencode("End time must be after start time."));
        exit();
    }

    $endTime = date('Y-m-d H:i:s', $endTimestamp);
}

if ($endTime === null) {
    $stmt = $conn->prepare(
        "UPDATE practice_schedule
         SET title = ?, start_time = ?, end_time = NULL, location = NULLIF(?, ''), notes = NULLIF(?, '')
         WHERE practice_id = ? AND athlete_id = ?"
    );

    if (!$stmt) {
        header("Location: coach_practice_edit.php?practice_id=" . urlencode((string)$practiceId) . "&week_start=" . urlencode($weekStartParam) . "&error=" . urlencode("Unable to update practice."));
        exit();
    }

    $stmt->bind_param("sssii", $title, $startTime, $location, $notes, $practiceId, $athleteId);
} else {
    $stmt = $conn->prepare(
        "UPDATE practice_schedule
         SET title = ?, start_time = ?, end_time = ?, location = NULLIF(?, ''), notes = NULLIF(?, '')
         WHERE practice_id = ? AND athlete_id = ?"
    );

    if (!$stmt) {
        header("Location: coach_practice_edit.php?practice_id=" . urlencode((string)$practiceId) . "&week_start=" . urlencode($weekStartParam) . "&error=" . urlencode("Unable to update practice."));
        exit();
    }

    $stmt->bind_param("sssssii", $title, $startTime, $endTime, $location, $notes, $practiceId, $athleteId);
}

if ($stmt->execute()) {
    coachRedirectPracticeSchedule($weekStartParam);
}

header("Location: coach_practice_edit.php?practice_id=" . urlencode((string)$practiceId) . "&week_start=" . urlencode($weekStartParam) . "&error=" . urlencode("Unable to update practice."));
exit();
