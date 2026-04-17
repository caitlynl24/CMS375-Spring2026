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

header("Location: coach_dashboard.php?tab=communication");
exit();
