<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 2) { // role_id 2 = Customer
    header("Location: login.php");
    exit;
}

try {
    $conn = new PDO("mysql:host=localhost;dbname=tickedsystem", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Bağlantı hatası: " . $e->getMessage());
}

// Yeni talep oluşturma
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_ticket'])) {
    try {
        $stmt = $conn->prepare("
            INSERT INTO TICKET (customer_id, title, description, category_id, priorities_id, status_id, create_date)
            VALUES (?, ?, ?, ?, ?, 1, CURRENT_TIMESTAMP)
        ");
        
        $stmt->execute([
            $_SESSION['user_id'],
            $_POST['title'],
            $_POST['description'],
            $_POST['category_id'],
            $_POST['priorities_id']
        ]);
        
        // Log kaydı
        $stmt = $conn->prepare("
            INSERT INTO LOG (user_id, action, action_date)
            VALUES (?, 'Yeni talep oluşturuldu', CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$_SESSION['user_id']]);
        
        header("Location: customer.php?page=my_tickets&success=1");
        exit;
    } catch(PDOException $e) {
        $error = $e->getMessage();
    }
}

$page = $_GET['page'] ?? 'my_tickets';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Müşteri Paneli</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="container">
        <nav class="sidebar">
            <ul>
                <li><a href="customer.php?page=create_ticket">Yeni Talep Oluştur</a></li>
                <li><a href="customer.php?page=my_tickets">Taleplerim</a></li>
                <li><a href="logout.php">Çıkış Yap</a></li>
            </ul>
        </nav>
        
        <div class="content">
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="success">
                    <?php if ($_GET['success'] == '1') echo "Talep başarıyla oluşturuldu!"; ?>
                </div>
            <?php endif; ?>

            <?php
            switch($page) {
                case 'create_ticket':
                    include 'pages/customer/create_ticket.php';
                    break;
                case 'my_tickets':
                    include 'pages/customer/my_tickets.php';
                    break;
                case 'ticket_details':
                    include 'pages/customer/ticket_details.php';
                    break;
                default:
                    include 'pages/customer/my_tickets.php';
            }
            ?>
        </div>
    </div>
</body>
</html>
