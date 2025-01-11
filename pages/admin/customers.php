<?php
$stmt = $conn->query("CALL GetCustomers()");
$customers = $stmt->fetchAll();

$stmt = $conn->prepare("SELECT total_customers FROM customer_count WHERE id = 1");
$stmt->execute();

$total_customers = $stmt->fetchColumn();

?>


<h1 class="stats-header">Müşteriler <span>(Toplam: <?php echo $total_customers; ?>)</span></h1>

<table class="data-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Ad Soyad</th>
            <th>Email</th>
            <th>Departman</th>
            <th>İşlemler</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($customers as $customer): ?>
        <tr>
            <td><?php echo $customer['user_id']; ?></td>
            <td><?php echo htmlspecialchars($customer['name']); ?></td>
            <td><?php echo htmlspecialchars($customer['email']); ?></td>
            <td><?php echo htmlspecialchars($customer['department_name'] ?? '-'); ?></td>
            <td>
                <!-- Düzenle Butonu -->
                <form method="GET" action="admin.php" style="display: inline;">
                    <input type="hidden" name="page" value="edit_customer">
                    <input type="hidden" name="user_id" value="<?php echo $customer['user_id']; ?>">
                    <button type="submit">Düzenle</button>
                </form>
                <!-- Sil Butonu -->
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="user_id" value="<?php echo $customer['user_id']; ?>">
                    <button class="button-red" type="submit" name="delete_customer" onclick="return confirm('Bu müşteriyi silmek istediğinizden emin misiniz?')">Sil</button>
                </form>


            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
