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

$athleteId          = isset($_POST['athlete_id']) ? (int)$_POST['athlete_id'] : 0;
$injuryTitle        = trim($_POST['injury_title']        ?? '');
$injuryDetails      = trim($_POST['injury_details']      ?? '');
$reportedDate       = trim($_POST['reported_date']       ?? '');
$status             = trim($_POST['status']              ?? '');
$clearanceStatus    = trim($_POST['clearance_status']    ?? '');
$expectedReturnDate = trim($_POST['expected_return_date'] ?? '');
$clearedDate        = trim($_POST['cleared_date']        ?? '');
$notes              = trim($_POST['notes']               ?? '');

$allowedStatuses   = ['active', 'recovering', 'cleared'];
$allowedClearances = ['not_cleared', 'limited', 'cleared'];

function atMedRedirect(string $msg): void
{
    header("Location: at_medical_create.php?error=" . urlencode($msg));
    exit();
}

if ($athleteId <= 0)                              { atMedRedirect("Please select an athlete."); }
if ($injuryTitle === '')                          { atMedRedirect("Injury title is required."); }
if ($reportedDate === '')                         { atMedRedirect("Reported date is required."); }
if (!in_array($status, $allowedStatuses, true))   { atMedRedirect("Please select a valid status."); }
if (!in_array($clearanceStatus, $allowedClearances, true)) { atMedRedirect("Please select a valid clearance status."); }

// Validate athlete exists
$verifyStmt = $conn->prepare("SELECT athlete_id FROM athletes WHERE athlete_id = ? LIMIT 1");
if (!$verifyStmt) { atMedRedirect("Unable to validate athlete."); }
$verifyStmt->bind_param("i", $athleteId);
$verifyStmt->execute();
if (!$verifyStmt->get_result()->fetch_assoc()) { atMedRedirect("Invalid athlete selected."); }

// Validate dates
$reportedDateObj = DateTime::createFromFormat('Y-m-d', $reportedDate);
if (!$reportedDateObj || $reportedDateObj->format('Y-m-d') !== $reportedDate) { atMedRedirect("Invalid reported date."); }

$expectedReturnDateSql = null;
if ($expectedReturnDate !== '') {
    $erdObj = DateTime::createFromFormat('Y-m-d', $expectedReturnDate);
    if (!$erdObj || $erdObj->format('Y-m-d') !== $expectedReturnDate) { atMedRedirect("Invalid expected return date."); }
    $expectedReturnDateSql = $expectedReturnDate;
}

$clearedDateSql = null;
if ($clearedDate !== '') {
    $cdObj = DateTime::createFromFormat('Y-m-d', $clearedDate);
    if (!$cdObj || $cdObj->format('Y-m-d') !== $clearedDate) { atMedRedirect("Invalid cleared date."); }
    $clearedDateSql = $clearedDate;
}

$stmt = $conn->prepare(
    "INSERT INTO medical_records
        (athlete_id, injury_title, injury_details, reported_date, status, clearance_status,
         expected_return_date, cleared_date, notes)
     VALUES (?, ?, NULLIF(?, ''), ?, ?, ?, ?, ?, NULLIF(?, ''))"
);

if (!$stmt) { atMedRedirect("Unable to save medical record."); }

$stmt->bind_param(
    "issssssss",
    $athleteId,
    $injuryTitle,
    $injuryDetails,
    $reportedDate,
    $status,
    $clearanceStatus,
    $expectedReturnDateSql,
    $clearedDateSql,
    $notes
);

if ($stmt->execute()) {
    header("Location: at_dashboard.php?tab=medical");
    exit();
}

atMedRedirect("Unable to save medical record.");
