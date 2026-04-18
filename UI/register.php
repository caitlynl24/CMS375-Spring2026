<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="auth-body">

<div class="login-container">
    <form class="login-card" method="POST" action="register_handler.php">
        <h2>Create Account</h2>

        <input type="text" name="name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>

        <select name="role">
            <option value="athlete">Athlete</option>
            <option value="coach">Coach</option>
            <option value="athletic_trainer">Athletic Trainer</option>
        </select>

        <button type="submit">Register</button>

        <div class="auth-link">
            Already have an account? <a href="login.php">Login</a>
        </div>
    </form>
</div>

</body>
</html>