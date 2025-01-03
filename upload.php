<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Oturum bulunamadı.']);
    exit;
}

try {
    $conn = new PDO("mysql:host=localhost;dbname=tickedsystem", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_FILES['file'])) {
            throw new Exception('Dosya seçilmedi.');
        }

        $file = $_FILES['file'];
        $fileName = $file['name'];
        $fileType = $file['type'];
        $fileTmpName = $file['tmp_name'];
        $fileError = $file['error'];
        $fileSize = $file['size'];

        // Dosya boyutu kontrolü (5MB)
        if ($fileSize > 5242880) {
            throw new Exception('Dosya boyutu 5MB\'dan büyük olamaz.');
        }

        // İzin verilen dosya tipleri
        $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf', 'application/msword', 
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        
        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception('Geçersiz dosya tipi. Sadece JPG, PNG, PDF ve DOC dosyaları yüklenebilir.');
        }

        // Dosya adını benzersiz yap
        $uniqueName = uniqid() . '_' . $fileName;
        $uploadPath = 'uploads/' . $uniqueName;

        if (!move_uploaded_file($fileTmpName, $uploadPath)) {
            throw new Exception('Dosya yüklenirken bir hata oluştu.');
        }

        // Veritabanına dosya kaydı
        $stmt = $conn->prepare("
            INSERT INTO FILE (ticket_id, file_name, upload_date) 
            VALUES (?, ?, CURRENT_TIMESTAMP)
        ");
        
        $stmt->execute([$_POST['ticket_id'], $uniqueName]);
        $fileId = $conn->lastInsertId();

        // Log kaydı
        $stmt = $conn->prepare("
            INSERT INTO LOG (user_id, action, action_date)
            VALUES (?, 'Dosya yüklendi: " . $fileName . "', CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$_SESSION['user_id']]);

        echo json_encode([
            'success' => true, 
            'file_id' => $fileId,
            'file_name' => $fileName,
            'file_path' => $uploadPath
        ]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
} 