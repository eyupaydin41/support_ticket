<h1>Hoş Geldiniz</h1>
<p>Müşteri panelinize hoş geldiniz. Sol menüden işlem seçebilirsiniz.</p>

<div class="dashboard-stats">
    <?php
    // Açık talep sayısı
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM TICKET 
        WHERE customer_id = ? AND status_id != 4
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $openTickets = $stmt->fetch()['count'];
    
    // Toplam talep sayısı
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM TICKET 
        WHERE customer_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $totalTickets = $stmt->fetch()['count'];
    ?>
    
    <div class="stat-box">
        <h3>Açık Talepleriniz</h3>
        <p class="stat-number"><?php echo $openTickets; ?></p>
    </div>
    
    <div class="stat-box">
        <h3>Toplam Talepleriniz</h3>
        <p class="stat-number"><?php echo $totalTickets; ?></p>
    </div>
</div> 