<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require 'db.php';

$userId = $_SESSION['user_id'];
$content = trim($_POST['content'] ?? '');
$recipientRole = trim($_POST['recipient_role'] ?? '');

$allowedRecipientRoles = ['coach', 'athletic_trainer'];

if ($content === '' || !in_array($recipientRole, $allowedRecipientRoles, true)) {
    header("Location: index.php");
    exit();
}

$athleteStmt = $conn->prepare("SELECT athlete_id FROM athletes WHERE user_id = ?");
if (!$athleteStmt) {
    header("Location: index.php");
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

$stmt = $conn->prepare(
    "INSERT INTO messages (athlete_id, sender_user_id, recipient_role, content)
     VALUES (?, ?, ?, ?)"
);
if (!$stmt) {
    header("Location: index.php");
    exit();
}

$stmt->bind_param("iiss", $athleteId, $userId, $recipientRole, $content);
$stmt->execute();

header("Location: index.php");
exit();

