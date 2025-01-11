<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "TickedSystem";

try {
    $conn = new PDO("mysql:host=$servername", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $conn->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    $conn->exec("USE $dbname");

    // SQL komutları
    $sql = "
        -- 1. ROLE Tablosu
        CREATE TABLE IF NOT EXISTS ROLE (
            role_id INT PRIMARY KEY AUTO_INCREMENT,
            role_name VARCHAR(255) NOT NULL
        );

        -- 2. DEPARTMENT Tablosu
        CREATE TABLE IF NOT EXISTS DEPARTMENT (
            department_id INT PRIMARY KEY AUTO_INCREMENT,
            department_name VARCHAR(255) NOT NULL
        );

        -- 3. STATUS Tablosu
        CREATE TABLE IF NOT EXISTS STATUS (
            status_id INT PRIMARY KEY AUTO_INCREMENT,
            status_name VARCHAR(255) NOT NULL
        );

        -- 4. PRIORITIES Tablosu
        CREATE TABLE IF NOT EXISTS PRIORITIES (
            priorities_id INT PRIMARY KEY AUTO_INCREMENT,
            priorities_name VARCHAR(255) NOT NULL
        );

        -- 5. CATEGORY Tablosu
        CREATE TABLE IF NOT EXISTS CATEGORY (
            category_id INT PRIMARY KEY AUTO_INCREMENT,
            category_name VARCHAR(255) NOT NULL
        );

        -- 6. PERMISSION Tablosu
        CREATE TABLE IF NOT EXISTS PERMISSION (
            permission_id INT PRIMARY KEY AUTO_INCREMENT,
            permission_name VARCHAR(255) NOT NULL
        );

        -- 7. USERS Tablosu
        CREATE TABLE IF NOT EXISTS USERS (
            user_id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role_id INT NOT NULL,
            department_id INT,
            FOREIGN KEY (role_id) REFERENCES ROLE(role_id),
            FOREIGN KEY (department_id) REFERENCES DEPARTMENT(department_id)
        );

        -- 8. TICKET Tablosu
        CREATE TABLE IF NOT EXISTS TICKET (
            ticket_id INT PRIMARY KEY AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            create_date DATETIME NOT NULL,
            status_id INT NOT NULL,
            priorities_id INT NOT NULL,
            customer_id INT NOT NULL,
            category_id INT NOT NULL,
            FOREIGN KEY (status_id) REFERENCES STATUS(status_id),
            FOREIGN KEY (priorities_id) REFERENCES PRIORITIES(priorities_id),
            FOREIGN KEY (customer_id) REFERENCES USERS(user_id),
            FOREIGN KEY (category_id) REFERENCES CATEGORY(category_id)
        );

        -- 9. RESPONSE Tablosu
        CREATE TABLE IF NOT EXISTS RESPONSE (
            response_id INT PRIMARY KEY AUTO_INCREMENT,
            ticket_id INT NOT NULL,
            employee_id INT NOT NULL,
            description TEXT NOT NULL,
            status_id INT NOT NULL,
            response_date DATETIME NOT NULL,
            FOREIGN KEY (ticket_id) REFERENCES TICKET(ticket_id),
            FOREIGN KEY (employee_id) REFERENCES USERS(user_id) ON DELETE CASCADE,
            FOREIGN KEY (status_id) REFERENCES STATUS(status_id)
        );

        -- 10. LOG Tablosu
        CREATE TABLE IF NOT EXISTS LOG (
            log_id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            action TEXT NOT NULL,
            action_date DATETIME NOT NULL,
            FOREIGN KEY (user_id) REFERENCES USERS(user_id) ON DELETE CASCADE
        );

        -- 11. ROLE_PERMISSION Tablosu
        CREATE TABLE IF NOT EXISTS ROLE_PERMISSION (
            role_id INT NOT NULL,
            permission_id INT NOT NULL,
            PRIMARY KEY (role_id, permission_id),
            FOREIGN KEY (role_id) REFERENCES ROLE(role_id),
            FOREIGN KEY (permission_id) REFERENCES PERMISSION(permission_id)
        );

        -- Başlangıç verilerini ekleme
        INSERT INTO ROLE (role_name) VALUES 
        ('Admin'), 
        ('Customer'), 
        ('Manager'), 
        ('Employee');

        INSERT INTO DEPARTMENT (department_name) VALUES 
        ('IT'), 
        ('HR'), 
        ('Finance'), 
        ('Sales');

        INSERT INTO STATUS (status_name) VALUES 
        ('Open'), 
        ('In Progress'), 
        ('Closed');

        INSERT INTO PRIORITIES (priorities_name) VALUES 
        ('Low'), 
        ('Medium'), 
        ('High');

        INSERT INTO CATEGORY (category_name) VALUES 
        ('Software'), 
        ('Hardware'), 
        ('Network');
        

        CREATE PROCEDURE GetCustomers()
        BEGIN
            SELECT u.*, d.department_name 
            FROM USERS u
            LEFT JOIN DEPARTMENT d ON u.department_id = d.department_id
            WHERE u.role_id = 2
            ORDER BY u.user_id;
        END;

        CREATE PROCEDURE GetEmployees()
        BEGIN
            SELECT u.*, r.role_name 
            FROM USERS u
            LEFT JOIN ROLE r ON u.role_id = r.role_id
            WHERE u.role_id IN (1, 3, 4)
            ORDER BY u.user_id;
        END;
        
        CREATE PROCEDURE GetRolesPermissions()
        BEGIN
           SELECT r.role_name, p.permission_name
           FROM role_permission rp
           JOIN role r ON rp.role_id = r.role_id
           JOIN permission p ON rp.permission_id = p.permission_id
           ORDER BY r.role_name, p.permission_name;
        END;
        
        CREATE PROCEDURE GetMyTickets(IN customer_id INT)
        BEGIN
            SELECT t.*, c.category_name, p.priorities_name, s.status_name
            FROM TICKET t
            JOIN CATEGORY c ON t.category_id = c.category_id
            JOIN PRIORITIES p ON t.priorities_id = p.priorities_id
            JOIN STATUS s ON t.status_id = s.status_id
            WHERE t.customer_id = customer_id
            ORDER BY t.create_date DESC;
        END;


        CREATE PROCEDURE GetTicketDetails(IN ticket_id INT, IN customerId INT)
        BEGIN
            SELECT t.*, c.category_name, p.priorities_name, s.status_name
            FROM TICKET t
            JOIN CATEGORY c ON t.category_id = c.category_id
            JOIN PRIORITIES p ON t.priorities_id = p.priorities_id
            JOIN STATUS s ON t.status_id = s.status_id
            WHERE t.ticket_id = ticket_id AND t.customer_id = customerId
            ORDER BY t.create_date DESC;
        END;
            
        
        CREATE PROCEDURE GetTicketResponseCustomer(IN ticket_id INT)
        BEGIN
            SELECT r.*, u.name as employee_name
            FROM RESPONSE r
            JOIN USERS u ON r.employee_id = u.user_id
            WHERE r.ticket_id = ticket_id AND r.status_id = 1
            ORDER BY r.response_date DESC;
        END;
        
        
        CREATE PROCEDURE GetMyTicketResponsed(IN employee_id INT)
        BEGIN
            SELECT DISTINCT t.*, c.category_name, p.priorities_name, s.status_name, u.name as customer_name
            FROM TICKET t
            JOIN CATEGORY c ON t.category_id = c.category_id
            JOIN PRIORITIES p ON t.priorities_id = p.priorities_id
            JOIN STATUS s ON t.status_id = s.status_id
            JOIN USERS u ON t.customer_id = u.user_id
            JOIN RESPONSE r ON t.ticket_id = r.ticket_id
            WHERE r.employee_id = employee_id AND r.status_id = 1
            ORDER BY t.create_date DESC;
        END;

        CREATE PROCEDURE GetOpenTickets()
        BEGIN
            SELECT t.*, c.category_name, p.priorities_name, s.status_name, u.name as customer_name
            FROM TICKET t
            JOIN CATEGORY c ON t.category_id = c.category_id
            JOIN PRIORITIES p ON t.priorities_id = p.priorities_id
            JOIN STATUS s ON t.status_id = s.status_id
            JOIN USERS u ON t.customer_id = u.user_id
            WHERE t.status_id IN (1, 2)
            ORDER BY t.priorities_id ASC, t.create_date ASC;
        END;


        CREATE PROCEDURE GetTicketDetailsEmp(IN ticket_id INT)
        BEGIN
            SELECT t.*, c.category_name, p.priorities_name, s.status_name, u.name as customer_name
            FROM TICKET t
            JOIN CATEGORY c ON t.category_id = c.category_id
            JOIN PRIORITIES p ON t.priorities_id = p.priorities_id
            JOIN STATUS s ON t.status_id = s.status_id
            JOIN USERS u ON t.customer_id = u.user_id
            WHERE t.status_id IN (1, 2) AND t.ticket_id = ticket_id
            ORDER BY t.priorities_id ASC, t.create_date ASC;
        END;
        
        CREATE PROCEDURE GetOpenTicketsManager()
        BEGIN
            SELECT t.*, c.category_name, p.priorities_name, s.status_name, u.name as customer_name
            FROM TICKET t
            JOIN CATEGORY c ON t.category_id = c.category_id
            JOIN PRIORITIES p ON t.priorities_id = p.priorities_id
            JOIN STATUS s ON t.status_id = s.status_id
            JOIN USERS u ON t.customer_id = u.user_id
            WHERE t.status_id IN (1, 2)
            ORDER BY t.priorities_id ASC, t.create_date ASC;
        END;

        CREATE PROCEDURE GetMyTicketResponsedManager(IN user_id INT)
        BEGIN
            SELECT DISTINCT t.*, c.category_name, p.priorities_name, s.status_name, u.name as customer_name
            FROM TICKET t
            JOIN CATEGORY c ON t.category_id = c.category_id
            JOIN PRIORITIES p ON t.priorities_id = p.priorities_id
            JOIN STATUS s ON t.status_id = s.status_id
            JOIN USERS u ON t.customer_id = u.user_id
            JOIN RESPONSE r ON t.ticket_id = r.ticket_id
            WHERE r.employee_id = user_id AND r.status_id = 1
            ORDER BY t.create_date DESC;
        END;
        
        CREATE PROCEDURE GetPendingResponseManager()
        BEGIN
           SELECT r.*, t.ticket_id, t.title, t.description AS ticket_desc, 
           u.name AS responder_name
           FROM RESPONSE r
           JOIN TICKET t ON r.ticket_id = t.ticket_id
           JOIN USERS u ON r.employee_id = u.user_id
           WHERE r.status_id = 2
           ORDER BY r.response_date ASC;
        END;

        CREATE PROCEDURE GetTicketDetailsManager(IN ticket_id INT)
        BEGIN
           SELECT t.*, c.category_name, p.priorities_name, s.status_name, 
           u.name as customer_name, d.department_name
           FROM TICKET t
           JOIN CATEGORY c ON t.category_id = c.category_id
           JOIN PRIORITIES p ON t.priorities_id = p.priorities_id
           JOIN STATUS s ON t.status_id = s.status_id
           JOIN USERS u ON t.customer_id = u.user_id
           JOIN DEPARTMENT d ON u.department_id = d.department_id
           WHERE t.ticket_id = ticket_id
           ORDER BY t.create_date DESC;

        END;
        
    ";

    $conn->exec($sql);

    // Şifreyi hash'le
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);

    // Başlangıç admin kullanıcısını ekle
    $stmt = $conn->prepare("INSERT INTO USERS (name, email, password, role_id, department_id) VALUES (:name, :email, :password, :role_id, :department_id)");
    $stmt->execute([
        ':name' => 'Admin User',
        ':email' => 'admin@example.com',
        ':password' => $adminPassword,
        ':role_id' => 1, // Admin rolü
        ':department_id' => 1 // IT departmanı
    ]);

    echo "Veritabanı ve tablolar başarıyla oluşturuldu.";
} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage();
}
?>
