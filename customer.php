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

// API Endpoints
if (isset($_GET['action'])) {
    switch($_GET['action']) {
        case 'get_categories':
            try {
                $stmt = $conn->query("SELECT * FROM CATEGORY ORDER BY category_name");
                echo json_encode(['success' => true, 'categories' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            } catch(PDOException $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;
            
        case 'get_priorities':
            try {
                $stmt = $conn->query("SELECT * FROM PRIORITIES ORDER BY priorities_id");
                echo json_encode(['success' => true, 'priorities' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            } catch(PDOException $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;
            
        case 'get_my_tickets':
            try {
                $stmt = $conn->prepare("
                    SELECT t.*, c.category_name, s.status_name, p.priorities_name
                    FROM TICKET t
                    JOIN CATEGORY c ON t.category_id = c.category_id
                    JOIN STATUS s ON t.status_id = s.status_id
                    JOIN PRIORITIES p ON t.priorities_id = p.priorities_id
                    WHERE t.customer_id = ?
                    ORDER BY t.create_date DESC
                ");
                $stmt->execute([$_SESSION['user_id']]);
                echo json_encode(['success' => true, 'tickets' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            } catch(PDOException $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;
            
        case 'get_ticket_details':
            try {
                // Talep detaylarını getir
                $stmt = $conn->prepare("
                    SELECT t.*, c.category_name, s.status_name, p.priorities_name
                    FROM TICKET t
                    JOIN CATEGORY c ON t.category_id = c.category_id
                    JOIN STATUS s ON t.status_id = s.status_id
                    JOIN PRIORITIES p ON t.priorities_id = p.priorities_id
                    WHERE t.ticket_id = ? AND t.customer_id = ?
                ");
                $stmt->execute([$_GET['ticket_id'], $_SESSION['user_id']]);
                $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

                // Yanıtları getir
                $stmt = $conn->prepare("
                    SELECT r.*, u.name as employee_name
                    FROM RESPONSE r
                    JOIN USERS u ON r.employee_id = u.user_id
                    WHERE r.ticket_id = ?
                    ORDER BY r.response_date DESC
                ");
                $stmt->execute([$_GET['ticket_id']]);
                $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode([
                    'success' => true,
                    'ticket' => $ticket,
                    'responses' => $responses
                ]);
            } catch(PDOException $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;
    }
}

// Talep oluşturma işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_ticket') {
    try {
        $stmt = $conn->prepare("
            INSERT INTO TICKET (customer_id, title, description, category_id, priorities_id, status_id, create_date)
            VALUES (?, ?, ?, ?, ?, 1, CURRENT_TIMESTAMP)
        ");
        
        $stmt->execute([
            $_SESSION['user_id'],
            $_POST['title'],
            $_POST['description'],
            $_POST['category_id'],
            $_POST['priorities_id']
        ]);
        
        echo json_encode(['success' => true]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

$page = $_GET['page'] ?? 'welcome';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Müşteri Paneli</title>
    <link rel="stylesheet" href="assets/css/customercss.css">
</head>
<body>
    <div class="container">
        <nav class="sidebar">
            <ul>
                <li><a href="customer.php?page=welcome">Hoş geldiniz <?php echo htmlspecialchars($_SESSION['name']); ?></a></li>
                <li><a href="customer.php?page=create_ticket">Talep Oluştur</a></li>
                <li><a href="customer.php?page=my_tickets">Taleplerim</a></li>
                <li><a href="logout.php">Çıkış Yap</a></li>
            </ul>
        </nav>

        <div class="content">
            <?php
            switch($page) {
                case 'welcome':
                    echo '<h1>Hoş Geldiniz</h1>';
                    echo '<p>Sol menüden işlem seçebilirsiniz.</p>';
                    break;

                case 'create_ticket':
                    ?>
                    <h1>Yeni Talep Oluştur</h1>
                    <form id="ticketForm">
                        <div class="form-group">
                            <label for="title">Başlık:</label>
                            <input type="text" id="title" name="title" required>
                        </div>
                        <div class="form-group">
                            <label for="category">Kategori:</label>
                            <select id="category" name="category_id" required></select>
                        </div>
                        <div class="form-group">
                            <label for="priority">Öncelik:</label>
                            <select id="priority" name="priorities_id" required></select>
                        </div>
                        <div class="form-group">
                            <label for="description">Açıklama:</label>
                            <textarea id="description" name="description" required></textarea>
                        </div>
                        <button type="submit">Talep Oluştur</button>
                    </form>
                    <?php
                    break;

                case 'my_tickets':
                    ?>
                    <h1>Taleplerim</h1>
                    <div id="ticketsContainer"></div>
                    <?php
                    break;

                case 'ticket_details':
                    if (!isset($_GET['ticket_id'])) {
                        header('Location: customer.php?page=my_tickets');
                        exit;
                    }
                    ?>
                    <h1>Talep Detayları</h1>
                    <div id="ticketDetails"></div>
                    <?php
                    break;
            }
            ?>
        </div>
    </div>

    <script>
        // Sayfa yüklendiğinde gerekli işlemleri yap
        document.addEventListener('DOMContentLoaded', async function() {
            const currentPage = '<?php echo $page; ?>';
            
            if (currentPage === 'create_ticket') {
                // Kategori ve öncelikleri yükle
                const [categoriesRes, prioritiesRes] = await Promise.all([
                    fetch('customer.php?action=get_categories'),
                    fetch('customer.php?action=get_priorities')
                ]);
                
                const categories = await categoriesRes.json();
                const priorities = await prioritiesRes.json();
                
                if (categories.success) {
                    const categorySelect = document.getElementById('category');
                    categories.categories.forEach(cat => {
                        categorySelect.innerHTML += `<option value="${cat.category_id}">${cat.category_name}</option>`;
                    });
                }
                
                if (priorities.success) {
                    const prioritySelect = document.getElementById('priority');
                    priorities.priorities.forEach(pri => {
                        prioritySelect.innerHTML += `<option value="${pri.priorities_id}">${pri.priorities_name}</option>`;
                    });
                }
                
                // Form gönderme işlemi
                document.getElementById('ticketForm').onsubmit = async (e) => {
                    e.preventDefault();
                    const formData = new FormData(e.target);
                    formData.append('action', 'create_ticket');
                    
                    try {
                        const response = await fetch('customer.php', {
                            method: 'POST',
                            body: formData
                        });
                        const result = await response.json();
                        
                        if (result.success) {
                            alert('Talep başarıyla oluşturuldu!');
                            window.location.href = 'customer.php?page=my_tickets';
                        } else {
                            alert('Hata: ' + result.error);
                        }
                    } catch (error) {
                        alert('Bir hata oluştu!');
                    }
                };
            }
            
            else if (currentPage === 'my_tickets') {
                // Talepleri yükle
                try {
                    const response = await fetch('customer.php?action=get_my_tickets');
                    const data = await response.json();
                    
                    const container = document.getElementById('ticketsContainer');
                    if (data.success && data.tickets.length > 0) {
                        container.innerHTML = data.tickets.map(ticket => `
                            <div class="ticket-card">
                                <div class="ticket-header">
                                    <h3 class="ticket-title">${ticket.title}</h3>
                                    <span class="priority-badge priority-${ticket.priorities_name.toLowerCase()}">${ticket.priorities_name}</span>
                                </div>
                                <div class="ticket-info">
                                    <p><strong>Talep No:</strong> #${ticket.ticket_id}</p>
                                    <p><strong>Durum:</strong> ${ticket.status_name}</p>
                                    <p><strong>Kategori:</strong> ${ticket.category_name}</p>
                                    <p><strong>Tarih:</strong> ${formatDate(ticket.create_date)}</p>
                                </div>
                                <div class="ticket-actions">
                                    <button onclick="window.location.href='customer.php?page=ticket_details&ticket_id=${ticket.ticket_id}'" class="btn-view">
                                        Detayları Görüntüle
                                    </button>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        container.innerHTML = '<p>Henüz talep bulunmamaktadır.</p>';
                    }
                } catch (error) {
                    console.error('Talepler yüklenirken hata:', error);
                }
            }

            // Talep detayları sayfası için
            else if (currentPage === 'ticket_details') {
                const ticketId = new URLSearchParams(window.location.search).get('ticket_id');
                try {
                    const response = await fetch(`customer.php?action=get_ticket_details&ticket_id=${ticketId}`);
                    const data = await response.json();
                    
                    if (data.success) {
                        const ticket = data.ticket;
                        const container = document.getElementById('ticketDetails');
                        
                        container.innerHTML = `
                            <div class="ticket-details">
                                <div class="ticket-header">
                                    <h2 class="ticket-title">${ticket.title}</h2>
                                    <span class="priority-badge priority-${ticket.priorities_name.toLowerCase()}">${ticket.priorities_name}</span>
                                </div>
                                <div class="ticket-info">
                                    <p><strong>Talep No:</strong> #${ticket.ticket_id}</p>
                                    <p><strong>Durum:</strong> ${ticket.status_name}</p>
                                    <p><strong>Kategori:</strong> ${ticket.category_name}</p>
                                    <p><strong>Tarih:</strong> ${formatDate(ticket.create_date)}</p>
                                </div>
                                <div class="ticket-description">
                                    <h3>Açıklama</h3>
                                    <p>${ticket.description}</p>
                                </div>
                                
                                <div class="responses-section">
                                    <h3>Yanıtlar</h3>
                                    ${data.responses.length > 0 ? 
                                        data.responses.map(response => `
                                            <div class="response-card">
                                                <div class="response-meta">
                                                    <strong>${response.employee_name}</strong> tarafından
                                                    ${formatDate(response.response_date)} tarihinde yanıtlandı
                                                </div>
                                                <div class="response-content">
                                                    ${response.description}
                                                </div>
                                            </div>
                                        `).join('') :
                                        '<p>Henüz yanıt bulunmamaktadır.</p>'
                                    }
                                </div>
                            </div>
                        `;
                    }
                } catch (error) {
                    console.error('Talep detayları yüklenirken hata:', error);
                }
            }
        });

        function formatDate(dateString) {
            return new Date(dateString).toLocaleString('tr-TR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    </script>
</body>
</html>
