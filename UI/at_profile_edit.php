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

$atUserId = (int)$_SESSION['user_id'];

$stmt = $conn->prepare(
    "SELECT u.name, u.email, at2.specialty, at2.certification
     FROM users u
     LEFT JOIN athletic_trainers at2 ON at2.user_id = u.user_id
     WHERE u.user_id = ?
     LIMIT 1"
);

if (!$stmt) {
    die("Unable to load profile.");
}

$stmt->bind_param("i", $atUserId);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

if (!$profile) {
    header("Location: at_dashboard.php?tab=profile");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Athletic Trainer Profile</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

<div class="content">
    <div class="topbar" style="border-radius: 12px;">
        <h1>Edit Profile</h1>
        <div class="user">
            <?php echo htmlspecialchars($_SESSION['name']); ?> |
            <a href="at_dashboard.php?tab=profile">Back</a> |
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="card">
        <?php if (isset($_GET['error'])): ?>
            <p class="error"><?php echo htmlspecialchars($_GET['error']); ?></p>
        <?php endif; ?>

        <form method="POST" action="at_profile_update_handler.php">
            <input type="text"  name="name"          placeholder="Full Name"                    required value="<?php echo htmlspecialchars($profile['name']); ?>">
            <input type="email" name="email"         placeholder="Email"                        required value="<?php echo htmlspecialchars($profile['email']); ?>">
            <input type="text"  name="specialty"     placeholder="Specialty (e.g. Sports Rehab)"        value="<?php echo htmlspecialchars($profile['specialty']     ?? ''); ?>">
            <input type="text"  name="certification" placeholder="Certification (e.g. ATC, CSCS)"       value="<?php echo htmlspecialchars($profile['certification'] ?? ''); ?>">
            <button type="submit">Save Changes</button>
        </form>
    </div>
</div>

</body>
</html>
