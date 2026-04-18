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

$stmt = $conn->prepare(
    "SELECT u.name, u.email, c.sport, c.title
     FROM users u
     LEFT JOIN coaches c ON c.user_id = u.user_id
     WHERE u.user_id = ?
     LIMIT 1"
);

if (!$stmt) {
    die("Unable to load profile.");
}

$stmt->bind_param("i", $coachUserId);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

if (!$profile) {
    header("Location: coach_dashboard.php?tab=profile");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Coach Profile</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

<div class="content">
    <div class="topbar" style="border-radius: 12px;">
        <h1>Edit Coach Profile</h1>
        <div class="user">
            <?php echo htmlspecialchars($_SESSION['name']); ?> |
            <a href="coach_dashboard.php?tab=profile">Back</a> |
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="card">
        <?php if (isset($_GET['error'])): ?>
            <p class="error"><?php echo htmlspecialchars($_GET['error']); ?></p>
        <?php endif; ?>

        <form method="POST" action="coach_profile_update_handler.php">
            <input type="text" name="name" placeholder="Full name" required value="<?php echo htmlspecialchars($profile['name']); ?>">
            <input type="email" name="email" placeholder="Email" required value="<?php echo htmlspecialchars($profile['email']); ?>">
            <input type="text" name="sport" placeholder="Sport" value="<?php echo htmlspecialchars($profile['sport'] ?? ''); ?>">
            <input type="text" name="title" placeholder="Title (e.g. Head Coach)" value="<?php echo htmlspecialchars($profile['title'] ?? ''); ?>">
            <button type="submit">Save changes</button>
        </form>
    </div>
</div>

</body>
</html>
