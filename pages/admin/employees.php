<?php
$stmt = $conn->query("CALL GetEmployees()");
$employees = $stmt->fetchAll();
?>

    <h1>Çalışanlar</h1>
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Ad Soyad</th>
                <th>Email</th>
                <th>Rol</th>
                <th>İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($employees as $employee): ?>
            <tr>
                <td><?php echo $employee['user_id']; ?></td>
                <td><?php echo htmlspecialchars($employee['name']); ?></td>
                <td><?php echo htmlspecialchars($employee['email']); ?></td>
                <td><?php echo htmlspecialchars($employee['role_name']); ?></td>
                <td>
                    <form method="GET" action="admin.php" style="display: inline;">
                        <input type="hidden" name="page" value="edit_employee">
                        <input type="hidden" name="user_id" value="<?php echo $employee['user_id']; ?>">
                        <button type="submit">Düzenle</button>
                    </form>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="user_id" value="<?php echo $employee['user_id']; ?>">
                        <button class="button-red" type="submit" name="delete_employee" onclick="return confirm('Bu çalışanı silmek istediğinizden emin misiniz?')">Sil</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
