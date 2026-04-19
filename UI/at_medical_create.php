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

$athletesStmt = $conn->prepare("SELECT athlete_id, full_name FROM athletes ORDER BY full_name ASC");
if (!$athletesStmt) { die("Unable to load athletes."); }
$athletesStmt->execute();
$athletesResult = $athletesStmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Medical Record</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

<div class="content">
    <div class="topbar" style="border-radius: 12px;">
        <h1>Add Medical Record</h1>
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

        <?php if ($athletesResult->num_rows === 0): ?>
            <p>No athlete profiles found. Athletes must create their profiles first.</p>
        <?php else: ?>
            <form method="POST" action="at_medical_create_handler.php">

                <label for="athlete_id">Athlete</label>
                <select id="athlete_id" name="athlete_id" required>
                    <option value="">Select Athlete</option>
                    <?php while ($ath = $athletesResult->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars((string)$ath['athlete_id']); ?>">
                            <?php echo htmlspecialchars($ath['full_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <input type="text" name="injury_title" placeholder="Injury Title" required>

                <label for="injury_details">Injury Details (optional)</label>
                <textarea id="injury_details" name="injury_details" rows="3"
                          placeholder="Describe the injury..."
                          style="width:100%; margin:10px 0; padding:10px; border:1px solid #ccc; border-radius:6px;"></textarea>

                <label for="reported_date">Reported Date</label>
                <input type="date" id="reported_date" name="reported_date" required>

                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="">Select Status</option>
                    <option value="active">Active</option>
                    <option value="recovering">Recovering</option>
                    <option value="cleared">Cleared</option>
                </select>

                <label for="clearance_status">Clearance Status</label>
                <select id="clearance_status" name="clearance_status" required>
                    <option value="">Select Clearance</option>
                    <option value="not_cleared">Not Cleared</option>
                    <option value="limited">Limited</option>
                    <option value="cleared">Cleared</option>
                </select>

                <label for="expected_return_date">Expected Return Date (optional)</label>
                <input type="date" id="expected_return_date" name="expected_return_date">

                <label for="cleared_date">Cleared Date (optional)</label>
                <input type="date" id="cleared_date" name="cleared_date">

                <label for="notes">Notes (optional)</label>
                <textarea id="notes" name="notes" rows="3"
                          placeholder="Additional notes..."
                          style="width:100%; margin:10px 0; padding:10px; border:1px solid #ccc; border-radius:6px;"></textarea>

                <button type="submit">Save Record</button>
            </form>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
