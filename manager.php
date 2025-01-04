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

// Yanıt onaylama
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['approve_response'])) {
    try {
        // RESPONSE tablosunda yanıtın durumunu güncelle
        $stmt = $conn->prepare("UPDATE RESPONSE SET status_id = ? WHERE response_id = ?");
        $stmt->execute([1, $_POST['response_id']]);

        // Başarılı mesajı ekleyip geri yönlendirme yap
        header("Location: manager.php?page=pending_responses&success=1");
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
    <style>
        /* Genel Stil Ayarları */
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

.content {
    margin-left: 270px;
    padding: 30px 40px;
    background-color: #fff;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    flex-grow: 1;
    height: 100vh;
    overflow-y: auto;
}

h1, h2, h3 {
    color: #333;
}

.error, .success {
    margin: 10px 0;
    padding: 10px;
    border-radius: 5px;
}

.error {
    background-color: #e74c3c;
    color: white;
}

.success {
    background-color: #2ecc71;
    color: white;
}

/* Ticket Kartları */
.ticket-card, .response-card, .pending-response-card {
    background-color: #ecf0f1;
    padding: 20px;
    margin: 10px 0;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.ticket-header h3, .ticket-header h2 {
    margin: 0;
    font-size: 20px;
    font-weight: bold;
}

.ticket-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.priority-badge {
    padding: 8px 16px;
    border-radius: 25px;
    color: #fff;
    font-size: 14px;
    text-transform: capitalize;
}

.priority-low {
    background-color: #28a745;
}

.priority-medium {
    background-color: #ffc107;
}

.priority-high {
    background-color: #dc3545;
}

.ticket-info p {
    margin: 5px 0;
    font-size: 14px;
    color: #555;
}

.ticket-actions {
    text-align: right;
}

.btn-view {
    text-decoration: none;
    color: #007bff;
    font-weight: bold;
    font-size: 16px;
    transition: color 0.3s ease;
}

.btn-view:hover {
    color: #0056b3;
    text-decoration: underline;
}

/* Yanıtlar */
.responses-section {
    margin-top: 20px;
}

.response-meta {
    font-size: 14px;
    color: #7f8c8d;
}

.response-content {
    margin-top: 10px;
    font-size: 16px;
    color: #34495e;
}

/* Pending Responses */
.pending-responses-container {
    display: flex;
    flex-direction: column;
}

.pending-responses-container .response-card {
    display: flex;
    justify-content: space-between;
}

button[type="submit"] {
    background-color: #3498db;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

button[type="submit"]:hover {
    background-color: #2980b9;
}

/* Genel düzenlemeler */
.tickets-container, .pending-responses-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
    margin-top: 30px;
}

.ticket-card, .response-card {
    background-color: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    padding: 20px;
    transition: box-shadow 0.3s ease;
}

.ticket-card:hover, .response-card:hover {
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
}

    </style>
</head>
<body>
    <div class="container">
        <nav class="sidebar">
            <ul>
                <li><a href="manager.php?page=all_tickets">Tüm Talepler</a></li>
                <li><a href="manager.php?page=my_tickets">Yanıt Verdiğim Talepler</a></li>
                <li><a href="manager.php?page=pending_responses">Onay Bekleyen Yanıtlar</a></li>
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
                case 'ticket_details':
                    include 'pages/manager/ticket_details.php';
                    break;
                case 'my_tickets':
                    include 'pages/manager/my_tickets.php';
                    break;
                case 'pending_responses':
                    include 'pages/manager/pending_responses.php';
                    break;
                default:
                    include 'pages/manager/all_tickets.php';
            }
            ?>
        </div>
    </div>
</body>
</html> 