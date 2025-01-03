<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die('Yetkisiz erişim.');
}

try {
    $conn = new PDO("mysql:host=localhost;dbname=tickedsystem", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Bağlantı hatası: " . $e->getMessage());
}

if (isset($_GET['file_id'])) {
    try {
        $stmt = $conn->prepare("
            SELECT f.*, t.customer_id 
            FROM FILE f
            JOIN TICKET t ON f.ticket_id = t.ticket_id
            WHERE f.file_id = ?
        ");
        
        $stmt->execute([$_GET['file_id']]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);

        // Yetki kontrolü
        if ($_SESSION['role'] == 2 && $file['customer_id'] != $_SESSION['user_id']) {
            die('Bu dosyaya erişim yetkiniz yok.');
        }

        $filePath = 'uploads/' . $file['file_name'];
        
        if (file_exists($filePath)) {
            $mimeType = mime_content_type($filePath);
            header('Content-Type: ' . $mimeType);
            header('Content-Disposition: inline; filename="' . basename($file['file_name']) . '"');
            readfile($filePath);
        } else {
            die('Dosya bulunamadı.');
        }
    } catch(PDOException $e) {
        die("Veritabanı hatası: " . $e->getMessage());
    }
} 