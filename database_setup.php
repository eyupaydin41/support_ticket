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
            create_date DATE NOT NULL,
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
            response_date DATE NOT NULL,
            FOREIGN KEY (ticket_id) REFERENCES TICKET(ticket_id),
            FOREIGN KEY (employee_id) REFERENCES USERS(user_id),
            FOREIGN KEY (status_id) REFERENCES STATUS(status_id)
        );

        -- 10. LOG Tablosu
        CREATE TABLE IF NOT EXISTS LOG (
            log_id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            action TEXT NOT NULL,
            action_date DATE NOT NULL,
            FOREIGN KEY (user_id) REFERENCES USERS(user_id)
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
    ";

    $conn->exec($sql);

    echo "Veritabanı ve tablolar başarıyla oluşturuldu.";
} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage();
}
?>
