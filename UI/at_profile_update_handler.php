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

$atUserId     = (int)$_SESSION['user_id'];
$name          = trim($_POST['name']          ?? '');
$email         = trim($_POST['email']         ?? '');
$specialty     = trim($_POST['specialty']     ?? '');
$certification = trim($_POST['certification'] ?? '');

function atRedirectEdit(string $msg): void
{
    header("Location: at_profile_edit.php?error=" . urlencode($msg));
    exit();
}

if ($name === '') {
    atRedirectEdit("Name is required.");
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    atRedirectEdit("Please enter a valid email address.");
}

$dupStmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ? LIMIT 1");
if (!$dupStmt) { atRedirectEdit("Unable to save profile."); }
$dupStmt->bind_param("si", $email, $atUserId);
$dupStmt->execute();
if ($dupStmt->get_result()->num_rows > 0) {
    atRedirectEdit("That email is already in use by another account.");
}

$updateUserStmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE user_id = ?");
if (!$updateUserStmt) { atRedirectEdit("Unable to save profile."); }
$updateUserStmt->bind_param("ssi", $name, $email, $atUserId);
if (!$updateUserStmt->execute()) {
    if ($conn->errno === 1062) { atRedirectEdit("That email is already in use by another account."); }
    atRedirectEdit("Unable to save profile.");
}

$_SESSION['name'] = $name;

$existsStmt = $conn->prepare("SELECT at_id FROM athletic_trainers WHERE user_id = ? LIMIT 1");
if (!$existsStmt) { atRedirectEdit("Identity saved, but trainer details could not be updated. Try again."); }
$existsStmt->bind_param("i", $atUserId);
$existsStmt->execute();
$existsRow = $existsStmt->get_result()->fetch_assoc();

if (!$existsRow) {
    $insertStmt = $conn->prepare(
        "INSERT INTO athletic_trainers (user_id, specialty, certification)
         VALUES (?, NULLIF(?, ''), NULLIF(?, ''))"
    );
    if (!$insertStmt) { atRedirectEdit("Identity saved, but trainer details could not be updated. Try again."); }
    $insertStmt->bind_param("iss", $atUserId, $specialty, $certification);
    if (!$insertStmt->execute()) { atRedirectEdit("Identity saved, but trainer details could not be updated. Try again."); }
    header("Location: at_dashboard.php?tab=profile");
    exit();
}

$updateAtStmt = $conn->prepare(
    "UPDATE athletic_trainers
     SET specialty = NULLIF(?, ''), certification = NULLIF(?, '')
     WHERE user_id = ?"
);
if (!$updateAtStmt) { atRedirectEdit("Identity saved, but trainer details could not be updated. Try again."); }
$updateAtStmt->bind_param("ssi", $specialty, $certification, $atUserId);
if (!$updateAtStmt->execute()) { atRedirectEdit("Identity saved, but trainer details could not be updated. Try again."); }

header("Location: at_dashboard.php?tab=profile");
exit();
