<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tickedsystem";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $email = $_POST['email'];
        $password = $_POST['password'];

        

        $stmt = $conn->prepare("SELECT * FROM USERS WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);


        if ($user) {
           
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['role'] = $user['role_id'];
                $_SESSION['name'] = $user['name'];

                switch ($user['role_id']) {
                    case 1: // Admin
                        header("Location: admin.php");
                        break;
                    case 2: // Customer
                        header("Location: customer.php");
                        break;
                    case 3: // Manager
                        header("Location: manager.php");
                        break;
                    case 4: // Employee
                        header("Location: employee.php");
                        break;
                    default:
                        header("Location: login.php");
                        break;
                }
                exit;
            } else {
                $error_message = "Geçersiz e-posta veya şifre!";
            }
        } else {
            $error_message = "Geçersiz e-posta veya şifre!";
        }
    } catch (PDOException $e) {
        $error_message = "Bağlantı hatası: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Support Ticket System</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="login-container">
        <h1>Giriş Yap</h1>
        <?php if (isset($error_message)): ?>
            <p style="color: red;"><?php echo $error_message; ?></p>
        <?php endif; ?>
        <form action="login.php" method="POST">
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" required>
            </div>
            <div class="input-group">
                <label for="password">Şifre</label>
                <input type="password" name="password" id="password" required>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
    </div>
</body>
</html>
