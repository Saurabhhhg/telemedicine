<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username='$username'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_type'] = $user['user_type'];
            header("Location: dashboard.php");
            exit;
        } else {
            $error_message = "Incorrect password.";
        }
    } else {
        $error_message = "Username not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MedConnect</title>
    <link rel="icon" type="image/png" href="mc.png">
    <link rel="stylesheet" href="styles/login.css">
</head>

<body>
    <div class="container">
        <h2>Login</h2>
        <?php
        if (isset($error_message)) {
            echo "<div class='alert alert-danger'>$error_message</div>";
        }
        ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
        <div class="text-center">
            <small>Don't have an account? <a href="signup.php">Sign up here</a></small>
        </div>
    </div>
</body>

</html>