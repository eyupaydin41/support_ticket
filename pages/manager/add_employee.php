<?php
// Departmanı otomatik seç
$department_id = $_SESSION['department_id'];

// Rolleri getir (sadece çalışan rolü)
$stmt = $conn->query("SELECT * FROM ROLES WHERE role_id = 4");
$roles = $stmt->fetchAll();
?>

<h1>Yeni Çalışan Ekle</h1>

<div class="form-container">
    <form method="POST" action="manager.php">
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

        <input type="hidden" name="role_id" value="4">
        <input type="hidden" name="department_id" value="<?php echo $department_id; ?>">

        <button type="submit" name="add_employee">Çalışan Ekle</button>
    </form>
</div> 