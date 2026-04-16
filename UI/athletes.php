<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require 'db.php';

$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT athlete_id, full_name, age, sport, position, jersey_number FROM athletes WHERE user_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $athlete = $result->fetch_assoc();
} else {
    $athlete = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Athlete Profile</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

<div class="content">
    <div class="topbar" style="border-radius: 12px;">
        <h1>Athlete Profile</h1>
        <div class="user">
            <?php echo htmlspecialchars($_SESSION['name']); ?> |
            <a href="index.php">Dashboard</a>
            |
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <?php if (!$athlete): ?>
        <div class="card">
            <p>No athlete profile found for your account.</p>
            <a href="athlete_create.php"><button>Create Athlete Profile</button></a>
        </div>
    <?php else: ?>
        <div class="card">
            <p><strong>Name:</strong> <?php echo htmlspecialchars($athlete['full_name']); ?></p>
            <p><strong>Age:</strong> <?php echo htmlspecialchars($athlete['age'] ?? ''); ?></p>
            <p><strong>Sport:</strong> <?php echo htmlspecialchars($athlete['sport'] ?? ''); ?></p>
            <p><strong>Position:</strong> <?php echo htmlspecialchars($athlete['position'] ?? ''); ?></p>
            <p><strong>Jersey #:</strong> <?php echo htmlspecialchars($athlete['jersey_number'] ?? ''); ?></p>

            <div style="display:flex; gap:10px; margin-top:15px;">
                <a href="athlete_edit.php?athlete_id=<?php echo urlencode($athlete['athlete_id']); ?>"><button>Edit</button></a>
                <form method="POST" action="athlete_delete.php" onsubmit="return confirm('Delete your athlete profile? This cannot be undone.');" style="margin:0;">
                    <input type="hidden" name="athlete_id" value="<?php echo htmlspecialchars($athlete['athlete_id']); ?>">
                    <button type="submit">Delete</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

</body>
</html>

