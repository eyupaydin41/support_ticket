<?php
// Tüm talepleri getir
$stmt = $conn->prepare("
    SELECT t.*, c.category_name, p.priorities_name, s.status_name, 
           u.name as customer_name, d.department_name
    FROM TICKET t
    JOIN CATEGORY c ON t.category_id = c.category_id
    JOIN PRIORITIES p ON t.priorities_id = p.priorities_id
    JOIN STATUS s ON t.status_id = s.status_id
    JOIN USERS u ON t.customer_id = u.user_id
    JOIN
"); 