<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "TicketSystem";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Veritabanına bağlan
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Formdan gelen e-posta ve şifre
        $email = $_POST['email'];
        $password = $_POST['password'];

        // Kullanıcıyı veritabanında ara
        $stmt = $conn->prepare("SELECT * FROM USERS WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Kullanıcı bulunduysa ve şifre doğruysa
        if ($user && password_verify($password, $user['password'])) {
            // Oturum bilgilerini ayarla
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role_id'];
            $_SESSION['name'] = $user['name'];

            // Kullanıcıyı gösterge paneline yönlendir
            header("Location: dashboard.php");
            exit;
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
