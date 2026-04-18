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

$deleteStmt = $conn->prepare("DELETE FROM practice_schedule WHERE practice_id = ? AND athlete_id = ?");
if (!$deleteStmt) {
    header("Location: practice.php");
    exit();
}

$deleteStmt->bind_param("ii", $practiceId, $athleteId);
$deleteStmt->execute();

header("Location: practice.php");
exit();

