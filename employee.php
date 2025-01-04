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

        // Yanıtı ekle
        $stmt = $conn->prepare("
            INSERT INTO RESPONSE (ticket_id, employee_id, status_id, description, response_date)
            VALUES (?, ?, 2, ?, CURRENT_TIMESTAMP)
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
    <style>
        /* Genel ayarlar */
body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f9;
    margin: 0;
    padding: 0;
    display: flex;
}

/* Konteyner düzeni */
.container {
    display: flex;
    width: 100%;
    min-height: 100vh;
}

/* Sidebar (sol menü) */
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
    margin: 20px 0;
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

.content {
    margin-left: 270px;
    padding: 30px 40px;
    background-color: #fff;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    flex-grow: 1;
    height: 100vh;
    overflow-y: auto;
}

/* Hata ve başarı mesajları */
.error, .success {
    background-color: #f2dede;
    color: #a94442;
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 5px;
}

.success {
    background-color: #dff0d8;
    color: #3c763d;
}

/* Ticket kartları */
.tickets-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.ticket-card {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    padding: 20px;
}

.ticket-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.priority-badge {
    padding: 5px 10px;
    border-radius: 12px;
    color: #fff;
    font-size: 14px;
}

.priority-low {
    background-color: #4caf50;
}

.priority-medium {
    background-color: #ff9800;
}

.priority-high {
    background-color: #f44336;
}

/* Detaylar ve form */
.ticket-details, .response-form {
    margin-top: 30px;
}

.ticket-header h2 {
    font-size: 24px;
    margin-bottom: 10px;
}

.ticket-info {
    margin-bottom: 20px;
}

.ticket-description {
    background-color: #f9f9f9;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 30px;
}

.responses-section h3, .response-form h3 {
    margin-bottom: 10px;
}

.response-card {
    background-color: #f9f9f9;
    padding: 15px;
    margin-bottom: 10px;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.response-meta {
    font-weight: bold;
    margin-bottom: 10px;
}

.response-content {
    white-space: pre-wrap;
}

/* Yanıt Ekleme Formu */
.response-form textarea {
    width: 100%;
    padding: 12px;
    border-radius: 5px;
    border: 1px solid #ddd;
    margin-bottom: 15px;
    font-size: 14px;
    font-family: Arial, sans-serif;
}

.response-form button {
    background-color: #1e90ff;
    color: #fff;
    border: none;
    padding: 12px 20px;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.response-form button:hover {
    background-color: #1c86ee;
}

/* İstatistikler (Welcome Sayfası) */
.dashboard-stats {
    display: flex;
    justify-content: space-between;
    margin-top: 30px;
}

.stat-box {
    background-color: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    text-align: center;
    width: 48%;
}

.stat-box h3 {
    margin-bottom: 10px;
    font-size: 18px;
    color: #333;
}

.stat-number {
    font-size: 24px;
    font-weight: bold;
    color: #1e90ff;
}

/* Genel linkler */
.btn-view {
    color: #1e90ff;
    font-weight: bold;
    text-decoration: none;
}

.btn-view:hover {
    text-decoration: underline;
}

    </style>
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