<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require 'db.php';

$userId = $_SESSION['user_id'];
$practiceId = isset($_POST['practice_id']) ? (int)$_POST['practice_id'] : 0;

if ($practiceId <= 0) {
    header("Location: practice.php");
    exit();
}

$athleteStmt = $conn->prepare("SELECT athlete_id FROM athletes WHERE user_id = ?");
if (!$athleteStmt) {
    header("Location: practice.php");
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
    header("Location: practice_edit.php?practice_id=" . urlencode((string)$practiceId) . "&error=" . urlencode("Title and start time are required."));
    exit();
}

$startTimestamp = strtotime($startTimeInput);
if ($startTimestamp === false) {
    header("Location: practice_edit.php?practice_id=" . urlencode((string)$practiceId) . "&error=" . urlencode("Invalid start time."));
    exit();
}

$startTime = date('Y-m-d H:i:s', $startTimestamp);
$endTime = null;

if ($endTimeInput !== '') {
    $endTimestamp = strtotime($endTimeInput);
    if ($endTimestamp === false) {
        header("Location: practice_edit.php?practice_id=" . urlencode((string)$practiceId) . "&error=" . urlencode("Invalid end time."));
        exit();
    }

    if ($endTimestamp < $startTimestamp) {
        header("Location: practice_edit.php?practice_id=" . urlencode((string)$practiceId) . "&error=" . urlencode("End time must be after start time."));
        exit();
    }

    $endTime = date('Y-m-d H:i:s', $endTimestamp);
}

$stmt = $conn->prepare(
    "UPDATE practice_schedule
     SET title = ?, start_time = ?, end_time = ?, location = NULLIF(?, ''), notes = NULLIF(?, '')
     WHERE practice_id = ? AND athlete_id = ?"
);

if (!$stmt) {
    header("Location: practice_edit.php?practice_id=" . urlencode((string)$practiceId) . "&error=" . urlencode("Unable to update practice."));
    exit();
}

$stmt->bind_param("sssssii", $title, $startTime, $endTime, $location, $notes, $practiceId, $athleteId);

if ($stmt->execute()) {
    header("Location: practice.php");
    exit();
}

header("Location: practice_edit.php?practice_id=" . urlencode((string)$practiceId) . "&error=" . urlencode("Unable to update practice."));
exit();

