<?php
$stmt = $conn->prepare("CALL GetPendingResponseManager()");
$stmt->execute();
$responses = $stmt->fetchAll();
?>

<h1>Onay Bekleyen Yanıtlar</h1>

<?php if (empty($responses)): ?>
    <p class="pending-responses-message">Şu anda onay bekleyen yanıt bulunmamaktadır.</p>
<?php else: ?>
    <div class="pending-responses">
        <?php foreach ($responses as $response): ?>
            <div class="pending-response-card">
                <div class="pending-response-header">
                    <h3 class="pending-response-title"><?php echo htmlspecialchars($response['title']); ?></h3>
                    <span class="pending-response-id">Talep No: #<?php echo $response['ticket_id']; ?></span>
                </div>

                <div class="pending-response-info">
                    <p><strong>Talep Açıklama:</strong> <?php echo nl2br(htmlspecialchars($response['ticket_desc'])); ?></p>
                    <p><strong>Yanıtlayan:</strong> <?php echo htmlspecialchars($response['responder_name']); ?></p>
                    <p><strong>Yanıt:</strong> <?php echo nl2br(htmlspecialchars($response['description'])); ?></p>
                    <p><strong>Tarih:</strong> <?php echo date('d.m.Y H:i', strtotime($response['response_date'])); ?></p>
                </div>

                <div class="pending-response-footer">
                    <div class="pending-response-details-link">
                        <a href="manager.php?page=ticket_details&ticket_id=<?php echo $response['ticket_id']; ?>" 
                            class="btn-view">Talep Detaylarını Gör</a>
                    </div>
                    <div class="pending-response-actions">
                        <form method="POST" class="action-form">
                        <input type="hidden" name="response_id" value="<?php echo $response['response_id']; ?>">
                        <button type="submit" name="approve_response" class="btn-green">Onayla</button>
                        <button type="submit" name="reject_response" class="btn-red">Reddet</button>
                    </form>
                    </div>
                </div>

            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
