<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require 'db.php';

$userId = $_SESSION['user_id'];

$athleteStmt = $conn->prepare("SELECT athlete_id FROM athletes WHERE user_id = ?");
if (!$athleteStmt) {
    header("Location: performance_create.php?error=" . urlencode("Unable to load athlete profile."));
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
$category = trim($_POST['category'] ?? '');
$metricName = trim($_POST['metric_name'] ?? '');
$metricValueRaw = trim($_POST['metric_value'] ?? '');
$recordDate = trim($_POST['record_date'] ?? '');
$notes = trim($_POST['notes'] ?? '');

if ($category === '' || $metricName === '' || $metricValueRaw === '' || $recordDate === '') {
    header("Location: performance_create.php?error=" . urlencode("Category, metric, value, and date are required."));
    exit();
}

$allowedMetrics = [
    'fitness' => [
        'beep_test_score',
        'deac_test_score',
        'max_push_ups',
        'bench_press',
        'trapbar_deadlift'
    ],
    'match' => [
        'wins',
        'losses',
        'unfinished',
        'matches_2_sets',
        'matches_3_sets'
    ]
];

if (!isset($allowedMetrics[$category]) || !in_array($metricName, $allowedMetrics[$category], true)) {
    header("Location: performance_create.php?error=" . urlencode("Invalid category and metric combination."));
    exit();
}

if (!is_numeric($metricValueRaw)) {
    header("Location: performance_create.php?error=" . urlencode("Metric value must be numeric."));
    exit();
}
$metricValue = (float)$metricValueRaw;

$recordDateObj = DateTime::createFromFormat('Y-m-d', $recordDate);
if (!$recordDateObj || $recordDateObj->format('Y-m-d') !== $recordDate) {
    header("Location: performance_create.php?error=" . urlencode("Invalid record date."));
    exit();
}

$stmt = $conn->prepare(
    "INSERT INTO performance_records (athlete_id, category, metric_name, metric_value, record_date, notes)
     VALUES (?, ?, ?, ?, ?, NULLIF(?, ''))"
);

if (!$stmt) {
    header("Location: performance_create.php?error=" . urlencode("Unable to save performance record."));
    exit();
}

$stmt->bind_param("issdss", $athleteId, $category, $metricName, $metricValue, $recordDate, $notes);

if ($stmt->execute()) {
    header("Location: index.php");
    exit();
}

header("Location: performance_create.php?error=" . urlencode("Unable to save performance record."));
exit();

