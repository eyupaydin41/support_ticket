<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) { // role_id 1 = Admin
    header("Location: login.php");
    exit;
}

try {
    $conn = new PDO("mysql:host=localhost;dbname=tickedsystem", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Bağlantı hatası: " . $e->getMessage());
}

// API Endpoints
if (isset($_GET['action']) || (isset($_POST['action']))) {
    $action = $_GET['action'] ?? $_POST['action'];
    
    switch($action) {
        case 'get_employees':
            try {
                $stmt = $conn->query("
                    SELECT u.*, d.department_name, r.role_name 
                    FROM USERS u
                    LEFT JOIN DEPARTMENT d ON u.department_id = d.department_id
                    LEFT JOIN ROLES r ON u.role_id = r.role_id
                    WHERE u.role_id IN (3, 4)
                    ORDER BY u.user_id
                ");
                echo json_encode(['success' => true, 'employees' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            } catch(PDOException $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;

        case 'add_employee':
            try {
                $stmt = $conn->prepare("
                    INSERT INTO USERS (name, email, password, role_id, department_id)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['email'],
                    password_hash($_POST['password'], PASSWORD_DEFAULT),
                    $_POST['role_id'],
                    $_POST['department_id']
                ]);
                
                // Log kaydı
                $stmt = $conn->prepare("
                    INSERT INTO LOG (user_id, action, action_date)
                    VALUES (?, 'Yeni çalışan eklendi', CURRENT_TIMESTAMP)
                ");
                $stmt->execute([$_SESSION['user_id']]);
                
                echo json_encode(['success' => true]);
            } catch(PDOException $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;

        case 'add_customer':
            try {
                $stmt = $conn->prepare("
                    INSERT INTO USERS (name, email, password, role_id, department_id)
                    VALUES (?, ?, ?, 2, ?)
                ");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['email'],
                    password_hash($_POST['password'], PASSWORD_DEFAULT),
                    $_POST['department_id']
                ]);
                
                // Log kaydı
                $stmt = $conn->prepare("
                    INSERT INTO LOG (user_id, action, action_date)
                    VALUES (?, 'Yeni müşteri eklendi', CURRENT_TIMESTAMP)
                ");
                $stmt->execute([$_SESSION['user_id']]);
                
                echo json_encode(['success' => true]);
            } catch(PDOException $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;

        case 'delete_customer':
            try {
                $stmt = $conn->prepare("DELETE FROM USERS WHERE user_id = ? AND role_id = 2");
                $stmt->execute([$_POST['user_id']]);
                
                // Log kaydı
                $stmt = $conn->prepare("
                    INSERT INTO LOG (user_id, action, action_date)
                    VALUES (?, 'Müşteri silindi', CURRENT_TIMESTAMP)
                ");
                $stmt->execute([$_SESSION['user_id']]);
                
                echo json_encode(['success' => true]);
            } catch(PDOException $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;

        case 'delete_employee':
            try {
                $stmt = $conn->prepare("DELETE FROM USERS WHERE user_id = ? AND role_id IN (3, 4)");
                $stmt->execute([$_POST['user_id']]);
                
                // Log kaydı
                $stmt = $conn->prepare("
                    INSERT INTO LOG (user_id, action, action_date)
                    VALUES (?, 'Çalışan silindi', CURRENT_TIMESTAMP)
                ");
                $stmt->execute([$_SESSION['user_id']]);
                
                echo json_encode(['success' => true]);
            } catch(PDOException $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;
    }
}

$page = $_GET['page'] ?? 'welcome';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="assets/css/admincss.css">
</head>
<body>
    <div class="container">
        <nav class="sidebar">
            <ul>
                <li><a href="admin.php?page=welcome">Hoş geldiniz <?php echo htmlspecialchars($_SESSION['name']); ?></a></li>
                <li><a href="admin.php?page=employees">Çalışanlar</a></li>
                <li><a href="admin.php?page=customers">Müşteriler</a></li>
                <li><a href="admin.php?page=add_employee">Yeni Çalışan Ekle</a></li>
                <li><a href="admin.php?page=add_customer">Yeni Müşteri Ekle</a></li>
                <li><a href="log.php">Sistem Logları</a></li>
                <li><a href="logout.php">Çıkış Yap</a></li>
            </ul>
        </nav>

        <div class="content">
            <?php
            switch($page) {
                case 'welcome':
                    echo '<h1>Yönetici Paneline Hoş Geldiniz</h1>';
                    echo '<p>Sol menüden yapmak istediğiniz işlemi seçebilirsiniz.</p>';
                    break;

                case 'employees':
                    ?>
                    <h1>Çalışan Listesi</h1>
                    <div id="employeesTable"></div>
                    <?php
                    break;

                case 'customers':
                    ?>
                    <h1>Müşteri Listesi</h1>
                    <div id="customersTable"></div>
                    <?php
                    break;

                case 'add_employee':
                    ?>
                    <h1>Yeni Çalışan Ekle</h1>
                    <form id="addEmployeeForm" class="admin-form">
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
                            <label for="role">Rol:</label>
                            <select id="role" name="role_id" required></select>
                        </div>
                        <div class="form-group">
                            <label for="department">Departman:</label>
                            <select id="department" name="department_id" required></select>
                        </div>
                        <button type="submit" class="btn-submit">Ekle</button>
                    </form>
                    <?php
                    break;

                case 'add_customer':
                    ?>
                    <h1>Yeni Müşteri Ekle</h1>
                    <form id="addCustomerForm" class="admin-form">
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
                            <label for="department">Departman:</label>
                            <select id="department" name="department_id" required></select>
                        </div>
                        <button type="submit" class="btn-submit">Ekle</button>
                    </form>
                    <?php
                    break;
            }
            ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', async function() {
            const currentPage = '<?php echo $page; ?>';
            console.log('Current page:', currentPage); // Debug için

            if (currentPage === 'employees') {
                await loadEmployees();
            }
            else if (currentPage === 'customers') {
                await loadCustomers();
            }
            else if (currentPage === 'add_employee') {
                await Promise.all([loadRoles(), loadDepartments()]);
                setupEmployeeForm();
            }
            else if (currentPage === 'add_customer') {
                await loadDepartments();
                setupCustomerForm();
            }
        });

        async function loadEmployees() {
            try {
                const response = await fetch('admin.php?action=get_employees');
                const data = await response.json();
                
                if (data.success) {
                    const container = document.getElementById('employeesTable');
                    container.innerHTML = `
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Ad Soyad</th>
                                    <th>Email</th>
                                    <th>Departman</th>
                                    <th>Rol</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.employees.map(emp => `
                                    <tr>
                                        <td>${emp.user_id}</td>
                                        <td>${emp.name}</td>
                                        <td>${emp.email}</td>
                                        <td>${emp.department_name || '-'}</td>
                                        <td>${emp.role_name}</td>
                                        <td>
                                            <button class="btn-edit" onclick="editEmployee(${emp.user_id})">Düzenle</button>
                                            <button class="btn-delete" onclick="deleteEmployee(${emp.user_id})">Sil</button>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    `;
                }
            } catch (error) {
                console.error('Çalışanlar yüklenirken hata:', error);
            }
        }

        async function loadCustomers() {
            try {
                const response = await fetch('admin.php?action=get_customers');
                const data = await response.json();
                
                if (data.success) {
                    const container = document.getElementById('customersTable');
                    container.innerHTML = `
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
                                ${data.customers.map(customer => `
                                    <tr>
                                        <td>${customer.user_id}</td>
                                        <td>${customer.name}</td>
                                        <td>${customer.email}</td>
                                        <td>${customer.department_name || '-'}</td>
                                        <td>
                                            <button class="btn-edit" onclick="editCustomer(${customer.user_id})">Düzenle</button>
                                            <button class="btn-delete" onclick="deleteCustomer(${customer.user_id})">Sil</button>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    `;
                }
            } catch (error) {
                console.error('Müşteriler yüklenirken hata:', error);
            }
        }

        async function loadRoles() {
            try {
                const response = await fetch('admin.php?action=get_roles');
                const data = await response.json();
                
                if (data.success) {
                    const select = document.getElementById('role');
                    data.roles.forEach(role => {
                        select.innerHTML += `<option value="${role.role_id}">${role.role_name}</option>`;
                    });
                }
            } catch (error) {
                console.error('Roller yüklenirken hata:', error);
            }
        }

        async function loadDepartments() {
            try {
                const response = await fetch('admin.php?action=get_departments');
                const data = await response.json();
                
                if (data.success) {
                    const select = document.getElementById('department');
                    data.departments.forEach(dept => {
                        select.innerHTML += `<option value="${dept.department_id}">${dept.department_name}</option>`;
                    });
                }
            } catch (error) {
                console.error('Departmanlar yüklenirken hata:', error);
            }
        }

        function setupEmployeeForm() {
            const form = document.getElementById('addEmployeeForm');
            if (form) {
                form.onsubmit = async (e) => {
                    e.preventDefault();
                    const formData = new FormData(form);
                    formData.append('action', 'add_employee');

                    try {
                        const response = await fetch('admin.php', {
                            method: 'POST',
                            body: formData
                        });
                        const data = await response.json();

                        if (data.success) {
                            alert('Çalışan başarıyla eklendi!');
                            window.location.href = 'admin.php?page=employees';
                        } else {
                            alert('Hata: ' + data.error);
                        }
                    } catch (error) {
                        alert('Bir hata oluştu!');
                    }
                };
            }
        }

        function setupCustomerForm() {
            const form = document.getElementById('addCustomerForm');
            if (form) {
                form.onsubmit = async (e) => {
                    e.preventDefault();
                    const formData = new FormData(form);
                    formData.append('action', 'add_customer');

                    try {
                        const response = await fetch('admin.php', {
                            method: 'POST',
                            body: formData
                        });
                        const data = await response.json();

                        if (data.success) {
                            alert('Müşteri başarıyla eklendi!');
                            window.location.href = 'admin.php?page=customers';
                        } else {
                            alert('Hata: ' + data.error);
                        }
                    } catch (error) {
                        alert('Bir hata oluştu!');
                    }
                };
            }
        }

        async function editEmployee(userId) {
            // Düzenleme işlemi için gerekli kodlar eklenecek
            alert('Düzenleme özelliği yakında eklenecek!');
        }

        async function deleteEmployee(userId) {
            if (confirm('Bu çalışanı silmek istediğinizden emin misiniz?')) {
                try {
                    const response = await fetch('admin.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=delete_employee&user_id=${userId}`
                    });
                    const data = await response.json();

                    if (data.success) {
                        alert('Çalışan başarıyla silindi!');
                        await loadEmployees(); // Listeyi yenile
                    } else {
                        alert('Hata: ' + data.error);
                    }
                } catch (error) {
                    alert('Bir hata oluştu!');
                }
            }
        }

        async function editCustomer(userId) {
            // Düzenleme işlemi için gerekli kodlar eklenecek
            alert('Düzenleme özelliği yakında eklenecek!');
        }

        async function deleteCustomer(userId) {
            if (confirm('Bu müşteriyi silmek istediğinizden emin misiniz?')) {
                try {
                    const response = await fetch('admin.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=delete_customer&user_id=${userId}`
                    });
                    const data = await response.json();

                    if (data.success) {
                        alert('Müşteri başarıyla silindi!');
                        await loadCustomers(); // Listeyi yenile
                    } else {
                        alert('Hata: ' + data.error);
                    }
                } catch (error) {
                    alert('Bir hata oluştu!');
                }
            }
        }
    </script>
</body>
</html> 