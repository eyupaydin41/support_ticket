<?php
// Departmanları getir
$stmt = $conn->query("SELECT * FROM DEPARTMENT ORDER BY department_name");
$departments = $stmt->fetchAll();
?>

<h1>Yeni Müşteri Ekle</h1>

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
            <label for="department_id">Departman:</label>
            <select id="department_id" name="department_id" required>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo $dept['department_id']; ?>">
                        <?php echo htmlspecialchars($dept['department_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" name="add_customer">Müşteri Ekle</button>
    </form>
</div> 