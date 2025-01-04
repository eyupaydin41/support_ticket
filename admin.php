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
            $stmt = $conn->prepare("DELETE FROM USERS WHERE user_id = ?");
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
            $stmt = $conn->prepare("INSERT INTO USERS (name, email, password, role_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $_POST['name'],
                $_POST['email'],
                password_hash($_POST['password'], PASSWORD_DEFAULT),
                $_POST['role_id'],
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
    <style>/* Genel stil */
body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
    margin: 0;
    padding: 0;
}

.container {
    display: flex;
}

.sidebar {
    width: 250px;
    background-color: #333;
    color: #fff;
    padding: 20px;
    height: 100vh;
}

.sidebar ul {
    list-style: none;
    padding: 0;
}

.sidebar ul li {
    margin: 20px 0;
}

.sidebar ul li a {
    color: #fff;
    text-decoration: none;
    font-size: 16px;
}

.sidebar ul li a:hover {
    background-color: #575757;
    padding: 10px;
    border-radius: 5px;
}

.content {
    flex-grow: 1;
    padding: 20px;
    background-color: #fff;
}

h1 {
    font-size: 24px;
    margin-bottom: 20px;
    color: #333;
}

.form-container {
    background-color: #fff;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    font-weight: bold;
    display: block;
}

.form-group input, .form-group select {
    width: 100%;
    padding: 8px;
    margin-top: 5px;
    border-radius: 4px;
    border: 1px solid #ccc;
}

button {
    padding: 10px 15px;
    background-color: #4CAF50;
    color: #fff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
}

button:hover {
    background-color: #45a049;
}

/* Success/Error mesajları */
.success {
    background-color: #4CAF50;
    color: white;
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.error {
    background-color: #f44336;
    color: white;
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.data-table th, .data-table td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.data-table th {
    background-color: #f2f2f2;
}

.data-table tr:hover {
    background-color: #f1f1f1;
}

/* Menü ve sayfa başlıkları */
nav.sidebar ul li a.active {
    background-color: #444;
}

/* Silme butonları */
button[type="submit"] {
    background-color: #f44336;
}

button[type="submit"]:hover {
    background-color: #d32f2f;
}
</style>
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