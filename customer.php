<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 2) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard</title>
    <link rel="stylesheet" href="assets/css/customercss.css">
</head>
<body>
    <div class="container">
        <!-- Sol Menü -->
        <nav class="sidebar">
            <ul>
                <li><a href="#" onclick="showContent('welcome')">Hoş geldiniz <?php echo htmlspecialchars($_SESSION['name']); ?></a></li>
                <li><a href="#" onclick="showContent('create_ticket')">Talep Oluştur</a></li>
                <li><a href="#" onclick="showContent('my_tickets')">Taleplerim</a></li>
                <li><a href="logout.php">Çıkış Yap</a></li>
            </ul>
        </nav>

        <!-- İçerik Alanı -->
        <div class="content" id="content">
            <h1>Müşteri Paneline Hoş Geldiniz</h1>
            <p>Bu panel üzerinden yeni talepler oluşturabilir ve mevcut taleplerinizi görüntüleyebilirsiniz.</p>
        </div>
    </div>

    <script>
        function showContent(contentType) {
            const content = document.getElementById('content');
            if (contentType === 'welcome') {
                content.innerHTML = `
                    <h1>Müşteri Paneline Hoş Geldiniz</h1>
                    <p>Bu panel üzerinden yeni talepler oluşturabilir ve mevcut taleplerinizi görüntüleyebilirsiniz.</p>
                `;
            } else if (contentType === 'create_ticket') {
                content.innerHTML = `
                    <h1>Talep Oluştur</h1>
                    <form action="#" method="POST">
                        <label for="category">Kategori Seçin:</label>
                        <select id="category" name="category" required>
                            <option value="1">Software</option>
                            <option value="2">Hardware</option>
                            <option value="3">Network</option>
                        </select>

                        <label for="title">Başlık:</label>
                        <input type="text" id="title" name="title" placeholder="Talep başlığınızı girin" required>

                        <label for="description">Açıklama:</label>
                        <textarea id="description" name="description" placeholder="Talep açıklamanızı girin" rows="4" required></textarea>

                        <button type="submit">Gönder</button>
                    </form>
                `;
            } else if (contentType === 'my_tickets') {
                content.innerHTML = `
                    <h1>Taleplerim</h1>
                    <p>Henüz oluşturduğunuz bir talep yok.</p>
                `;
            }
        }
    </script>
</body>
</html>
