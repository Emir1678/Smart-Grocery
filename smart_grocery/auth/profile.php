<?php

/**
 * auth/profile.php - KULLANICI PROFIL VE GUVENLIK YONETIMI
 */
require_once __DIR__ . '/../includes/functions.php';
require_login();  // OTURUM KONTROLU

global $pdo;
$mesaj = "";
$user_id = current_user_id();

// --- BACKEND: Mevcut Kullanıcı Bilgileri Çekme ---
$stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: logout.php");
    exit;
}

// --- BACKEND: Güncelleme Ve Sifre Dogrulama İşlemi ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_name = sanitize($_POST['name'] ?? '');
    $new_email = sanitize($_POST['email'] ?? '');
    $new_password = $_POST['password'] ?? '';
    $current_password = $_POST['current_password'] ?? '';

    // MEVCUT SIFRE KONTROLU(GUVENLIK BARIYERI)
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $stored_hash = $stmt->fetchColumn();

    if (!password_verify($current_password, $stored_hash)) {
        $mesaj = "Hata: Mevcut şifreniz yanlış!";
    } else {
        $update_fields = [];
        $update_params = [];

        // ISIM DEGISIKLIGI KONTROLU
        if ($new_name !== $user['name']) {
            $update_fields[] = "name = ?";
            $update_params[] = $new_name;
            $_SESSION['user_name'] = $new_name;
        }

        //EMAIL CAKISMA KONTROLU
        if ($new_email !== $user['email']) {
            $check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check_stmt->execute([$new_email, $user_id]);
            if ($check_stmt->fetch()) {
                $mesaj = "Hata: Bu email adresi zaten kullanılıyor.";
            } else {
                $update_fields[] = "email = ?";
                $update_params[] = $new_email;
            }
        }

        //YENI SIFRE KRITER KONTROLU
        if (!empty($new_password)) {
            if (strlen($new_password) < 6) {
                $mesaj = "Hata: Yeni şifre en az 6 karakter olmalı!";
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_fields[] = "password = ?";
                $update_params[] = $hashed_password;
            }
        }

        // VERITABANINA NIHAI KAYIT ISLEMI
        if (empty($mesaj) && !empty($update_fields)) {
            $sql = "UPDATE users SET " . implode(", ", $update_fields) . " WHERE id = ?";
            $update_params[] = $user_id;
            try {
                $stmt = $pdo->prepare($sql);
                $stmt->execute($update_params);
                $user['name'] = $new_name;
                $user['email'] = $new_email;
                $mesaj = "Başarılı: Profil bilgileriniz güncellendi!";
            } catch (PDOException $e) {
                $mesaj = "Hata: Güncelleme sırasında bir sorun oluştu.";
            }
        } elseif (empty($mesaj)) {
            $mesaj = "Uyarı: Herhangi bir değişiklik yapmadınız.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Ayarları | Smart Grocery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /** RENK PALETI VE FONT AYARLARI */
        :root {
            --primary-green: #1B5E20;
        }

        body,
        html {
            height: 100%;
            margin: 0;
            font-family: 'Inter', sans-serif;
            overflow: hidden;
        }

        /** ARKA PLAN: GRADIENT VE DASHBOARD DUZENI */
        .dashboard-container {
            display: flex;
            height: 100vh;
            background: linear-gradient(135deg, #e0f2fe 0%, #e8f5e9 50%, #fff9c4 100%);
        }

        /** SOL SIDEBAR (NAVIGASYON) TASARIMI */
        .sidebar {
            width: 280px;
            background: #fff;
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            box-shadow: 10px 0 30px rgba(0, 0, 0, 0.03);
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 14px 20px;
            margin-bottom: 10px;
            color: #555;
            text-decoration: none;
            border-radius: 15px;
            transition: 0.3s;
        }

        .nav-item i {
            width: 25px;
            margin-right: 15px;
            font-size: 1.1rem;
        }

        .nav-item:hover,
        .nav-item.active {
            background: #E8F5E9;
            color: var(--primary-green);
            font-weight: 600;
        }

        /** ICERIK PANELI VE GLASSMORPHISM */
        .main-content {
            flex: 1;
            overflow-y: auto;
            padding: 40px;
        }

        .content-panel {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(15px);
            border-radius: 40px;
            padding: 40px;
            max-width: 700px;
            margin: 0 auto;
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
        }

        /** OZEL INPUT STILLERI: ALTTAN CIZGILI */
        .form-control {
            border: none;
            border-bottom: 2px solid rgba(0, 0, 0, 0.05);
            border-radius: 0;
            padding: 12px 5px;
            background: transparent !important;
            margin-bottom: 20px;
        }

        .form-control:focus {
            box-shadow: none;
            border-color: var(--primary-green);
        }

        .btn-save {
            background: #1A1C1E;
            color: #fff;
            border-radius: 25px;
            padding: 12px 40px;
            font-weight: 700;
            border: none;
            width: 100%;
            margin-top: 20px;
        }
    </style>
</head>

<body>

    <div class="dashboard-container">
        <div class="sidebar">
            <div class="mb-5 px-3">
                <h2 class="fw-800" style="color: var(--primary-green);">Smart Grocery</h2>
            </div>
            <nav class="flex-grow-1">
                <a href="../index.php" class="nav-item"><i class="fa-solid fa-house"></i> Ana Sayfa</a>
                <a href="../shopping_list.php" class="nav-item"><i class="fa-solid fa-cart-shopping"></i> Sepetim</a>
                <a href="../index.php?favori=1" class="nav-item"><i class="fa-solid fa-heart"></i> Favorilerim</a>
                <a href="profile.php" class="nav-item active"><i class="fa-solid fa-user"></i> Profil Ayarları</a>
            </nav>
            <div class="mt-auto"><a href="logout.php" class="nav-item text-danger"><i class="fa-solid fa-power-off"></i> Çıkış Yap</a></div>
        </div>

        <div class="main-content">
            <div class="text-center mb-4">
                <h1 class="fw-800">Profilimi Güncelle 👤</h1>
                <p class="text-muted">Kişisel bilgilerini ve şifreni buradan yönetebilirsin.</p>
            </div>

            <div class="content-panel">
                <?php if ($mesaj): ?>
                    <div class="alert <?= strpos($mesaj, 'Başarılı') !== false ? 'alert-success' : 'alert-danger' ?> py-2 small shadow-sm"><?= $mesaj ?></div>
                <?php endif; ?>

                <form method="POST">
                    <label class="small fw-bold text-uppercase" style="color: #777;">Ad Soyad / Giriş Adı</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>

                    <label class="small fw-bold text-uppercase" style="color: #777;">E-posta Adresi</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>

                    <hr class="my-4 opacity-5">
                    <h5 class="mb-3 fw-bold">Şifre Değiştir</h5>

                    <label class="small fw-bold text-uppercase" style="color: #777;">Yeni Şifre (İsteğe Bağlı)</label>
                    <input type="password" name="password" class="form-control" placeholder="Değiştirmek istemiyorsan boş bırak">

                    <label class="small fw-bold text-uppercase text-danger" style="color: #777;">Mevcut Şifren (Onay İçin Zorunlu)</label>
                    <input type="password" name="current_password" class="form-control" placeholder="Değişiklikleri kaydetmek için şifreni gir" required>

                    <button type="submit" class="btn btn-save">BİLGİLERİ KAYDET</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>