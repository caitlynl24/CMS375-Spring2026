<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

<body class="auth-body">

<div class="login-container">
    <form class="login-card" method="POST" action="login_handler.php">
        <h2>Rollins Athletics</h2>

        <?php if (isset($_GET['error'])): ?>
            <p class="error">Invalid email or password</p>
        <?php endif; ?>

        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>

        <button type="submit">Login</button>

        <div class="auth-link">
            Don't have an account? <a href="register.php">Register</a>
        </div>
    </form>
</div>

</body>
</html>