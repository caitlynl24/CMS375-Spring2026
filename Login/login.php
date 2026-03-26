<!DOCTYPE html>
<html>
    <head>
        <title>Login</title>
    </head>
    <body>

    <h2>Login</h2>

<form method="POST">
    Username: <input type="text" name="username" required><br><br>
    Password: <input type="password" name="password" required><br><br>
    <button type="submit">Login</button>
</form>

<?php
$conn = new mysqli("localhost", "root", "", "SocialMediaDB");

$message = "";
$class   = "error";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    // find the user by username
    $stmt = $conn->prepare("SELECT * FROM user_profiles WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // check the password against the hash
        if (password_verify($password, $user["password"])) {
            $message = "Login successful! Welcome, " . htmlspecialchars($user["full_name"]) . ".";
            $class   = "success";
        } else {
            $message = "Login unsuccessful. Incorrect password.";
        }
    } else {
        $message = "Login unsuccessful. User not found.";
    }

    $stmt->close();
}

$conn->close();
?>

</body>
</html>


