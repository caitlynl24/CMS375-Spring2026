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

$medicalRecordId = isset($_GET['medical_record_id']) ? (int)$_GET['medical_record_id'] : 0;
if ($medicalRecordId <= 0) {
    header("Location: at_dashboard.php?tab=medical");
    exit();
}

$stmt = $conn->prepare(
    "SELECT m.medical_record_id, m.athlete_id, a.full_name AS athlete_name,
            m.injury_title, m.injury_details, m.reported_date, m.status,
            m.clearance_status, m.expected_return_date, m.cleared_date, m.notes
     FROM medical_records m
     INNER JOIN athletes a ON m.athlete_id = a.athlete_id
     WHERE m.medical_record_id = ?
     LIMIT 1"
);
if (!$stmt) {
    header("Location: at_dashboard.php?tab=medical");
    exit();
}

$stmt->bind_param("i", $medicalRecordId);
$stmt->execute();
$record = $stmt->get_result()->fetch_assoc();

if (!$record) {
    header("Location: at_dashboard.php?tab=medical");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Medical Record</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

<div class="content">
    <div class="topbar" style="border-radius: 12px;">
        <h1>Edit Medical Record</h1>
        <div class="user">
            <?php echo htmlspecialchars($_SESSION['name']); ?> |
            <a href="at_dashboard.php?tab=medical">Back</a> |
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="card">
        <?php if (isset($_GET['error'])): ?>
            <p class="error"><?php echo htmlspecialchars($_GET['error']); ?></p>
        <?php endif; ?>

        <form method="POST" action="at_medical_update_handler.php">
            <input type="hidden" name="medical_record_id" value="<?php echo htmlspecialchars((string)$record['medical_record_id']); ?>">

            <label>Athlete</label>
            <input type="text" value="<?php echo htmlspecialchars($record['athlete_name']); ?>" disabled>

            <input type="text" name="injury_title" placeholder="Injury Title" required
                   value="<?php echo htmlspecialchars($record['injury_title']); ?>">

            <label for="injury_details">Injury Details (optional)</label>
            <textarea id="injury_details" name="injury_details" rows="3"
                      placeholder="Describe the injury..."
                      style="width:100%; margin:10px 0; padding:10px; border:1px solid #ccc; border-radius:6px;"><?php echo htmlspecialchars($record['injury_details'] ?? ''); ?></textarea>

            <label for="reported_date">Reported Date</label>
            <input type="date" id="reported_date" name="reported_date" required
                   value="<?php echo htmlspecialchars($record['reported_date']); ?>">

            <label for="status">Status</label>
            <select id="status" name="status" required>
                <option value="active"     <?php echo ($record['status'] === 'active')     ? 'selected' : ''; ?>>Active</option>
                <option value="recovering" <?php echo ($record['status'] === 'recovering') ? 'selected' : ''; ?>>Recovering</option>
                <option value="cleared"    <?php echo ($record['status'] === 'cleared')    ? 'selected' : ''; ?>>Cleared</option>
            </select>

            <label for="clearance_status">Clearance Status</label>
            <select id="clearance_status" name="clearance_status" required>
                <option value="not_cleared" <?php echo ($record['clearance_status'] === 'not_cleared') ? 'selected' : ''; ?>>Not Cleared</option>
                <option value="limited"     <?php echo ($record['clearance_status'] === 'limited')     ? 'selected' : ''; ?>>Limited</option>
                <option value="cleared"     <?php echo ($record['clearance_status'] === 'cleared')     ? 'selected' : ''; ?>>Cleared</option>
            </select>

            <label for="expected_return_date">Expected Return Date (optional)</label>
            <input type="date" id="expected_return_date" name="expected_return_date"
                   value="<?php echo htmlspecialchars($record['expected_return_date'] ?? ''); ?>">

            <label for="cleared_date">Cleared Date (optional)</label>
            <input type="date" id="cleared_date" name="cleared_date"
                   value="<?php echo htmlspecialchars($record['cleared_date'] ?? ''); ?>">

            <label for="notes">Notes (optional)</label>
            <textarea id="notes" name="notes" rows="3"
                      placeholder="Additional notes..."
                      style="width:100%; margin:10px 0; padding:10px; border:1px solid #ccc; border-radius:6px;"><?php echo htmlspecialchars($record['notes'] ?? ''); ?></textarea>

            <button type="submit">Save Changes</button>
        </form>
    </div>
</div>

</body>
</html>
