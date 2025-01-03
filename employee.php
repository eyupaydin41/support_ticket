<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], [3, 4])) { // role_id 3,4 = Employee
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
        
        // Log kaydı
        $stmt = $conn->prepare("
            INSERT INTO LOG (user_id, action, action_date)
            VALUES (?, 'Talep yanıtlandı', CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$_SESSION['user_id']]);
        
        header("Location: employee.php?page=ticket_details&ticket_id=" . $_POST['ticket_id'] . "&success=1");
        exit;
    } catch(PDOException $e) {
        $error = $e->getMessage();
    }
}

$page = $_GET['page'] ?? 'open_tickets';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Çalışan Paneli</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="container">
        <nav class="sidebar">
            <ul>
                <li><a href="employee.php?page=open_tickets">Açık Talepler</a></li>
                <li><a href="employee.php?page=my_tickets">Yanıtladığım Talepler</a></li>
                <li><a href="logout.php">Çıkış Yap</a></li>
            </ul>
        </nav>
        
        <div class="content">
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="success">
                    <?php if ($_GET['success'] == '1') echo "Yanıt başarıyla eklendi!"; ?>
                </div>
            <?php endif; ?>

            <?php
            switch($page) {
                case 'open_tickets':
                    include 'pages/employee/open_tickets.php';
                    break;
                case 'my_tickets':
                    include 'pages/employee/my_tickets.php';
                    break;
                case 'ticket_details':
                    include 'pages/employee/ticket_details.php';
                    break;
                default:
                    include 'pages/employee/open_tickets.php';
            }
            ?>
        </div>
    </div>
</body>
</html> 