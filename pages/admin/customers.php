<?php
$stmt = $conn->query("
    SELECT u.*, d.department_name 
    FROM USERS u
    LEFT JOIN DEPARTMENT d ON u.department_id = d.department_id
    WHERE u.role_id = 2
    ORDER BY u.user_id
");
$customers = $stmt->fetchAll();
?>

<h1>Müşteriler</h1>

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
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="user_id" value="<?php echo $customer['user_id']; ?>">
                    <button type="submit" name="delete_customer" onclick="return confirm('Bu müşteriyi silmek istediğinizden emin misiniz?')">Sil</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
