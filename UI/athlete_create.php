<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Athlete Profile</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

<div class="content">
    <div class="topbar" style="border-radius: 12px;">
        <h1>Create Athlete Profile</h1>
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

        <form method="POST" action="athlete_create_handler.php">
            <input type="text" name="full_name" placeholder="Full Name" required>
            <input type="number" name="age" placeholder="Age" min="0">
            <input type="text" name="sport" placeholder="Sport">
            <input type="text" name="position" placeholder="Position">
            <input type="number" name="jersey_number" placeholder="Jersey Number" min="0">

            <button type="submit">Create</button>
        </form>
    </div>
</div>

</body>
</html>

