<?php
$stmt = $conn->prepare("
    SELECT r.*, t.ticket_id, t.title, t.description as ticket_desc, p.name
    FROM RESPONSE r
    JOIN TICKET t ON t.ticket_id = r.ticket_id
    JOIN USERS p ON r.employee_id = p.user_id
    JOIN STATUS s ON r.status_id = s.status_id
    WHERE r.status_id = 2
");
$stmt->execute(); // Eksik olan kısı
$responses = $stmt->fetchAll(); 
?>

<h1>Onay Bekleyen Yanıtlar</h1>

<?php if (empty($responses)): ?>
    <p>Şu anda onay bekleyen yanıt bulunmamaktadır.</p>
<?php else: ?>
    <div class="pending-responses-container">
    <?php foreach ($responses as $response): ?>
        <div class="response-card">
            <div class="ticket-info">
                <p><strong>Talep No:</strong> #<?php echo $response['ticket_id']; ?></p>
                <p><strong>Talep Başlık:</strong> <?php echo htmlspecialchars($response['title']); ?></p>
                <p><strong>Talep Açıklama:</strong> <?php echo htmlspecialchars($response['ticket_desc']); ?></p>
                <p><strong>Yanıtlayan:</strong> <?php echo htmlspecialchars($response['name']); ?></p>
                <p><strong>Yanıt:</strong> <?php echo htmlspecialchars($response['description']); ?></p>
                <p><strong>Tarih:</strong> <?php echo date('d.m.Y H:i', strtotime($response['response_date'])); ?></p>
            </div>

            <!-- Form Başlangıcı -->
            <form method="POST">
                <input type="hidden" name="response_id" value="<?php echo $response['response_id']; ?>">
                <button type="submit" name="approve_response">Onayla</button>
            </form>
            <!-- Form Bitişi -->
        </div>
    <?php endforeach; ?>
    </div>

<?php endif; ?> 