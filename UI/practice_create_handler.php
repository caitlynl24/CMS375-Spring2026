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
    header("Location: practice_create.php?error=" . urlencode("Unable to load athlete profile."));
    exit();
}

$athleteStmt->bind_param("i", $userId);
$athleteStmt->execute();
$athlete = $athleteStmt->get_result()->fetch_assoc();

if (!$athlete) {
    header("Location: athlete_create.php");
    exit();
}

$athleteId = (int)$athlete['athlete_id'];
$title = trim($_POST['title'] ?? '');
$startTimeInput = trim($_POST['start_time'] ?? '');
$endTimeInput = trim($_POST['end_time'] ?? '');
$location = trim($_POST['location'] ?? '');
$notes = trim($_POST['notes'] ?? '');

if ($title === '' || $startTimeInput === '') {
    header("Location: practice_create.php?error=" . urlencode("Title and start time are required."));
    exit();
}

$startTimestamp = strtotime($startTimeInput);
if ($startTimestamp === false) {
    header("Location: practice_create.php?error=" . urlencode("Invalid start time."));
    exit();
}

$startTime = date('Y-m-d H:i:s', $startTimestamp);
$endTime = null;

if ($endTimeInput !== '') {
    $endTimestamp = strtotime($endTimeInput);
    if ($endTimestamp === false) {
        header("Location: practice_create.php?error=" . urlencode("Invalid end time."));
        exit();
    }
    if ($endTimestamp < $startTimestamp) {
        header("Location: practice_create.php?error=" . urlencode("End time must be after start time."));
        exit();
    }
    $endTime = date('Y-m-d H:i:s', $endTimestamp);
}

$stmt = $conn->prepare(
    "INSERT INTO practice_schedule (athlete_id, title, start_time, end_time, location, notes)
     VALUES (?, ?, ?, ?, NULLIF(?, ''), NULLIF(?, ''))"
);

if (!$stmt) {
    header("Location: practice_create.php?error=" . urlencode("Unable to save practice."));
    exit();
}

$stmt->bind_param("isssss", $athleteId, $title, $startTime, $endTime, $location, $notes);

if ($stmt->execute()) {
    header("Location: practice.php");
    exit();
}

header("Location: practice_create.php?error=" . urlencode("Unable to save practice."));
exit();

