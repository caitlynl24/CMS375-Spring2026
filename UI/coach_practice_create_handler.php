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
$title = trim($_POST['title'] ?? '');
$startTimeInput = trim($_POST['start_time'] ?? '');
$endTimeInput = trim($_POST['end_time'] ?? '');
$location = trim($_POST['location'] ?? '');
$notes = trim($_POST['notes'] ?? '');

if ($athleteId <= 0) {
    header("Location: coach_practice_create.php?error=" . urlencode("Please select an athlete."));
    exit();
}

$verifyStmt = $conn->prepare("SELECT athlete_id FROM athletes WHERE athlete_id = ?");
if (!$verifyStmt) {
    header("Location: coach_practice_create.php?error=" . urlencode("Unable to validate athlete."));
    exit();
}

$verifyStmt->bind_param("i", $athleteId);
$verifyStmt->execute();
$verified = $verifyStmt->get_result()->fetch_assoc();

if (!$verified) {
    header("Location: coach_practice_create.php?error=" . urlencode("Invalid athlete selected."));
    exit();
}

if ($title === '' || $startTimeInput === '') {
    header("Location: coach_practice_create.php?error=" . urlencode("Title and start time are required."));
    exit();
}

$startTimestamp = strtotime($startTimeInput);
if ($startTimestamp === false) {
    header("Location: coach_practice_create.php?error=" . urlencode("Invalid start time."));
    exit();
}

$startTime = date('Y-m-d H:i:s', $startTimestamp);
$endTime = null;

if ($endTimeInput !== '') {
    $endTimestamp = strtotime($endTimeInput);
    if ($endTimestamp === false) {
        header("Location: coach_practice_create.php?error=" . urlencode("Invalid end time."));
        exit();
    }
    if ($endTimestamp < $startTimestamp) {
        header("Location: coach_practice_create.php?error=" . urlencode("End time must be after start time."));
        exit();
    }
    $endTime = date('Y-m-d H:i:s', $endTimestamp);
}

if ($endTime === null) {
    $stmt = $conn->prepare(
        "INSERT INTO practice_schedule (athlete_id, title, start_time, end_time, location, notes)
         VALUES (?, ?, ?, NULL, NULLIF(?, ''), NULLIF(?, ''))"
    );

    if (!$stmt) {
        header("Location: coach_practice_create.php?error=" . urlencode("Unable to save practice."));
        exit();
    }

    $stmt->bind_param("issss", $athleteId, $title, $startTime, $location, $notes);
} else {
    $stmt = $conn->prepare(
        "INSERT INTO practice_schedule (athlete_id, title, start_time, end_time, location, notes)
         VALUES (?, ?, ?, ?, NULLIF(?, ''), NULLIF(?, ''))"
    );

    if (!$stmt) {
        header("Location: coach_practice_create.php?error=" . urlencode("Unable to save practice."));
        exit();
    }

    $stmt->bind_param("isssss", $athleteId, $title, $startTime, $endTime, $location, $notes);
}

if ($stmt->execute()) {
    header("Location: coach_dashboard.php?tab=schedule");
    exit();
}

header("Location: coach_practice_create.php?error=" . urlencode("Unable to save practice."));
exit();
