<?php
$stmt = $conn->query("SELECT * FROM ROLE WHERE role_id IN (1, 3, 4) ORDER BY role_id");
$roles = $stmt->fetchAll();
?>

<h1>Yeni Çalışan Ekle</h1>

<div class="form-container">
    <form method="POST" action="admin.php">
        <div class="form-group">
            <label for="name">Ad Soyad:</label>
            <input type="text" id="name" name="name" required>
        </div>

        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>

        <div class="form-group">
            <label for="password">Şifre:</label>
            <input type="password" id="password" name="password" required>
        </div>

        <div class="form-group">
            <label for="role_id">Rol:</label>
            <select id="role_id" name="role_id" required>
                <?php foreach ($roles as $role): ?>
                    <option value="<?php echo $role['role_id']; ?>">
                        <?php echo htmlspecialchars($role['role_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" name="add_employee">Çalışan Ekle</button>
    </form>
</div> 