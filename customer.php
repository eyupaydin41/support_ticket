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
        $stmt = $conn->prepare("INSERT INTO TICKET (customer_id, title, description, category_id, priorities_id, status_id, create_date) VALUES (?, ?, ?, ?, ?, 1, CURRENT_TIMESTAMP)");
        $stmt->execute([
            $_SESSION['user_id'],
            $_POST['title'],
            $_POST['description'],
            $_POST['category_id'],
            $_POST['priorities_id']
        ]);
        $stmt = $conn->prepare("INSERT INTO LOG (user_id, action, action_date) VALUES (?, 'Yeni talep oluşturuldu', CURRENT_TIMESTAMP)");
        $stmt->execute([$_SESSION['user_id']]);
        header("Location: customer.php?page=my_tickets&success=1");
        exit;
    } catch(PDOException $e) {
        $error = $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_response'])) {
    try {

        // Yanıtı ekle
        $stmt = $conn->prepare("
            INSERT INTO RESPONSE (ticket_id, employee_id, status_id, description, response_date)
            VALUES (?, ?, 1, ?, CURRENT_TIMESTAMP)
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
        
        header("Location: customer.php?page=ticket_details&ticket_id=" . $_POST['ticket_id'] . "&success=1");
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
    <style>
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

.form-container {
    background-color: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    max-width: 800px;
    margin: 0 auto;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 10px;
    font-weight: 600;
    color: #333;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 16px;
    transition: border-color 0.3s ease;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: #007bff;
    outline: none;
}

button {
    background-color: #007bff;
    color: #fff;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    transition: background-color 0.3s ease;
}

button:hover {
    background-color: #0056b3;
}

.ticket-details {
    background-color: #ffffff;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    padding: 30px;
    max-width: 1200px;
    margin: 0 auto;
    margin-top: 30px;
}

.tickets-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
    margin-top: 30px;
}

.ticket-card {
    background-color: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    padding: 20px;
    transition: box-shadow 0.3s ease;
}

.ticket-card:hover {
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
}

.ticket-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.ticket-header h2 {
    font-size: 24px;
    color: #333;
}

.priority-badge {
    padding: 10px 20px;
    border-radius: 25px;
    color: #fff;
    font-size: 14px;
    text-transform: capitalize;
    font-weight: bold;
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

.ticket-description {
    margin-bottom: 20px;
}

.ticket-description h3 {
    font-size: 20px;
    margin-bottom: 10px;
    color: #333;
}

.ticket-description p {
    font-size: 16px;
    line-height: 1.6;
    color: #444;
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
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

.ticket-info {
    margin-bottom: 20px;
    padding: 20px;
    background-color: #f8f9fa;
    border-radius: 8px;
}

.ticket-info p {
    margin: 5px 0;
    font-size: 16px;
    color: #555;
}

.responses-section {
    margin-top: 30px;
}

.responses-section h3 {
    font-size: 20px;
    margin-bottom: 15px;
    color: #333;
}

.response-card {
    background-color: #f8f9fa;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.response-meta {
    margin-bottom: 10px;
    font-size: 14px;
    color: #555;
    font-style: italic;
}

.response-content {
    font-size: 16px;
    color: #333;
    line-height: 1.6;
}

.response-form {
    margin-top: 30px;
    padding: 20px;
    background-color: #f8f9fa;
    border-radius: 12px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.response-form h3 {
    font-size: 20px;
    margin-bottom: 20px;
    color: #333;
}

.response-form .form-group {
    margin-bottom: 20px;
}

.response-form label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
    color: #333;
}

.response-form textarea {
    width: 100%;
    padding: 12px;
    font-size: 16px;
    border: 1px solid #ccc;
    border-radius: 8px;
    resize: vertical;
}

.response-form button {
    background-color: #007bff;
    color: #fff;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.response-form button:hover {
    background-color: #0056b3;
}


.success,
.error {
    background-color: #d4edda;
    color: #155724;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 16px;
}

.error {
    background-color: #f8d7da;
    color: #721c24;
}
    </style>
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
                    <?php if ($_GET['success'] == '1') echo "Başarıyla oluşturuldu!"; ?>
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
