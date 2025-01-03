<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) { // Sadece admin görebilir
    header("Location: login.php");
    exit;
}

try {
    $conn = new PDO("mysql:host=localhost;dbname=tickedsystem", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Bağlantı hatası: " . $e->getMessage());
}

// Log kayıtlarını getirme
if (isset($_GET['action']) && $_GET['action'] === 'get_logs') {
    try {
        $stmt = $conn->query("
            SELECT l.*, u.name as user_name, r.role_name
            FROM LOG l
            JOIN USERS u ON l.user_id = u.user_id
            JOIN ROLE r ON u.role_id = r.role_id
            ORDER BY l.action_date DESC
            LIMIT 1000
        ");
        
        echo json_encode(['success' => true, 'logs' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Logları</title>
    <link rel="stylesheet" href="assets/css/logcss.css">
</head>
<body>
    <div class="container">
        <nav class="sidebar">
            <ul>
                <li><a href="admin.php">← Admin Paneline Dön</a></li>
                <li><a href="#" onclick="filterLogs('all')">Tüm Loglar</a></li>
                <li><a href="#" onclick="filterLogs('today')">Bugünün Logları</a></li>
                <li><a href="#" onclick="filterLogs('week')">Bu Haftanın Logları</a></li>
                <li><a href="logout.php">Çıkış Yap</a></li>
            </ul>
        </nav>

        <div class="content" id="content">
            <h1>Sistem Logları</h1>
            <div class="search-container">
                <input type="text" id="searchInput" placeholder="Log kayıtlarında ara..." onkeyup="searchLogs()">
            </div>
            <div id="logContainer"></div>
        </div>
    </div>

    <script>
        let allLogs = [];

        async function loadLogs() {
            try {
                const response = await fetch('log.php?action=get_logs');
                const data = await response.json();
                
                if (data.success) {
                    allLogs = data.logs;
                    displayLogs(allLogs);
                }
            } catch (error) {
                console.error('Loglar yüklenirken hata:', error);
            }
        }

        function displayLogs(logs) {
            const container = document.getElementById('logContainer');
            
            let html = `
                <table class="log-table">
                    <thead>
                        <tr>
                            <th>Tarih/Saat</th>
                            <th>Kullanıcı</th>
                            <th>Rol</th>
                            <th>İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            logs.forEach(log => {
                html += `
                    <tr>
                        <td>${formatDate(log.action_date)}</td>
                        <td>${log.user_name}</td>
                        <td>${log.role_name}</td>
                        <td>${log.action}</td>
                    </tr>
                `;
            });

            html += '</tbody></table>';
            container.innerHTML = html;
        }

        function filterLogs(period) {
            const now = new Date();
            let filteredLogs;

            switch(period) {
                case 'today':
                    filteredLogs = allLogs.filter(log => {
                        const logDate = new Date(log.action_date);
                        return logDate.toDateString() === now.toDateString();
                    });
                    break;
                case 'week':
                    const weekAgo = new Date(now.setDate(now.getDate() - 7));
                    filteredLogs = allLogs.filter(log => {
                        return new Date(log.action_date) >= weekAgo;
                    });
                    break;
                default:
                    filteredLogs = allLogs;
            }

            displayLogs(filteredLogs);
        }

        function searchLogs() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const filteredLogs = allLogs.filter(log => 
                log.user_name.toLowerCase().includes(searchTerm) ||
                log.action.toLowerCase().includes(searchTerm) ||
                log.role_name.toLowerCase().includes(searchTerm)
            );
            displayLogs(filteredLogs);
        }

        function formatDate(dateString) {
            const options = { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            return new Date(dateString).toLocaleDateString('tr-TR', options);
        }

        // Sayfa yüklendiğinde logları getir
        document.addEventListener('DOMContentLoaded', loadLogs);
    </script>
</body>
</html> 