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

$atUserId = (int)$_SESSION['user_id'];
$action = trim($_POST['action'] ?? '');

if ($action === 'coach_direct') {
    $content = trim($_POST['content'] ?? '');
    if ($content === '') {
        header("Location: at_dashboard.php?tab=communication&staff_dm=coach#staff-coach-dm");
        exit();
    }

    $coachStmt = $conn->prepare("SELECT user_id FROM users WHERE LOWER(TRIM(role)) = ? ORDER BY user_id ASC LIMIT 1");
    if (!$coachStmt) {
        header("Location: at_dashboard.php?tab=communication&staff_dm=coach&error=" . urlencode("Unable to send message.") . "#staff-coach-dm");
        exit();
    }

    $coachRole = 'coach';
    $coachStmt->bind_param("s", $coachRole);
    $coachStmt->execute();
    $coachRow = $coachStmt->get_result()->fetch_assoc();

    if (!$coachRow || empty($coachRow['user_id'])) {
        header("Location: at_dashboard.php?tab=communication&staff_dm=coach&error=" . urlencode("No coach account is available.") . "#staff-coach-dm");
        exit();
    }

    $coachUserId = (int)$coachRow['user_id'];

    $stmt = $conn->prepare(
        "INSERT INTO messages (message_type, athlete_id, sender_user_id, recipient_user_id, recipient_group, content)
         VALUES ('direct', NULL, ?, ?, NULL, ?)"
    );

    if (!$stmt) {
        header("Location: at_dashboard.php?tab=communication&staff_dm=coach#staff-coach-dm");
        exit();
    }

    $stmt->bind_param("iis", $atUserId, $coachUserId, $content);
    $stmt->execute();

    header("Location: at_dashboard.php?tab=communication&staff_dm=coach#staff-coach-dm");
    exit();
}

$athleteId = isset($_POST['athlete_id']) ? (int)$_POST['athlete_id'] : 0;
$content = trim($_POST['content'] ?? '');

if ($athleteId <= 0 || $content === '') {
    $redirect = $athleteId > 0
        ? "at_dashboard.php?tab=communication&athlete_id=" . urlencode((string)$athleteId)
        : "at_dashboard.php?tab=communication";
    header("Location: " . $redirect);
    exit();
}

$athleteStmt = $conn->prepare("SELECT user_id FROM athletes WHERE athlete_id = ? LIMIT 1");
if (!$athleteStmt) {
    header("Location: at_dashboard.php?tab=communication");
    exit();
}
$athleteStmt->bind_param("i", $athleteId);
$athleteStmt->execute();
$athleteRow = $athleteStmt->get_result()->fetch_assoc();

if (!$athleteRow || empty($athleteRow['user_id'])) {
    header("Location: at_dashboard.php?tab=communication");
    exit();
}

$athleteUserId = (int)$athleteRow['user_id'];

$stmt = $conn->prepare(
    "INSERT INTO messages (message_type, athlete_id, sender_user_id, recipient_user_id, recipient_group, content)
     VALUES ('direct', ?, ?, ?, NULL, ?)"
);

if (!$stmt) {
    header("Location: at_dashboard.php?tab=communication&athlete_id=" . urlencode((string)$athleteId));
    exit();
}

$stmt->bind_param("iiis", $athleteId, $atUserId, $athleteUserId, $content);
$stmt->execute();

header("Location: at_dashboard.php?tab=communication&athlete_id=" . urlencode((string)$athleteId));
exit();
