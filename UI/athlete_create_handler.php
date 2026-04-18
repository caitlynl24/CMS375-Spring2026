<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require 'db.php';

$userId = $_SESSION['user_id'];
$fullName = trim($_POST['full_name'] ?? '');
$age = trim($_POST['age'] ?? '');
$sport = trim($_POST['sport'] ?? '');
$position = trim($_POST['position'] ?? '');
$jerseyNumber = trim($_POST['jersey_number'] ?? '');

if ($fullName === '') {
    header("Location: athlete_create.php?error=" . urlencode("Full name is required."));
    exit();
}

$checkStmt = $conn->prepare("SELECT athlete_id FROM athletes WHERE user_id = ?");
$checkStmt->bind_param("i", $userId);
$checkStmt->execute();
$existing = $checkStmt->get_result()->fetch_assoc();

if ($existing) {
    header("Location: athletes.php");
    exit();
}

$stmt = $conn->prepare(
    "INSERT INTO athletes (user_id, full_name, age, sport, position, jersey_number)
     VALUES (?, ?, NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''))"
);
$stmt->bind_param("isssss", $userId, $fullName, $age, $sport, $position, $jerseyNumber);

if ($stmt->execute()) {
    header("Location: athletes.php");
    exit();
}

header("Location: athlete_create.php?error=" . urlencode("Unable to create athlete profile."));
exit();

