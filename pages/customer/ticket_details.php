<?php
if (!isset($_GET['ticket_id'])) {
    header('Location: customer.php?page=my_tickets');
    exit;
}

// Talep detaylarını getir
$stmt = $conn->prepare("CALL GetTicketDetails(?, ?)");
$stmt->execute([$_GET['ticket_id'], $_SESSION['user_id']]);
$ticket = $stmt->fetch();

if (!$ticket) {
    header('Location: customer.php?page=my_tickets');
    exit;
}

// Yanıtları getir
$stmt = $conn->prepare("CALL GetTicketResponseCustomer(?)");
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
        <p><strong>Durum:</strong> <?php echo htmlspecialchars($ticket['status_name']); ?></p>
        <p><strong>Kategori:</strong> <?php echo htmlspecialchars($ticket['category_name']); ?></p>
        <p><strong>Tarih:</strong> <?php echo date('d.m.Y H:i', strtotime($ticket['create_date'])); ?></p>
    </div>
    
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
        <form method="POST" action="customer.php">
            <input type="hidden" name="ticket_id" value="<?php echo $ticket['ticket_id']; ?>">

            <div class="form-group">
                <label for="response">Yanıtınız:</label>
                <textarea id="response" name="response" rows="5" required></textarea>
            </div>

            <button type="submit" name="add_response">Yanıt Ekle</button>
        </form>
    </div>
</div> 