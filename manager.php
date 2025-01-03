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

// İstatistikleri getir
if (isset($_GET['action']) && $_GET['action'] === 'get_statistics') {
    try {
        $stats = [];
        
        // Toplam talep sayısı
        $stmt = $conn->query("SELECT COUNT(*) as total FROM TICKET");
        $stats['total_tickets'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Açık talep sayısı
        $stmt = $conn->query("SELECT COUNT(*) as open FROM TICKET WHERE status_id = 1");
        $stats['open_tickets'] = $stmt->fetch(PDO::FETCH_ASSOC)['open'];
        
        // Bekleyen yanıt sayısı
        $stmt = $conn->query("SELECT COUNT(*) as pending FROM RESPONSE WHERE status_id = 1");
        $stats['pending_responses'] = $stmt->fetch(PDO::FETCH_ASSOC)['pending'];
        
        // Kategori bazlı talep dağılımı
        $stmt = $conn->query("
            SELECT c.category_name, COUNT(*) as count
            FROM TICKET t
            JOIN CATEGORY c ON t.category_id = c.category_id
            GROUP BY c.category_id
        ");
        $stats['category_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'stats' => $stats]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Yanıt detaylarını getir
if (isset($_GET['action']) && $_GET['action'] === 'get_response_details') {
    try {
        $stmt = $conn->prepare("
            SELECT r.*, t.title as ticket_title, u.name as employee_name,
                   t.description as ticket_description
            FROM RESPONSE r
            JOIN TICKET t ON r.ticket_id = t.ticket_id
            JOIN USERS u ON r.employee_id = u.user_id
            WHERE r.response_id = ?
        ");
        
        $stmt->execute([$_GET['response_id']]);
        echo json_encode(['success' => true, 'response' => $stmt->fetch(PDO::FETCH_ASSOC)]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Yanıt onaylama işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'approve_response') {
        try {
            $stmt = $conn->prepare("
                UPDATE RESPONSE 
                SET status_id = 2 
                WHERE response_id = ?
            ");
            
            $stmt->execute([$_POST['response_id']]);
            
            // Log kaydı
            $stmt = $conn->prepare("
                INSERT INTO LOG (user_id, action, action_date) 
                VALUES (?, 'Yanıt onaylandı', CURRENT_TIMESTAMP)
            ");
            $stmt->execute([$_SESSION['user_id']]);
            
            echo json_encode(['success' => true]);
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
}

// Bekleyen yanıtları getirme
if (isset($_GET['action']) && $_GET['action'] === 'get_pending_responses') {
    try {
        $stmt = $conn->query("
            SELECT r.*, t.title as ticket_title, u.name as employee_name, 
                   t.description as ticket_description
            FROM RESPONSE r
            JOIN TICKET t ON r.ticket_id = t.ticket_id
            JOIN USERS u ON r.employee_id = u.user_id
            WHERE r.status_id = 1
            ORDER BY r.response_date DESC
        ");
        
        echo json_encode(['success' => true, 'responses' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Müdür Paneli</title>
    <link rel="stylesheet" href="assets/css/managercss.css">
</head>
<body>
    <div class="container">
        <nav class="sidebar">
            <ul>
                <li><a href="#" id="dashboardLink">Dashboard</a></li>
                <li><a href="#" id="allTicketsLink">Tüm Talepler</a></li>
                <li><a href="#" id="pendingResponsesLink">Bekleyen Yanıtlar</a></li>
                <li><a href="#" id="statisticsLink">İstatistikler</a></li>
                <li><a href="logout.php">Çıkış Yap</a></li>
            </ul>
        </nav>

        <div class="content" id="content">
            <!-- İçerik JavaScript ile doldurulacak -->
        </div>
    </div>

    <script>
        // Event Listener'ları ekle
        document.addEventListener('DOMContentLoaded', function() {
            // Sayfa yüklendiğinde dashboard'u göster
            showContent('dashboard');

            // Link event listener'ları
            document.getElementById('dashboardLink').addEventListener('click', function(e) {
                e.preventDefault();
                showContent('dashboard');
            });

            document.getElementById('allTicketsLink').addEventListener('click', function(e) {
                e.preventDefault();
                showContent('all_tickets');
            });

            document.getElementById('pendingResponsesLink').addEventListener('click', function(e) {
                e.preventDefault();
                showContent('pending_responses');
            });

            document.getElementById('statisticsLink').addEventListener('click', function(e) {
                e.preventDefault();
                showContent('statistics');
            });
        });

        // showContent fonksiyonu (önceki gönderdiğimiz gibi)
        async function showContent(section) {
            const content = document.getElementById('content');
            
            switch(section) {
                case 'dashboard':
                    try {
                        const response = await fetch('manager.php?action=get_statistics');
                        const data = await response.json();
                        
                        if (data.success) {
                            const stats = data.stats;
                            content.innerHTML = `
                                <div class="dashboard">
                                    <h1>Hoş Geldiniz</h1>
                                    <div class="stats-grid">
                                        <div class="stat-card">
                                            <h3>Toplam Talep</h3>
                                            <p class="stat-number">${stats.total_tickets}</p>
                                        </div>
                                        <div class="stat-card">
                                            <h3>Açık Talep</h3>
                                            <p class="stat-number">${stats.open_tickets}</p>
                                        </div>
                                        <div class="stat-card">
                                            <h3>Bekleyen Yanıt</h3>
                                            <p class="stat-number">${stats.pending_responses}</p>
                                        </div>
                                    </div>
                                    
                                    <div class="category-stats">
                                        <h3>Kategori Dağılımı</h3>
                                        <div class="category-chart">
                                            ${stats.category_distribution.map(cat => `
                                                <div class="category-bar">
                                                    <div class="bar-label">${cat.category_name}</div>
                                                    <div class="bar" style="width: ${(cat.count / stats.total_tickets * 100)}%">
                                                        ${cat.count}
                                                    </div>
                                                </div>
                                            `).join('')}
                                        </div>
                                    </div>
                                </div>
                            `;
                        }
                    } catch (error) {
                        content.innerHTML = '<p>İstatistikler yüklenirken bir hata oluştu.</p>';
                    }
                    break;
                    
                // ... diğer case'ler devam ediyor ...
            }
        }
    </script>
</body>
</html> 