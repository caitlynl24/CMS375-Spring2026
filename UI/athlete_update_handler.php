<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require 'db.php';

$userId = $_SESSION['user_id'];
$athleteId = isset($_POST['athlete_id']) ? (int)$_POST['athlete_id'] : 0;

$fullName = trim($_POST['full_name'] ?? '');
$age = trim($_POST['age'] ?? '');
$sport = trim($_POST['sport'] ?? '');
$position = trim($_POST['position'] ?? '');
$jerseyNumber = trim($_POST['jersey_number'] ?? '');

if ($athleteId <= 0) {
    header("Location: athletes.php");
    exit();
}

if ($fullName === '') {
    header("Location: athlete_edit.php?athlete_id=" . urlencode((string)$athleteId) . "&error=" . urlencode("Full name is required."));
    exit();
}

$stmt = $conn->prepare(
    "UPDATE athletes
     SET full_name = ?, age = NULLIF(?, ''), sport = NULLIF(?, ''), position = NULLIF(?, ''), jersey_number = NULLIF(?, '')
     WHERE athlete_id = ? AND user_id = ?"
);
$stmt->bind_param("sssssii", $fullName, $age, $sport, $position, $jerseyNumber, $athleteId, $userId);

if ($stmt->execute()) {
    header("Location: athletes.php");
    exit();
}

header("Location: athlete_edit.php?athlete_id=" . urlencode((string)$athleteId) . "&error=" . urlencode("Unable to save changes."));
exit();

