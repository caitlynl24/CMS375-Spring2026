<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$role = isset($_SESSION['role']) ? strtolower(trim((string)$_SESSION['role'])) : '';
if ($role !== 'coach') {
    header("Location: index.php");
    exit();
}

require 'db.php';

$coachUserId = (int)$_SESSION['user_id'];
$action = trim($_POST['action'] ?? '');

if ($action === 'announcement') {
    $content = trim($_POST['content'] ?? '');
    if ($content === '') {
        header("Location: coach_dashboard.php?tab=communication");
        exit();
    }

    $stmt = $conn->prepare(
        "INSERT INTO messages (message_type, athlete_id, sender_user_id, recipient_user_id, recipient_group, content)
         VALUES ('announcement', NULL, ?, NULL, 'team', ?)"
    );

    if (!$stmt) {
        header("Location: coach_dashboard.php?tab=communication");
        exit();
    }

    $stmt->bind_param("is", $coachUserId, $content);
    $stmt->execute();

    header("Location: coach_dashboard.php?tab=communication");
    exit();
}

if ($action === 'direct') {
    $athleteId = isset($_POST['athlete_id']) ? (int)$_POST['athlete_id'] : 0;
    $content = trim($_POST['content'] ?? '');

    if ($athleteId <= 0 || $content === '') {
        $redirect = $athleteId > 0
            ? "coach_dashboard.php?tab=communication&athlete_id=" . urlencode((string)$athleteId)
            : "coach_dashboard.php?tab=communication";
        header("Location: " . $redirect);
        exit();
    }

    $athleteStmt = $conn->prepare("SELECT user_id FROM athletes WHERE athlete_id = ? LIMIT 1");
    if (!$athleteStmt) {
        header("Location: coach_dashboard.php?tab=communication");
        exit();
    }

    $athleteStmt->bind_param("i", $athleteId);
    $athleteStmt->execute();
    $athleteRow = $athleteStmt->get_result()->fetch_assoc();

    if (!$athleteRow || empty($athleteRow['user_id'])) {
        header("Location: coach_dashboard.php?tab=communication");
        exit();
    }

    $athleteUserId = (int)$athleteRow['user_id'];

    $stmt = $conn->prepare(
        "INSERT INTO messages (message_type, athlete_id, sender_user_id, recipient_user_id, recipient_group, content)
         VALUES ('direct', ?, ?, ?, NULL, ?)"
    );

    if (!$stmt) {
        header("Location: coach_dashboard.php?tab=communication&athlete_id=" . urlencode((string)$athleteId));
        exit();
    }

    $stmt->bind_param("iiis", $athleteId, $coachUserId, $athleteUserId, $content);
    $stmt->execute();

    header("Location: coach_dashboard.php?tab=communication&athlete_id=" . urlencode((string)$athleteId));
    exit();
}

if ($action === 'trainer_direct') {
    $content = trim($_POST['content'] ?? '');
    if ($content === '') {
        header("Location: coach_dashboard.php?tab=communication&staff_dm=trainer#staff-trainer-dm");
        exit();
    }

    $trainerStmt = $conn->prepare("SELECT user_id FROM users WHERE LOWER(TRIM(role)) = ? ORDER BY user_id ASC LIMIT 1");
    if (!$trainerStmt) {
        header("Location: coach_dashboard.php?tab=communication&staff_dm=trainer&error=" . urlencode("Unable to send message.") . "#staff-trainer-dm");
        exit();
    }

    $trainerRole = 'athletic_trainer';
    $trainerStmt->bind_param("s", $trainerRole);
    $trainerStmt->execute();
    $trainerRow = $trainerStmt->get_result()->fetch_assoc();

    if (!$trainerRow || empty($trainerRow['user_id'])) {
        header("Location: coach_dashboard.php?tab=communication&staff_dm=trainer&error=" . urlencode("No athletic trainer account is available.") . "#staff-trainer-dm");
        exit();
    }

    $trainerUserId = (int)$trainerRow['user_id'];

    $stmt = $conn->prepare(
        "INSERT INTO messages (message_type, athlete_id, sender_user_id, recipient_user_id, recipient_group, content)
         VALUES ('direct', NULL, ?, ?, NULL, ?)"
    );

    if (!$stmt) {
        header("Location: coach_dashboard.php?tab=communication&staff_dm=trainer#staff-trainer-dm");
        exit();
    }

    $stmt->bind_param("iis", $coachUserId, $trainerUserId, $content);
    $stmt->execute();

    header("Location: coach_dashboard.php?tab=communication&staff_dm=trainer#staff-trainer-dm");
    exit();
}

header("Location: coach_dashboard.php?tab=communication");
exit();
