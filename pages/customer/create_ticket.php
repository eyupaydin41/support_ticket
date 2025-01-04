<?php
// Kategorileri getir
$stmt = $conn->query("SELECT * FROM CATEGORY ORDER BY category_name");
$categories = $stmt->fetchAll();

// Öncelikleri getir
$stmt = $conn->query("SELECT * FROM PRIORITIES ORDER BY priorities_id");
$priorities = $stmt->fetchAll();
?>

<h1>Yeni Talep Oluştur</h1>

<div class="form-container">
    <form method="POST" action="customer.php">
        

        <div class="form-group">
            <label for="category_id">Kategori:</label>
            <select id="category_id" name="category_id" required>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['category_id']; ?>">
                        <?php echo htmlspecialchars($category['category_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="priorities_id">Öncelik:</label>
            <select id="priorities_id" name="priorities_id" required>
                <?php foreach ($priorities as $priority): ?>
                    <option value="<?php echo $priority['priorities_id']; ?>">
                        <?php echo htmlspecialchars($priority['priorities_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="title">Başlık:</label>
            <input type="text" id="title" name="title" required>
        </div>

        <div class="form-group">
            <label for="description">Açıklama:</label>
            <textarea id="description" name="description" rows="5" required></textarea>
        </div>

        <button type="submit" name="create_ticket">Talep Oluştur</button>
    </form>
</div> 