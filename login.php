<?php
session_start();
if (isset($_SESSION['user_id'])) {
    switch($_SESSION['role']) {
        case 1: header("Location: admin.php"); break;
        case 2: header("Location: customer.php"); break;
        case 3: header("Location: manager.php"); break;
        case 4: header("Location: employee.php"); break;
    }
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $conn = new PDO("mysql:host=localhost;dbname=tickedsystem", "root", "");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $conn->prepare("SELECT * FROM USERS WHERE email = ?");
        $stmt->execute([$_POST['email']]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($_POST['password'], $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role_id'];
            
            switch($user['role_id']) {
                case 1: header("Location: admin.php"); break;
                case 2: header("Location: customer.php"); break;
                case 3: header("Location: manager.php"); break;
                case 4: header("Location: employee.php"); break;
            }
            exit;
        } else {
            $error = 'Geçersiz email veya şifre!';
        }
    } catch(PDOException $e) {
        $error = "Bağlantı hatası: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="login-container">
        <h2>Giriş Yap</h2>
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Şifre:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit">Giriş Yap</button>
        </form>
    </div>
</body>
</html>
