<h1>Hoş Geldiniz</h1>
<p>Çalışan panelinize hoş geldiniz. Sol menüden işlem seçebilirsiniz.</p>

<div class="dashboard-stats">
    <?php
    // Açık talep sayısı
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM TICKET 
        WHERE status_id = 1
    ");
    $stmt->execute();
    $openTickets = $stmt->fetch()['count'];
    
    // Yanıtladığım talep sayısı
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT ticket_id) as count 
        FROM RESPONSE 
        WHERE employee_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $answeredTickets = $stmt->fetch()['count'];
    ?>
    
    <div class="stat-box">
        <h3>Açık Talepler</h3>
        <p class="stat-number"><?php echo $openTickets; ?></p>
    </div>
    
    <div class="stat-box">
        <h3>Yanıtladığım Talepler</h3>
        <p class="stat-number"><?php echo $answeredTickets; ?></p>
    </div>
</div> 