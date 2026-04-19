<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AthleteHub — Login</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="auth-body">

<div class="login-container">
    <form class="login-card" method="POST" action="login_handler.php">

        <div class="brand">
            <span class="brand-name">AthleteHub</span>
            <span class="brand-sub">Rollins Athletics · Integrated Management</span>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <p class="error" style="text-align:center;">Invalid email or password.</p>
        <?php endif; ?>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="you@rollins.edu" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="••••••••" required>

        <hr class="divider">
        <button type="submit">Sign In</button>

        <div class="auth-link">
            Don't have an account? <a href="register.php">Register</a>
        </div>
    </form>
</div>

</body>
</html>
