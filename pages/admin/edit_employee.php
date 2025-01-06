<?php
if (isset($_GET['user_id'])) {
    $stmt = $conn->prepare("SELECT * FROM USERS WHERE user_id = ?");
    $stmt->execute([$_GET['user_id']]);
    $employee = $stmt->fetch();

    if (!$employee) {
        die("Çalışan bulunamadı!");
    }
}
?>

<h1>Çalışan Düzenle</h1>

<?php if (isset($error)): ?>
    <div class="error"><?php echo $error; ?></div>
<?php endif; ?>

<form method="POST" class="form-container" action="admin.php">
    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($employee['user_id']); ?>">
    
    <div class="form-group">
        <label>Ad Soyad</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($employee['name']); ?>" required>
    </div>
    
    <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($employee['email']); ?>" required>
    </div>
    
    <div class="form-group">
        <label>Rol</label>
        <select name="role_id" required>
            <option value="1" <?php echo $employee['role_id'] == 1 ? 'selected' : ''; ?>>Admin</option>
            <option value="3" <?php echo $employee['role_id'] == 3 ? 'selected' : ''; ?>>Yönetici</option>
            <option value="4" <?php echo $employee['role_id'] == 4 ? 'selected' : ''; ?>>Çalışan</option>
        </select>
    </div>
    
    <div class="form-group">
        <label>Yeni Şifre (Opsiyonel)</label>
        <input type="password" name="password">
    </div>
    
    <button type="submit" name="edit_employee">Güncelle</button>
</form>