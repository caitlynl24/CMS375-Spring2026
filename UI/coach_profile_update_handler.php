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
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$sport = trim($_POST['sport'] ?? '');
$title = trim($_POST['title'] ?? '');

function redirectEdit(string $msg): void
{
    header("Location: coach_profile_edit.php?error=" . urlencode($msg));
    exit();
}

if ($name === '') {
    redirectEdit("Name is required.");
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirectEdit("Please enter a valid email address.");
}

$dupStmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ? LIMIT 1");
if (!$dupStmt) {
    redirectEdit("Unable to save profile.");
}

$dupStmt->bind_param("si", $email, $coachUserId);
$dupStmt->execute();
$dupResult = $dupStmt->get_result();

if ($dupResult && $dupResult->num_rows > 0) {
    redirectEdit("That email is already in use by another account.");
}

$updateUserStmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE user_id = ?");
if (!$updateUserStmt) {
    redirectEdit("Unable to save profile.");
}

$updateUserStmt->bind_param("ssi", $name, $email, $coachUserId);

if (!$updateUserStmt->execute()) {
    if ($conn->errno === 1062) {
        redirectEdit("That email is already in use by another account.");
    }
    redirectEdit("Unable to save profile.");
}

$_SESSION['name'] = $name;

$existsStmt = $conn->prepare("SELECT coach_id FROM coaches WHERE user_id = ? LIMIT 1");
if (!$existsStmt) {
    redirectEdit("Identity saved, but coach details could not be updated. Try again.");
}

$existsStmt->bind_param("i", $coachUserId);
$existsStmt->execute();
$existsRow = $existsStmt->get_result()->fetch_assoc();

if (!$existsRow) {
    $insertStmt = $conn->prepare(
        "INSERT INTO coaches (user_id, sport, title)
         VALUES (?, NULLIF(?, ''), NULLIF(?, ''))"
    );

    if (!$insertStmt) {
        redirectEdit("Identity saved, but coach details could not be updated. Try again.");
    }

    $insertStmt->bind_param("iss", $coachUserId, $sport, $title);

    if (!$insertStmt->execute()) {
        redirectEdit("Identity saved, but coach details could not be updated. Try again.");
    }

    header("Location: coach_dashboard.php?tab=profile");
    exit();
}

$updateCoachStmt = $conn->prepare(
    "UPDATE coaches
     SET sport = NULLIF(?, ''), title = NULLIF(?, '')
     WHERE user_id = ?"
);

if (!$updateCoachStmt) {
    redirectEdit("Identity saved, but coach details could not be updated. Try again.");
}

$updateCoachStmt->bind_param("ssi", $sport, $title, $coachUserId);

if (!$updateCoachStmt->execute()) {
    redirectEdit("Identity saved, but coach details could not be updated. Try again.");
}

header("Location: coach_dashboard.php?tab=profile");
exit();
