<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AthleteHub — Register</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="auth-body">

<div class="login-container">
    <form class="login-card" method="POST" action="register_handler.php">

        <div class="brand">
            <span class="brand-name">AthleteHub</span>
            <span class="brand-sub">Rollins Athletics · Create Account</span>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <p class="error" style="text-align:center;">
                <?php
                $err = $_GET['error'];
                if ($err === 'weak_password') echo 'Password must be at least 8 characters.';
                else echo htmlspecialchars($err);
                ?>
            </p>
        <?php endif; ?>

        <label for="name">Full Name</label>
        <input type="text" id="name" name="name" placeholder="Jane Smith" required>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="you@rollins.edu" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Min. 8 characters" required>

        <label for="role">Role</label>
        <select id="role" name="role">
            <option value="athlete">Athlete</option>
            <option value="coach">Coach</option>
            <option value="athletic_trainer">Athletic Trainer</option>
        </select>

        <hr class="divider">
        <button type="submit">Create Account</button>

        <div class="auth-link">
            Already have an account? <a href="login.php">Sign In</a>
        </div>
    </form>
</div>

</body>
</html>
