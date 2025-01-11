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
        
        header("Location: manager.php?page=ticket_details&ticket_id=" . $_POST['ticket_id'] . "&success=1");
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
        header("Location: manager.php?page=all_tickets&ticket_id=" . $_POST['ticket_id'] . "&success=3");
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

// Yanıt reddetme
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reject_response'])) {
    try {
        $stmt = $conn->prepare("UPDATE RESPONSE SET status_id = ? WHERE response_id = ?");
        $stmt->execute([3, $_POST['response_id']]);

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
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f8f9fa;
    margin: 0;
    padding: 0;
    display: flex;
    
}
/* Konteyner düzeni */
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

.open-tickets-title {
    font-size: 28px;
    font-weight: bold;
    color: #333;
    margin-top: 30px;
    margin-bottom: 20px;
    text-align: center;
    letter-spacing: 1px;
    background-color: #f4f4f9;
    padding: 10px 20px;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    transition: color 0.3s ease;
}

.success {
    background-color: #2ecc71;
    color: white;
}

/* Ticket Kartları */
.ticket-card, .pending-response-card {
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

.ticket-details {
    background-color: #ffffff;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    padding: 30px;
    max-width: 1200px;
    margin: 0 auto;
    margin-top: 30px;
}

.ticket-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
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

.btn-blue {
    background-color: #007bff; /* Mavi renk */
    color: white;
    border: none;
    padding: 10px 20px;
    font-size: 16px;
    cursor: pointer;
    border-radius: 5px;
}

.btn-green {
    background-color: #28a745; /* Yeşil arka plan */
    color: #fff; /* Beyaz yazı rengi */
    border: none; /* Çerçeve yok */
    padding: 10px 20px; /* İçerik boşluğu */
    border-radius: 5px; /* Köşeleri yuvarlatma */
    font-size: 16px; /* Yazı boyutu */
    cursor: pointer; /* Tıklanabilir imleç */
    transition: background-color 0.3s ease; /* Hover animasyonu */
}

.btn-green:hover {
    background-color: #218838; /* Hover durumunda daha koyu bir yeşil */
}

.btn-blue:hover {
    background-color: #0056b3; /* Hoverda daha koyu mavi */
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

.close_ticket {
    margin-top: 30px;
    margin-left: 40%;
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
}

.form-buttons {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
}



.form-container {
    display: flex;
    flex-direction: column;
    gap: 10px; /* Textarea ve buton arasındaki mesafeyi ayarlıyoruz */
    width: 90%;    
}

.responses-section {
    margin-top: 30px;
}

.responses-section h3 {
    font-size: 20px;
    margin-bottom: 15px;
    color: #333;
}

.response-meta {
    margin-bottom: 10px;
    font-size: 14px;
    color: #555;
    font-style: italic;
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

.pending-responses-container  {
    display: flex;
    justify-content: space-between;
}

.response-card {
    background-color: #f8f9fa;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

/* Genel düzenlemeler */
.tickets-container, .pending-responses-container {
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

/* Genel */
.pending-responses {
    display: flex;
    flex-direction: column;
    gap: 20px;
    margin-top: 20px;
}

.pending-responses-message {
    font-size: 18px;
    color: #555;
    text-align: center;
    padding: 20px;
}

/* Yanıt Kartları */
.pending-response-card {
    background-color: #fdfdfd;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.pending-response-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.pending-response-title {
    font-size: 20px;
    font-weight: bold;
    color: #333;
    margin: 0;
}

.pending-response-id {
    font-size: 14px;
    color: #888;
    font-style: italic;
}

/* Yanıt Bilgileri */
.pending-response-info p {
    margin: 5px 0;
    font-size: 16px;
    color: #444;
}

.btn-approve {
    background-color: #28a745;
    color: white;
    border: none;
    padding: 10px 20px;
    font-size: 14px;
    cursor: pointer;
    border-radius: 5px;
    transition: background-color 0.3s ease;
}

.btn-approve:hover {
    background-color: #218838;
}

.btn-reject {
    background-color: #dc3545;
    color: white;
    border: none;
    padding: 10px 20px;
    font-size: 14px;
    cursor: pointer;
    border-radius: 5px;
    transition: background-color 0.3s ease;
}

.btn-reject:hover {
    background-color: #c82333;
}

.pending-response-footer {
    display: flex;
    justify-content: space-between; /* Detay linki sola, butonlar sağa hizalanır */
    align-items: center; /* Dikey hizalama */
    margin-top: 20px; /* Üstten boşluk bırakmak için */
}

.pending-response-details-link {
    flex: 1; /* Sola yaslanır */
}

.pending-response-actions {
    display: flex;
    gap: 10px; /* Butonlar arasındaki boşluk */
    justify-content: flex-end; /* Butonları sağa hizalar */
    align-items: center; /* Dikey hizalamayı merkezler */
}


.btn-view-details {
    background-color: #007bff;
    color: white;
    text-decoration: none;
    padding: 8px 16px;
    border-radius: 5px;
    transition: background-color 0.3s ease;
}

.btn-view-details:hover {
    background-color: #0056b3;
}


    </style>
</head>
<body>
    <div class="container">
        <nav class="sidebar">
            <ul>
                <li><a href="manager.php?page=all_tickets">Açık Talepler</a></li>
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
                        case '3': echo "Talep başarıyla kapatıldı!"; break;
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