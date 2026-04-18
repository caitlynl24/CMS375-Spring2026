<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require 'db.php';

$userId = $_SESSION['user_id'];
$athleteId = isset($_GET['athlete_id']) ? (int)$_GET['athlete_id'] : 0;

if ($athleteId <= 0) {
    header("Location: athletes.php");
    exit();
}

$stmt = $conn->prepare("SELECT athlete_id, full_name, age, sport, position, jersey_number FROM athletes WHERE athlete_id = ? AND user_id = ?");
$stmt->bind_param("ii", $athleteId, $userId);
$stmt->execute();
$athlete = $stmt->get_result()->fetch_assoc();

if (!$athlete) {
    header("Location: athletes.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Athlete Profile</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

<div class="content">
    <div class="topbar" style="border-radius: 12px;">
        <h1>Edit Athlete Profile</h1>
        <div class="user">
            <?php echo htmlspecialchars($_SESSION['name']); ?> |
            <a href="athletes.php">Back</a>
            |
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="card">
        <?php if (isset($_GET['error'])): ?>
            <p class="error"><?php echo htmlspecialchars($_GET['error']); ?></p>
        <?php endif; ?>

        <form method="POST" action="athlete_update_handler.php">
            <input type="hidden" name="athlete_id" value="<?php echo htmlspecialchars($athlete['athlete_id']); ?>">

            <input type="text" name="full_name" placeholder="Full Name" required value="<?php echo htmlspecialchars($athlete['full_name']); ?>">
            <input type="number" name="age" placeholder="Age" min="0" value="<?php echo htmlspecialchars($athlete['age'] ?? ''); ?>">
            <input type="text" name="sport" placeholder="Sport" value="<?php echo htmlspecialchars($athlete['sport'] ?? ''); ?>">
            <input type="text" name="position" placeholder="Position" value="<?php echo htmlspecialchars($athlete['position'] ?? ''); ?>">
            <input type="number" name="jersey_number" placeholder="Jersey Number" min="0" value="<?php echo htmlspecialchars($athlete['jersey_number'] ?? ''); ?>">

            <button type="submit">Save Changes</button>
        </form>
    </div>
</div>

</body>
</html>

