<?php
// Yanıtladığım talepleri getir
$stmt = $conn->prepare("
    SELECT DISTINCT t.*, c.category_name, p.priorities_name, s.status_name, u.name as customer_name
    FROM TICKET t
    JOIN CATEGORY c ON t.category_id = c.category_id
    JOIN PRIORITIES p ON t.priorities_id = p.priorities_id
    JOIN STATUS s ON t.status_id = s.status_id
    JOIN USERS u ON t.customer_id = u.user_id
    JOIN RESPONSE r ON t.ticket_id = r.ticket_id
    WHERE r.employee_id = ? AND r.status_id = 1
    ORDER BY t.create_date DESC
");
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
                    <a href="employee.php?page=ticket_details&ticket_id=<?php echo $ticket['ticket_id']; ?>" 
                       class="btn-view">Detayları Görüntüle</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?> 