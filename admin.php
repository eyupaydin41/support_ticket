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

    // Form gönderildiğinde güncelleme işlemi yap
    if (isset($_POST['edit_customer'])) {
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $department_id = $_POST['department_id'];
        $user_id = $_POST['user_id'];
        $password = $_POST['password'];

        // Şifre var mı kontrolü
        if (!empty($password)) {
            // Şifre hash'leme işlemi
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE USERS SET name = ?, email = ?, department_id = ?, password = ? WHERE user_id = ?");
            $stmt->execute([$name, $email, $department_id, $hashed_password, $user_id]);
        } else {
            // Şifre boş bırakılmışsa, sadece diğer bilgileri güncelle
            $stmt = $conn->prepare("UPDATE USERS SET name = ?, email = ?, department_id = ? WHERE user_id = ?");
            $stmt->execute([$name, $email, $department_id, $user_id]);
        }

        header("Location: admin.php?page=customers&success=3");
        exit;
    } catch (PDOException $e) {
        $error = $e->getMessage();  
    }
}
    }

    if (isset($_POST['edit_employee'])) {
// Form gönderildiğinde işlemleri yap
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $role_id = $_POST['role_id'];
        $user_id = $_POST['user_id'];

        // Şifre alanı boş değilse, yeni şifreyi hashle
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE USERS SET name = ?, email = ?, role_id = ?, password = ? WHERE user_id = ?");
            $stmt->execute([$name, $email, $role_id, $password, $user_id]);
        } else {
            // Şifreyi değiştirme
            $stmt = $conn->prepare("UPDATE USERS SET name = ?, email = ?, role_id = ? WHERE user_id = ?");
            $stmt->execute([$name, $email, $role_id, $user_id]);
        }

        // Log kaydı
        $stmt = $conn->prepare("INSERT INTO LOG (user_id, action, action_date) VALUES (?, 'Çalışan bilgileri güncellendi', CURRENT_TIMESTAMP)");
        $stmt->execute([$_SESSION['user_id']]);

        header("Location: admin.php?page=employees&success=3");
        exit;
    } catch (PDOException $e) {
        $error = $e->getMessage();
    }
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
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f8f9fa;
    margin: 0;
    padding: 0;
    display: flex;
    min-height: 100vh;
}

.container {
    display: flex;
    width: 100%;
    flex-grow: 1;
}

/* Sidebar */
.sidebar {
    width: 250px;
    background-color: #333;
    color: #fff;
    padding: 20px;
    height: 100vh;
}

.sidebar {
    width: 250px;
    background-color: #343a40;
    color: #fff;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    padding: 30px 20px;
    box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
}

.sidebar ul {
    list-style: none;
    padding: 0;
}

.sidebar ul li {
    margin-bottom: 25px;
}

.sidebar ul li a {
    color: #fff;
    text-decoration: none;
    font-size: 18px;
    display: block;
    transition: color 0.3s ease;
}

.sidebar ul li a:hover {
    color: #007bff;
}

/* Content */
.content {
    margin-left: 270px;
    padding: 30px 40px;
    background-color: #fff;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    flex-grow: 1;
    overflow-y: auto;
}

h1 {
    font-size: 24px;
    margin-bottom: 20px;
    color: #333;
}

/* Formlar */
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

.button-red {
    padding: 10px 15px;
    background-color:rgb(235, 49, 49);
    color: #fff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
}

.button-red:hover {
    background-color: rgb(177, 22, 22);
}


/* Tablolar */
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

/* Mesajlar */
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

/* log.php için özel CSS */

/* Genel container */
.log-container {
    display: flex;
    min-height: 100vh;
    background-color: #f4f4f9;
}

/* İçerik kısmı */
.log-content {
    flex: 1;
    padding: 20px;
    font-family: 'Arial', sans-serif;
}

.log-content h1 {
    font-size: 28px;
    margin-bottom: 20px;
}

/* Arama formu */
.log-search-container {
    margin-bottom: 20px;
}

.log-search-container input {
    padding: 8px;
    width: 70%;
    margin-right: 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
}

.log-search-container button {
    padding: 8px 15px;
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.log-search-container button:hover {
    background-color: #0056b3;
}

/* Filtreleme seçeneği */
.log-filter-form {
    margin-bottom: 20px;
}

.log-filter-form select {
    padding: 8px;
    font-size: 16px;
    border: 1px solid #ccc;
    border-radius: 5px;
}

/* Log tablosu */
.log-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    border: 1px solid #ddd;
}

.log-table th, .log-table td {
    padding: 12px;
    text-align: left;
}

.log-table th {
    background-color: #333;
    color: #fff;
}

.log-table tr:nth-child(even) {
    background-color: #f9f9f9;
}

.log-table tr:hover {
    background-color: #f1f1f1;
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
                <li><a href="admin.php?page=log">Sistem Logları</a></li>
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
                        case '3': echo "Düzenleme işlemi başarılı!"; break;
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
                case 'edit_employee':
                    include 'pages/admin/edit_employee.php';
                    break;
                case 'edit_customer':
                    include 'pages/admin/edit_customer.php';
                    break;       
                case 'log':
                    include 'pages/admin/log.php';
                    break;   
                default:
                    include 'pages/admin/employees.php';
            }
            ?>
        </div>
    </div>
</body>
</html> 