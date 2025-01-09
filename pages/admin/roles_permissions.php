<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header("Location: login.php");
    exit;
}

try {
    $conn = new PDO("mysql:host=localhost;dbname=tickedsystem", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("SELECT r.role_name, p.permission_name
                            FROM role_permission rp
                            JOIN role r ON rp.role_id = r.role_id
                            JOIN permission p ON rp.permission_id = p.permission_id
                            ORDER BY r.role_name, p.permission_name");
    $stmt->execute();
    $rolePermissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Bağlantı hatası: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roller ve Yetkiler</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="admin-page">
    <h1 class="admin-title">Roller ve Yetkiler</h1>

    <h2 class="admin-section-title">Roller ve İlgili Yetkiler</h2>
<table class="admin-table">
    <thead>
        <tr>
            <th class="admin-table-header">Rol Adı</th>
            <th class="admin-table-header">Yetki Adı</th>
            <th class="admin-table-header">Sil</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rolePermissions as $row): ?>
        <tr>
            <td class="admin-table-cell"><?php echo htmlspecialchars($row['role_name']); ?></td>
            <td class="admin-table-cell"><?php echo htmlspecialchars($row['permission_name']); ?></td>
            <td class="admin-table-cell">
                <!-- Silme Butonu -->
                <form action="admin.php" method="post">
                    <input type="hidden" name="role_name" value="<?php echo htmlspecialchars($row['role_name']); ?>">
                    <input type="hidden" name="permission_name" value="<?php echo htmlspecialchars($row['permission_name']); ?>">
                    <button type="submit" name="delete_role_permission" class="admin-form-button">Sil</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

    <details class="admin-details">
        <summary class="admin-details-summary">Rol ve Yetki İlişkisi Ekle</summary>
        <form action="admin.php" method="post" class="admin-form">
            <label for="role_id" class="admin-form-label">Rol Seçin:</label>
            <select name="role_id" id="role_id" class="admin-form-input">
                <?php
                $stmt = $conn->prepare("SELECT role_id, role_name FROM role");
                $stmt->execute();
                $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($roles as $role) {
                    echo "<option value='{$role['role_id']}'>{$role['role_name']}</option>";
                }
                ?>
            </select>

            <label for="permission_id" class="admin-form-label">Yetki Seçin:</label>
            <select name="permission_id" id="permission_id" class="admin-form-input">
                <?php
                $stmt = $conn->prepare("SELECT permission_id, permission_name FROM permission");
                $stmt->execute();
                $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($permissions as $permission) {
                    echo "<option value='{$permission['permission_id']}'>{$permission['permission_name']}</option>";
                }
                ?>
            </select>

            <button type="submit" name="assign_permission" class="admin-form-add-button">Ekle</button>
        </form>
    </details>

    <details class="admin-details">
        <summary class="admin-details-summary">Yeni Rol Ekle</summary>
        <form action="admin.php" method="post" class="admin-form">
            <label for="role_name" class="admin-form-label">Rol Adı:</label>
            <input type="text" id="role_name" name="role_name" class="admin-form-input" required>
            <button type="submit" name="add_role" class="admin-form-add-button">Ekle</button>
        </form>
    </details>

    <details class="admin-details">
        <summary class="admin-details-summary">Yeni Yetki Ekle</summary>
        <form action="admin.php" method="post" class="admin-form">
            <label for="permission_name" class="admin-form-label">Yetki Adı:</label>
            <input type="text" id="permission_name" name="permission_name" class="admin-form-input" required>
            <button type="submit" name="add_permission" class="admin-form-add-button">Ekle</button>
        </form>
    </details>
</body>
</html>

