<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 3) { // role_id 3 = Manager
    header("Location: login.php");
    exit;
}

try {
    $conn = new PDO("mysql:host=localhost;dbname=tickedsystem", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Bağlantı hatası: " . $e->getMessage());
}

// Talep yanıtlama
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_response'])) {
    try {
        // Önce talebin durumunu güncelle
        $stmt = $conn->prepare("UPDATE TICKET SET status_id = ? WHERE ticket_id = ?");
        $stmt->execute([$_POST['status_id'], $_POST['ticket_id']]);
        
        // Yanıtı ekle
        $stmt = $conn->prepare("
            INSERT INTO RESPONSE (ticket_id, employee_id, description, response_date)
            VALUES (?, ?, ?, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            $_POST['ticket_id'],
            $_SESSION['user_id'],
            $_POST['response']
        ]);
        
        header("Location: manager.php?page=ticket_details&ticket_id=" . $_POST['ticket_id'] . "&success=1");
        exit;
    } catch(PDOException $e) {
        $error = $e->getMessage();
    }
}

// Çalışan ekleme
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_employee'])) {
    try {
        $stmt = $conn->prepare("
            INSERT INTO USERS (name, email, password, role_id, department_id) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_POST['name'],
            $_POST['email'],
            password_hash($_POST['password'], PASSWORD_DEFAULT),
            4, // role_id = 4 (Employee)
            $_SESSION['department_id']
        ]);
        
        header("Location: manager.php?page=employees&success=2");
        exit;
    } catch(PDOException $e) {
        $error = $e->getMessage();
    }
}

// Çalışan silme
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_employee'])) {
    try {
        $stmt = $conn->prepare("
            DELETE FROM USERS 
            WHERE user_id = ? AND role_id = 4 AND department_id = ?
        ");
        $stmt->execute([$_POST['user_id'], $_SESSION['department_id']]);
        
        header("Location: manager.php?page=employees&success=1");
        exit;
    } catch(PDOException $e) {
        $error = $e->getMessage();
    }
}

$page = $_GET['page'] ?? 'all_tickets';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yönetici Paneli</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="container">
        <nav class="sidebar">
            <ul>
                <li><a href="manager.php?page=all_tickets">Tüm Talepler</a></li>
                <li><a href="manager.php?page=employees">Çalışanlar</a></li>
                <li><a href="manager.php?page=add_employee">Yeni Çalışan Ekle</a></li>
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
                        case '1': echo "İşlem başarıyla tamamlandı!"; break;
                        case '2': echo "Çalışan başarıyla eklendi!"; break;
                    }
                    ?>
                </div>
            <?php endif; ?>

            <?php
            switch($page) {
                case 'all_tickets':
                    include 'pages/manager/all_tickets.php';
                    break;
                case 'employees':
                    include 'pages/manager/employees.php';
                    break;
                case 'add_employee':
                    include 'pages/manager/add_employee.php';
                    break;
                case 'ticket_details':
                    include 'pages/manager/ticket_details.php';
                    break;
                default:
                    include 'pages/manager/all_tickets.php';
            }
            ?>
        </div>
    </div>
</body>
</html> 