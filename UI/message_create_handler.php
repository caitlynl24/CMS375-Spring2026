<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require 'db.php';

$userId = (int)$_SESSION['user_id'];
$content = trim($_POST['content'] ?? '');
$conversation = trim($_POST['conversation'] ?? '');

$allowedConversations = ['coach', 'athletic_trainer'];

if ($content === '' || !in_array($conversation, $allowedConversations, true)) {
    $safe = in_array($conversation, $allowedConversations, true) ? $conversation : 'coach';
    header("Location: index.php?tab=communication&conversation=" . urlencode($safe));
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

$peerRole = $conversation === 'coach' ? 'coach' : 'athletic_trainer';
$peerStmt = $conn->prepare("SELECT user_id FROM users WHERE LOWER(TRIM(role)) = ? ORDER BY user_id ASC LIMIT 1");
if (!$peerStmt) {
    header("Location: index.php?tab=communication&conversation=" . urlencode($conversation) . "&error=" . urlencode("Unable to send message."));
    exit();
}

$peerStmt->bind_param("s", $peerRole);
$peerStmt->execute();
$peerRow = $peerStmt->get_result()->fetch_assoc();

if (!$peerRow) {
    header("Location: index.php?tab=communication&conversation=" . urlencode($conversation) . "&error=" . urlencode("No staff account is available for this conversation yet."));
    exit();
}

$recipientUserId = (int)$peerRow['user_id'];

$stmt = $conn->prepare(
    "INSERT INTO messages (message_type, athlete_id, sender_user_id, recipient_user_id, recipient_group, content)
     VALUES ('direct', ?, ?, ?, NULL, ?)"
);

if (!$stmt) {
    header("Location: index.php?tab=communication&conversation=" . urlencode($conversation));
    exit();
}

$stmt->bind_param("iiis", $athleteId, $userId, $recipientUserId, $content);
$stmt->execute();

header("Location: index.php?tab=communication&conversation=" . urlencode($conversation));
exit();
