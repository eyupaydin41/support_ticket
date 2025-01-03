<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header("Location: login.php");
    exit;
}

try {
    $conn = new PDO("mysql:host=localhost;dbname=tickedsystem", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Bağlantı hatası: " . $e->getMessage());
}

// Silme işlemleri
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['delete_employee'])) {
        try {
            $stmt = $conn->prepare("DELETE FROM USERS WHERE user_id = ? AND role_id IN (3, 4)");
            $stmt->execute([$_POST['user_id']]);
            
            $stmt = $conn->prepare("INSERT INTO LOG (user_id, action, action_date) VALUES (?, 'Çalışan silindi', CURRENT_TIMESTAMP)");
            $stmt->execute([$_SESSION['user_id']]);
            
            header("Location: admin.php?page=employees&success=1");
            exit;
        } catch(PDOException $e) {
            $error = $e->getMessage();
        }
    }
    
    if (isset($_POST['delete_customer'])) {
        try {
            $stmt = $conn->prepare("DELETE FROM USERS WHERE user_id = ? AND role_id = 2");
            $stmt->execute([$_POST['user_id']]);
            
            $stmt = $conn->prepare("INSERT INTO LOG (user_id, action, action_date) VALUES (?, 'Müşteri silindi', CURRENT_TIMESTAMP)");
            $stmt->execute([$_SESSION['user_id']]);
            
            header("Location: admin.php?page=customers&success=1");
            exit;
        } catch(PDOException $e) {
            $error = $e->getMessage();
        }
    }
    
    // Yeni çalışan ekleme
    if (isset($_POST['add_employee'])) {
        try {
            $stmt = $conn->prepare("INSERT INTO USERS (name, email, password, role_id, department_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['name'],
                $_POST['email'],
                password_hash($_POST['password'], PASSWORD_DEFAULT),
                $_POST['role_id'],
                $_POST['department_id']
            ]);
            
            $stmt = $conn->prepare("INSERT INTO LOG (user_id, action, action_date) VALUES (?, 'Yeni çalışan eklendi', CURRENT_TIMESTAMP)");
            $stmt->execute([$_SESSION['user_id']]);
            
            header("Location: admin.php?page=employees&success=2");
            exit;
        } catch(PDOException $e) {
            $error = $e->getMessage();
        }
    }
    
    // Yeni müşteri ekleme
    if (isset($_POST['add_customer'])) {
        try {
            $stmt = $conn->prepare("INSERT INTO USERS (name, email, password, role_id, department_id) VALUES (?, ?, ?, 2, ?)");
            $stmt->execute([
                $_POST['name'],
                $_POST['email'],
                password_hash($_POST['password'], PASSWORD_DEFAULT),
                $_POST['department_id']
            ]);
            
            $stmt = $conn->prepare("INSERT INTO LOG (user_id, action, action_date) VALUES (?, 'Yeni müşteri eklendi', CURRENT_TIMESTAMP)");
            $stmt->execute([$_SESSION['user_id']]);
            
            header("Location: admin.php?page=customers&success=2");
            exit;
        } catch(PDOException $e) {
            $error = $e->getMessage();
        }
    }
}

$page = $_GET['page'] ?? 'employees';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="container">
        <nav class="sidebar">
            <ul>
                <li><a href="admin.php?page=employees">Çalışanlar</a></li>
                <li><a href="admin.php?page=customers">Müşteriler</a></li>
                <li><a href="admin.php?page=add_employee">Yeni Çalışan Ekle</a></li>
                <li><a href="admin.php?page=add_customer">Yeni Müşteri Ekle</a></li>
                <li><a href="logout.php">Çıkış Yap</a></li>
            </ul>
        </nav>
        
        <div class="content">
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="success">
                    <?php
                    switch($_GET['success']) {
                        case '1': echo "Silme işlemi başarılı!"; break;
                        case '2': echo "Ekleme işlemi başarılı!"; break;
                    }
                    ?>
                </div>
            <?php endif; ?>

            <?php
            switch($page) {
                case 'employees':
                    include 'pages/admin/employees.php';
                    break;
                case 'customers':
                    include 'pages/admin/customers.php';
                    break;
                case 'add_employee':
                    include 'pages/admin/add_employee.php';
                    break;
                case 'add_customer':
                    include 'pages/admin/add_customer.php';
                    break;
                default:
                    include 'pages/admin/employees.php';
            }
            ?>
        </div>
    </div>
</body>
</html> 