<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$role = isset($_SESSION['role']) ? strtolower(trim((string)$_SESSION['role'])) : '';
if ($role !== 'athletic_trainer') {
    header("Location: index.php");
    exit();
}

require 'db.php';

$medicalRecordId = isset($_POST['medical_record_id']) ? (int)$_POST['medical_record_id'] : 0;

if ($medicalRecordId <= 0) {
    header("Location: at_dashboard.php?tab=medical");
    exit();
}

$deleteStmt = $conn->prepare("DELETE FROM medical_records WHERE medical_record_id = ?");
if (!$deleteStmt) {
    header("Location: at_dashboard.php?tab=medical");
    exit();
}

$deleteStmt->bind_param("i", $medicalRecordId);
$deleteStmt->execute();

header("Location: at_dashboard.php?tab=medical");
exit();
