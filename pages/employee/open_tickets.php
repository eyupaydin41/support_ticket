<?php
// Açık talepleri getir
$stmt = $conn->prepare("CALL GetOpenTickets()");
$stmt->execute();
$tickets = $stmt->fetchAll();
// Açık talepler sayısını getir
$stmt = $conn->prepare("SELECT open_ticket_count FROM OPEN_TICKET_COUNT LIMIT 1");
$stmt->execute();
$ticket_count = $stmt->fetchColumn();
?>



<h1 class="open-tickets-title">Açık Talepler (Toplam: <?php echo $ticket_count; ?>)</h1>

<?php if (empty($tickets)): ?>
    <p>Şu anda açık talep bulunmamaktadır.</p>
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
                    <p><strong>Kategori:</strong> <?php echo htmlspecialchars($ticket['category_name']); ?></p>
                    <p><strong>Tarih:</strong> <?php echo date('d.m.Y H:i', strtotime($ticket['create_date'])); ?></p>
                </div>
                
                <div class="ticket-actions">
                    <a href="employee.php?page=ticket_details&ticket_id=<?php echo $ticket['ticket_id']; ?>" 
                       class="btn-view">Detayları Görüntüle</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?> 