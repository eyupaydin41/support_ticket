<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 4) { // role_id 4 = Employee
    header("Location: login.php");
    exit;
}

try {
    $conn = new PDO("mysql:host=localhost;dbname=tickedsystem", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Bağlantı hatası: " . $e->getMessage());
}

// Yanıt ekleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_response') {
        try {
            $stmt = $conn->prepare("
                INSERT INTO RESPONSE (ticket_id, employee_id, description, status_id, response_date)
                VALUES (?, ?, ?, 1, CURRENT_TIMESTAMP)
            ");
            
            $stmt->execute([
                $_POST['ticket_id'],
                $_SESSION['user_id'],
                $_POST['description']
            ]);
            
            // Log kaydı
            $stmt = $conn->prepare("
                INSERT INTO LOG (user_id, action, action_date)
                VALUES (?, 'Talebe yanıt verildi', CURRENT_TIMESTAMP)
            ");
            $stmt->execute([$_SESSION['user_id']]);
            
            echo json_encode(['success' => true]);
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
}

// Açık talepleri getirme
if (isset($_GET['action']) && $_GET['action'] === 'get_open_tickets') {
    try {
        $stmt = $conn->query("
            SELECT t.*, c.category_name, s.status_name, p.priorities_name,
                   u.name as customer_name, d.department_name
            FROM TICKET t
            JOIN CATEGORY c ON t.category_id = c.category_id
            JOIN STATUS s ON t.status_id = s.status_id
            JOIN PRIORITIES p ON t.priorities_id = p.priorities_id
            JOIN USERS u ON t.customer_id = u.user_id
            LEFT JOIN DEPARTMENT d ON u.department_id = d.department_id
            WHERE t.status_id = 1
            ORDER BY t.priorities_id DESC, t.create_date ASC
        ");
        
        echo json_encode(['success' => true, 'tickets' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Yanıtlanmış talepleri getirme
if (isset($_GET['action']) && $_GET['action'] === 'get_my_responses') {
    try {
        $stmt = $conn->prepare("
            SELECT r.*, t.title as ticket_title, t.description as ticket_description,
                   s.status_name, u.name as customer_name
            FROM RESPONSE r
            JOIN TICKET t ON r.ticket_id = t.ticket_id
            JOIN STATUS s ON r.status_id = s.status_id
            JOIN USERS u ON t.customer_id = u.user_id
            WHERE r.employee_id = ?
            ORDER BY r.response_date DESC
        ");
        
        $stmt->execute([$_SESSION['user_id']]);
        echo json_encode(['success' => true, 'responses' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// PHP kısmına eklenecek yeni endpoint
if (isset($_GET['action']) && $_GET['action'] === 'get_ticket_details') {
    try {
        // Ticket detaylarını getir
        $stmt = $conn->prepare("
            SELECT t.*, c.category_name, s.status_name, p.priorities_name,
                   u.name as customer_name, d.department_name
            FROM TICKET t
            JOIN CATEGORY c ON t.category_id = c.category_id
            JOIN STATUS s ON t.status_id = s.status_id
            JOIN PRIORITIES p ON t.priorities_id = p.priorities_id
            JOIN USERS u ON t.customer_id = u.user_id
            LEFT JOIN DEPARTMENT d ON u.department_id = d.department_id
            WHERE t.ticket_id = ?
        ");
        $stmt->execute([$_GET['ticket_id']]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

        // Yanıtları getir
        $stmt = $conn->prepare("
            SELECT r.*, u.name as employee_name, s.status_name
            FROM RESPONSE r
            JOIN USERS u ON r.employee_id = u.user_id
            JOIN STATUS s ON r.status_id = s.status_id
            WHERE r.ticket_id = ?
            ORDER BY r.response_date ASC
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Çalışan Paneli</title>
    <link rel="stylesheet" href="assets/css/employeecss.css">
</head>
<body>
    <div class="container">
        <nav class="sidebar">
            <ul>
                <li><a href="#" onclick="showContent('welcome')">Hoş geldiniz <?php echo htmlspecialchars($_SESSION['name']); ?></a></li>
                <li><a href="#" onclick="showContent('open_tickets')">Açık Talepler</a></li>
                <li><a href="#" onclick="showContent('my_responses')">Yanıtlarım</a></li>
                <li><a href="logout.php">Çıkış Yap</a></li>
            </ul>
        </nav>

        <div class="content" id="content">
            <h1>Çalışan Paneline Hoş Geldiniz</h1>
            <p>Sol menüden yapmak istediğiniz işlemi seçebilirsiniz.</p>
        </div>
    </div>

    <script>
        async function showContent(contentType) {
            const content = document.getElementById('content');
            
            switch(contentType) {
                case 'welcome':
                    content.innerHTML = `
                        <h1>Çalışan Paneline Hoş Geldiniz</h1>
                        <p>Sol menüden yapmak istediğiniz işlemi seçebilirsiniz.</p>
                    `;
                    break;
                    
                case 'open_tickets':
                    try {
                        const response = await fetch('employee.php?action=get_open_tickets');
                        const data = await response.json();

                        let html = `
                            <h1>Açık Talepler</h1>
                            <div class="tickets-container">
                        `;

                        if (data.tickets && data.tickets.length > 0) {
                            data.tickets.forEach(ticket => {
                                html += `
                                    <div class="ticket-card ${ticket.priorities_name.toLowerCase()}">
                                        <div class="ticket-header">
                                            <h3>${ticket.title}</h3>
                                            <span class="priority-badge">${ticket.priorities_name}</span>
                                        </div>
                                        <p><strong>Müşteri:</strong> ${ticket.customer_name}</p>
                                        <p><strong>Departman:</strong> ${ticket.department_name || 'Belirtilmemiş'}</p>
                                        <p><strong>Kategori:</strong> ${ticket.category_name}</p>
                                        <p><strong>Açıklama:</strong> ${ticket.description}</p>
                                        <p><strong>Oluşturulma Tarihi:</strong> ${ticket.create_date}</p>
                                        <button onclick="showResponseForm(${ticket.ticket_id})" class="btn btn-respond">
                                            Yanıtla
                                        </button>
                                    </div>
                                `;
                            });
                        } else {
                            html += '<p>Açık talep bulunmamaktadır.</p>';
                        }

                        html += '</div>';
                        content.innerHTML = html;
                    } catch (error) {
                        content.innerHTML = '<p>Talepler yüklenirken bir hata oluştu.</p>';
                    }
                    break;

                case 'my_responses':
                    try {
                        const response = await fetch('employee.php?action=get_my_responses');
                        const data = await response.json();

                        let html = `
                            <h1>Yanıtlarım</h1>
                            <div class="responses-container">
                        `;

                        if (data.responses && data.responses.length > 0) {
                            data.responses.forEach(response => {
                                html += `
                                    <div class="response-card">
                                        <h3>${response.ticket_title}</h3>
                                        <p><strong>Talep:</strong> ${response.ticket_description}</p>
                                        <p><strong>Yanıtım:</strong> ${response.description}</p>
                                        <p><strong>Durum:</strong> ${response.status_name}</p>
                                        <p><strong>Yanıt Tarihi:</strong> ${response.response_date}</p>
                                    </div>
                                `;
                            });
                        } else {
                            html += '<p>Henüz yanıtladığınız talep bulunmamaktadır.</p>';
                        }

                        html += '</div>';
                        content.innerHTML = html;
                    } catch (error) {
                        content.innerHTML = '<p>Yanıtlar yüklenirken bir hata oluştu.</p>';
                    }
                    break;
            }
        }

        function showResponseForm(ticketId) {
            const content = document.getElementById('content');
            content.innerHTML += `
                <div class="modal">
                    <div class="modal-content">
                        <h2>Yanıt Ekle</h2>
                        <form id="responseForm" class="response-form">
                            <input type="hidden" name="ticket_id" value="${ticketId}">
                            <div class="form-group">
                                <label for="description">Yanıtınız:</label>
                                <textarea id="description" name="description" rows="4" required></textarea>
                            </div>
                            <div class="form-buttons">
                                <button type="submit" class="btn btn-submit">Gönder</button>
                                <button type="button" onclick="closeModal()" class="btn btn-cancel">İptal</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;

            document.getElementById('responseForm').onsubmit = async (e) => {
                e.preventDefault();
                const formData = new FormData(e.target);
                formData.append('action', 'add_response');

                try {
                    const response = await fetch('employee.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();

                    if (data.success) {
                        alert('Yanıtınız başarıyla kaydedildi!');
                        closeModal();
                        showContent('open_tickets');
                    } else {
                        alert('Hata: ' + data.error);
                    }
                } catch (error) {
                    alert('Bir hata oluştu!');
                }
            };
        }

        function closeModal() {
            const modal = document.querySelector('.modal');
            if (modal) {
                modal.remove();
            }
        }

        async function viewTicketDetails(ticketId) {
            try {
                const response = await fetch(`employee.php?action=get_ticket_details&ticket_id=${ticketId}`);
                const data = await response.json();

                if (data.success) {
                    const ticket = data.ticket;
                    const responses = data.responses;

                    const content = document.getElementById('content');
                    content.innerHTML = `
                        <div class="ticket-details">
                            <h2>Talep Detayları</h2>
                            <div class="ticket-info">
                                <p><strong>Talep No:</strong> ${ticket.ticket_id}</p>
                                <p><strong>Başlık:</strong> ${ticket.title}</p>
                                <p><strong>Müşteri:</strong> ${ticket.customer_name}</p>
                                <p><strong>Departman:</strong> ${ticket.department_name || '-'}</p>
                                <p><strong>Kategori:</strong> ${ticket.category_name}</p>
                                <p><strong>Öncelik:</strong> ${ticket.priorities_name}</p>
                                <p><strong>Durum:</strong> ${ticket.status_name}</p>
                                <p><strong>Oluşturulma Tarihi:</strong> ${ticket.create_date}</p>
                                <p><strong>Açıklama:</strong></p>
                                <div class="ticket-description">${ticket.description}</div>
                            </div>

                            <h3>Yanıtlar</h3>
                            <div class="responses-container">
                                ${responses.map(response => `
                                    <div class="response-card ${response.employee_id == ${SESSION['user_id']} ? 'my-response' : ''}">
                                        <p><strong>Yanıtlayan:</strong> ${response.employee_name}</p>
                                        <p><strong>Yanıt:</strong> ${response.description}</p>
                                        <p><strong>Tarih:</strong> ${response.response_date}</p>
                                        <p><strong>Durum:</strong> ${response.status_name}</p>
                                    </div>
                                `).join('')}
                            </div>

                            <div class="new-response">
                                <h3>Yeni Yanıt Ekle</h3>
                                <form id="responseForm" class="response-form">
                                    <input type="hidden" name="ticket_id" value="${ticket.ticket_id}">
                                    <div class="form-group">
                                        <label for="description">Yanıtınız:</label>
                                        <textarea id="description" name="description" rows="4" required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-submit">Yanıt Gönder</button>
                                </form>
                            </div>
                        </div>
                    `;

                    // Yanıt formu submit işlemi
                    document.getElementById('responseForm').onsubmit = async (e) => {
                        e.preventDefault();
                        const formData = new FormData(e.target);
                        formData.append('action', 'add_response');

                        try {
                            const response = await fetch('employee.php', {
                                method: 'POST',
                                body: formData
                            });
                            const data = await response.json();

                            if (data.success) {
                                alert('Yanıtınız başarıyla kaydedildi!');
                                viewTicketDetails(ticketId); // Sayfayı yenile
                            } else {
                                alert('Hata: ' + data.error);
                            }
                        } catch (error) {
                            alert('Bir hata oluştu!');
                        }
                    };
                }
            } catch (error) {
                alert('Talep detayları yüklenirken bir hata oluştu!');
            }
        }
    </script>
</body>
</html> 