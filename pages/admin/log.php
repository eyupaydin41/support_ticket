<?php
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

// Filtreleme ve arama
$filter = '';
$searchTerm = '';
$periodFilter = isset($_GET['filter_period']) ? $_GET['filter_period'] : '';

// Filtreleme işlemi
if ($periodFilter) {
    switch($periodFilter) {
        case 'today':
            $filter = "AND DATE(action_date) = CURDATE()";
            break;
        case 'week':
            $filter = "AND action_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            break;
        case 'all':
        default:
            $filter = ''; // Tüm loglar
    }
}

// Arama işlemi
if (isset($_POST['search'])) {
    $searchTerm = $_POST['search'];
    if ($searchTerm) {
        // Arama terimi varsa filtreye ekleyin
        $filter .= " AND (u.name LIKE :searchTerm OR l.action LIKE :searchTerm OR r.role_name LIKE :searchTerm)";
    }
}

// Logları getir
$sql = "
    SELECT l.*, u.name as user_name, r.role_name
    FROM LOG l
    JOIN USERS u ON l.user_id = u.user_id
    JOIN ROLE r ON u.role_id = r.role_id
    WHERE 1=1 $filter
    ORDER BY l.action_date DESC
    LIMIT 1000
";

$stmt = $conn->prepare($sql);

// Eğer arama terimi varsa, parametreyi bind edin
if ($searchTerm) {
    $stmt->execute([':searchTerm' => "%$searchTerm%"]);
} else {
    // Arama terimi yoksa sadece filtreyi çalıştır
    $stmt->execute();
}

$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Tarih formatlama fonksiyonu
function formatDate($dateString) {
    $options = [ 
        'year' => 'numeric', 
        'month' => 'long', 
        'day' => 'numeric',
        'hour' => '2-digit',
        'minute' => '2-digit'
    ];
    return (new DateTime($dateString))->format('Y-m-d H:i');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Logları</title>
</head>
<body>
    <div class="container">
        <nav class="sidebar">
            <ul>
                <li><a href="admin.php">← Admin Paneline Dön</a></li>
                <li><a href="admin.php?page=log" <?php echo (isset($_GET['page']) && $_GET['page'] == 'log') ? 'class="active"' : ''; ?>>Tüm Loglar</a></li>
                <li><a href="admin.php?page=log&filter_period=today" <?php echo (isset($_GET['filter_period']) && $_GET['filter_period'] == 'today') ? 'class="active"' : ''; ?>>Bugünün Logları</a></li>
                <li><a href="admin.php?page=log&filter_period=week" <?php echo (isset($_GET['filter_period']) && $_GET['filter_period'] == 'week') ? 'class="active"' : ''; ?>>Bu Haftanın Logları</a></li>
                <li><a href="logout.php">Çıkış Yap</a></li>
            </ul>
        </nav>

        <div class="log-content">
            <h1>Sistem Logları</h1>
            
            <!-- Arama Formu -->
            <form method="POST" class="log-search-container">
                <input type="text" name="search" placeholder="Log kayıtlarında ara..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                <button type="submit">Ara</button>
            </form>


            <div id="logContainer">
                <!-- Log Tablosu -->
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
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo formatDate($log['action_date']); ?></td>
                                <td><?php echo htmlspecialchars($log['user_name']); ?></td>
                                <td><?php echo htmlspecialchars($log['role_name']); ?></td>
                                <td><?php echo htmlspecialchars($log['action']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
