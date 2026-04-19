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

$medicalRecordId    = isset($_POST['medical_record_id']) ? (int)$_POST['medical_record_id'] : 0;
$injuryTitle        = trim($_POST['injury_title']         ?? '');
$injuryDetails      = trim($_POST['injury_details']       ?? '');
$reportedDate       = trim($_POST['reported_date']        ?? '');
$status             = trim($_POST['status']               ?? '');
$clearanceStatus    = trim($_POST['clearance_status']     ?? '');
$expectedReturnDate = trim($_POST['expected_return_date'] ?? '');
$clearedDate        = trim($_POST['cleared_date']         ?? '');
$notes              = trim($_POST['notes']                ?? '');

$allowedStatuses   = ['active', 'recovering', 'cleared'];
$allowedClearances = ['not_cleared', 'limited', 'cleared'];

function atMedEditRedirect(int $id, string $msg): void
{
    header("Location: at_medical_edit.php?medical_record_id=" . urlencode((string)$id) . "&error=" . urlencode($msg));
    exit();
}

if ($medicalRecordId <= 0) {
    header("Location: at_dashboard.php?tab=medical");
    exit();
}

if ($injuryTitle === '')                                    { atMedEditRedirect($medicalRecordId, "Injury title is required."); }
if ($reportedDate === '')                                   { atMedEditRedirect($medicalRecordId, "Reported date is required."); }
if (!in_array($status, $allowedStatuses, true))            { atMedEditRedirect($medicalRecordId, "Please select a valid status."); }
if (!in_array($clearanceStatus, $allowedClearances, true)) { atMedEditRedirect($medicalRecordId, "Please select a valid clearance status."); }

// Verify record exists
$verifyStmt = $conn->prepare("SELECT medical_record_id FROM medical_records WHERE medical_record_id = ? LIMIT 1");
if (!$verifyStmt) { atMedEditRedirect($medicalRecordId, "Unable to update record."); }
$verifyStmt->bind_param("i", $medicalRecordId);
$verifyStmt->execute();
if (!$verifyStmt->get_result()->fetch_assoc()) {
    header("Location: at_dashboard.php?tab=medical");
    exit();
}

// Validate dates
$reportedDateObj = DateTime::createFromFormat('Y-m-d', $reportedDate);
if (!$reportedDateObj || $reportedDateObj->format('Y-m-d') !== $reportedDate) { atMedEditRedirect($medicalRecordId, "Invalid reported date."); }

$expectedReturnDateSql = null;
if ($expectedReturnDate !== '') {
    $erdObj = DateTime::createFromFormat('Y-m-d', $expectedReturnDate);
    if (!$erdObj || $erdObj->format('Y-m-d') !== $expectedReturnDate) { atMedEditRedirect($medicalRecordId, "Invalid expected return date."); }
    $expectedReturnDateSql = $expectedReturnDate;
}

$clearedDateSql = null;
if ($clearedDate !== '') {
    $cdObj = DateTime::createFromFormat('Y-m-d', $clearedDate);
    if (!$cdObj || $cdObj->format('Y-m-d') !== $clearedDate) { atMedEditRedirect($medicalRecordId, "Invalid cleared date."); }
    $clearedDateSql = $clearedDate;
}

$stmt = $conn->prepare(
    "UPDATE medical_records
     SET injury_title        = ?,
         injury_details      = NULLIF(?, ''),
         reported_date       = ?,
         status              = ?,
         clearance_status    = ?,
         expected_return_date = ?,
         cleared_date        = ?,
         notes               = NULLIF(?, '')
     WHERE medical_record_id = ?"
);

if (!$stmt) { atMedEditRedirect($medicalRecordId, "Unable to update record."); }

$stmt->bind_param(
    "ssssssssi",
    $injuryTitle,
    $injuryDetails,
    $reportedDate,
    $status,
    $clearanceStatus,
    $expectedReturnDateSql,
    $clearedDateSql,
    $notes,
    $medicalRecordId
);

if ($stmt->execute()) {
    header("Location: at_dashboard.php?tab=medical");
    exit();
}

atMedEditRedirect($medicalRecordId, "Unable to update record.");
