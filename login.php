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
    <style>
        /* Genel stil */
body {
    font-family: 'Arial', sans-serif;
    background-color: #f0f0f0;
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}

.login-container {
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: #f0f0f0;
    padding: 20px;
}

.login-box {
    background-color: #fff;
    padding: 40px;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    width: 100%;
    max-width: 400px;
}

h2 {
    text-align: center;
    font-size: 24px;
    margin-bottom: 20px;
    color: #333;
}

/* Form stil */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: bold;
    margin-bottom: 8px;
    color: #333;
}

.form-group input {
    width: 100%;
    padding: 12px;
    font-size: 16px;
    border: 1px solid #ccc;
    border-radius: 4px;
    margin-top: 6px;
    box-sizing: border-box;
    transition: border-color 0.3s ease;
}

.form-group input:focus {
    border-color: #4CAF50;
    outline: none;
}

button {
    width: 100%;
    padding: 12px;
    background-color: #4CAF50;
    color: #fff;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

button:hover {
    background-color: #45a049;
}

/* Hata mesajı */
.error {
    background-color: #f44336;
    color: white;
    padding: 10px;
    border-radius: 4px;
    margin-bottom: 20px;
    text-align: center;
}
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
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
    </div>
</body>
</html>
