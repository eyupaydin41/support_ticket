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

// Talep kapatma işlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['close_ticket'])) {
    try {
        // Talebin durumunu "Kapalı" (3) olarak güncelle
        $stmt = $conn->prepare("UPDATE TICKET SET status_id = 3 WHERE ticket_id = ?");
        $stmt->execute([$_POST['ticket_id']]);
        
        // Log kaydı (isteğe bağlı, kapanış işlemi için)
        $stmt = $conn->prepare("
            INSERT INTO LOG (user_id, action, action_date)
            VALUES (?, 'Talep kapatıldı', CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$_SESSION['user_id']]);

        // İşlem tamamlandığında yönlendirme
        header("Location: employee.php?page=open_tickets&ticket_id=" . $_POST['ticket_id'] . "&success=2");
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

.btn-blue {
    background-color: #007bff; /* Mavi renk */
    color: white;
    border: none;
    padding: 10px 20px;
    font-size: 16px;
    cursor: pointer;
    border-radius: 5px;
}

.btn-blue:hover {
    background-color: #0056b3; /* Hoverda daha koyu mavi */
}

.btn-red {
    background-color: #dc3545; /* Kırmızı renk */
    color: white;
    border: none;
    padding: 10px 20px;
    font-size: 16px;
    cursor: pointer;
    border-radius: 5px;
}

.btn-red:hover {
    background-color: #c82333; /* Hoverda daha koyu kırmızı */
}


/* Ticket kartları */
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

.ticket-actions {
    text-align: right;
}

.ticket-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
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

.btn-close {
    background-color: #dc3545;
    color: #fff;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s ease;
    margin-top: 20px;
}

.btn-close:hover {
    background-color: #c82333;
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


.ticket-header h2 {
    font-size: 24px;
    color: #333;
}

.ticket-description {
    background-color: #f9f9f9;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 30px;
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
}

/* Genel linkler */
.btn-view {
    text-decoration: none;
    font-weight: bold;
    font-size: 16px;
    transition: color 0.3s ease;
}

.btn-view:hover {
    text-decoration: underline;
}

.ticket-info {
    margin-bottom: 10px;
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
    color: #fff;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.response-form {
    display: flex;
    flex-direction: column;
    gap: 20px; /* Form ve butonlar arasındaki mesafeyi ayarlıyoruz */
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
    color: #fff;
    border: none;
    padding: 12px 20px;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.response-form form {
    display: inline-block;
    margin-right: 10px; /* Butonlar arasındaki mesafeyi ayarlamak için */
}

.response-form {
    display: flex;
    justify-content: space-between;
    align-items: center;
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



.form-container {
    display: flex;
    flex-direction: column;
    gap: 10px; /* Textarea ve buton arasındaki mesafeyi ayarlıyoruz */
    width: 90%;    
}

.form-buttons {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
}

.btn-blue {
    background-color: #007bff; /* Mavi renk */
    color: white;
    border: none;
    padding: 10px 20px;
    font-size: 16px;
    cursor: pointer;
    border-radius: 5px;
}

.btn-blue:hover {
    background-color: #0056b3; /* Hoverda daha koyu mavi */
}

.btn-red {
    background-color: #dc3545; /* Kırmızı renk */
    color: white;
    border: none;
    padding: 10px 20px;
    font-size: 16px;
    cursor: pointer;
    border-radius: 5px;
    margin-top: 25px;
    margin-left: 45%;
}

.btn-red:hover {
    background-color: #c82333; /* Hoverda daha koyu kırmızı */
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
                    <?php if ($_GET['success'] == '1') echo "İşlem başarıyla gerçekleştirildi!"; ?>
                    <?php if ($_GET['success'] == '2') echo "Talep başarıyla kapatıldı!"; ?>
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