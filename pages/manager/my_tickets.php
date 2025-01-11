<?php
// Yanıtladığım talepleri getir
$stmt = $conn->prepare("CALL GetMyTicketResponsedManager(?)");
$stmt->execute([$_SESSION['user_id']]);
$tickets = $stmt->fetchAll();
?>

<h1>Yanıtladığım Talepler</h1>

<?php if (empty($tickets)): ?>
    <p>Henüz yanıtladığınız talep bulunmamaktadır.</p>
<?php else: ?>
    <div class="tickets-container">
        <?php foreach ($tickets as $ticket): ?>
            <div class="ticket-card">
                <div class="ticket-header">
                    <h3><?php echo htmlspecialchars($ticket['title']); ?></h3>
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
                
                <div class="ticket-actions">
                    <a href="manager.php?page=ticket_details&ticket_id=<?php echo $ticket['ticket_id']; ?>" 
                       class="btn-view">Detayları Görüntüle</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?> 