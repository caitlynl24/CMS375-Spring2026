<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require 'db.php';

$userId = $_SESSION['user_id'];
$athleteId = isset($_POST['athlete_id']) ? (int)$_POST['athlete_id'] : 0;

if ($athleteId <= 0) {
    header("Location: athletes.php");
    exit();
}

$stmt = $conn->prepare("DELETE FROM athletes WHERE athlete_id = ? AND user_id = ?");
$stmt->bind_param("ii", $athleteId, $userId);
$stmt->execute();

header("Location: athletes.php");
exit();

