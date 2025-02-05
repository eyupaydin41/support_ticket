<?php
if (!isset($_GET['ticket_id'])) {
    header('Location: employee.php?page=open_tickets');
    exit;
}

// Talep detaylarını getir
$stmt = $conn->prepare("CALL GetTicketDetailsEmp(?)");
$stmt->execute([$_GET['ticket_id']]);
$ticket = $stmt->fetch();

if (!$ticket) {
    header('Location: employee.php?page=all_tickets');
    exit;
}

// Yanıtları getir
$stmt = $conn->prepare("
    SELECT r.*, u.name as employee_name
    FROM RESPONSE r
    JOIN USERS u ON r.employee_id = u.user_id
    WHERE r.ticket_id = ? AND r.status_id = 1
    ORDER BY r.response_date DESC
");
$stmt->execute([$_GET['ticket_id']]);
$responses = $stmt->fetchAll();
?>

<h1>Talep Detayları</h1>

<div class="ticket-details">
    <div class="ticket-header">
        <h2><?php echo htmlspecialchars($ticket['title']); ?></h2>
        <span class="priority-badge priority-<?php echo strtolower($ticket['priorities_name']); ?>">
            <?php echo htmlspecialchars($ticket['priorities_name']); ?>
        </span>
    </div>
    
    <div class="ticket-info">
        <p><strong>Talep No:</strong> #<?php echo $ticket['ticket_id']; ?></p>
        <p><strong>Müşteri:</strong> <?php echo htmlspecialchars($ticket['customer_name']); ?></p>
        <p><strong>Durum:</strong> <?php echo htmlspecialchars($ticket['status_name']); ?></p>
        <p><strong>Kategori:</strong> <?php echo htmlspecialchars($ticket['category_name']); ?></p>
        <p><strong>Tarih:</strong> <?php echo date('d.m.Y H:i', strtotime($ticket['create_date'])); ?></p>
    </div>

        <!-- Talebi Kapat Butonu -->

    
    <div class="ticket-description">
        <h3>Açıklama</h3>
        <p><?php echo nl2br(htmlspecialchars($ticket['description'])); ?></p>
    </div>

    <div class="responses-section">
        <h3>Yanıtlar</h3>
        <?php if (empty($responses)): ?>
            <p>Henüz yanıt bulunmamaktadır.</p>
        <?php else: ?>
            <?php foreach ($responses as $response): ?>
                <div class="response-card">
                    <div class="response-meta">
                        <strong><?php echo htmlspecialchars($response['employee_name']); ?></strong> tarafından
                        <?php echo date('d.m.Y H:i', strtotime($response['response_date'])); ?> tarihinde yanıtlandı
                    </div>
                    <div class="response-content">
                        <?php echo nl2br(htmlspecialchars($response['description'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div class="response-form">
    <h3>Yanıt Ekle</h3>
    <form method="POST" action="employee.php" class="form-container">
        <input type="hidden" name="ticket_id" value="<?php echo $ticket['ticket_id']; ?>">

        <div class="form-group">
            <label for="response">Yanıtınız:</label>
            <textarea id="response" name="response" rows="5" required></textarea>
        </div>

        <div class="form-buttons">
            <!-- Mavi buton -->
            <button type="submit" name="add_response" class="btn-blue">Yanıt Ekle</button>
            <!-- Kırmızı buton -->
            
        </div>
    </form>
    </div>
    
    <form method="POST" action="">
        <input type="hidden" name="ticket_id" value="<?php echo $ticket['ticket_id']; ?>">
        <button type="submit" name="close_ticket" class="btn-red">Talebi Kapat</button>
    </form>
</div> 