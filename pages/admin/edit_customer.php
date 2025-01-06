<?php
if (isset($_GET['user_id'])) {
    $stmt = $conn->prepare("SELECT * FROM USERS WHERE user_id = ?");
    $stmt->execute([$_GET['user_id']]);
    $customer = $stmt->fetch();

    if (!$customer) {
        die("Müşteri bulunamadı!");
    }
}
?>

<h1>Müşteri Düzenle</h1>

<?php if (isset($error)): ?>
    <div class="error"><?php echo $error; ?></div>
<?php endif; ?>

<form method="POST" class="form-container" action="admin.php">
    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($customer['user_id']); ?>">
    
    <div class="form-group">
        <label>Ad Soyad</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($customer['name']); ?>" required>
    </div>
    
    <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($customer['email']); ?>" required>
    </div>
    
    <div class="form-group">
        <label>Departman</label>
        <select name="department_id" required>
            <option value="1" <?php echo $customer['department_id'] == 1 ? 'selected' : ''; ?>>Satış</option>
            <option value="2" <?php echo $customer['department_id'] == 2 ? 'selected' : ''; ?>>Teknik Destek</option>
            <option value="3" <?php echo $customer['department_id'] == 3 ? 'selected' : ''; ?>>Muhasebe</option>
        </select>
    </div>
    
    <div class="form-group">
        <label>Yeni Şifre (Opsiyonel)</label>
        <input type="password" name="password">
    </div>
    
    <button type="submit" name="edit_customer">Kaydet</button>
</form>