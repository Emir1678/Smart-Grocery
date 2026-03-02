<?php

/**
 * 
 * SIFRE YENILEME SAYFASI
 */



ob_start();
require_once __DIR__ . '/../includes/functions.php';

$mesaj = "";
$mesaj_turu = "danger";

// PHP MANTIGI: Veritabaninda Kullanici Sorgulama
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? sanitize($_POST['email']) : '';

    if (isset($pdo)) {
        // EPOSTA ADRESI KAYITLI MI KONTROLU
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            // Basarili islem simulasyonu
            $mesaj = "Şifre sıfırlama talimatları e-posta adresinize gönderildi. ✨";
            $mesaj_turu = "success";
        } else {
            //Hata bildirimi
            $mesaj = "Bu e-posta adresi sistemimizde kayıtlı değil.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifremi Unuttum | Smart Grocery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /** RENK PALETI */
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

        /** ARKA PLAN: GRADIENT TASARIMI */
        .login-container {
            display: flex;
            height: 100vh;
            width: 100vw;
            background: linear-gradient(135deg, #e0f2fe 0%, #e8f5e9 50%, #fff9c4 100%);
        }

        /** FORM KARTI VE YERLESIM */
        .login-form-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }

        .form-card {
            background: #fff;
            width: 85%;
            max-width: 400px;
            padding: 50px 45px;
            border-radius: 30px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.06);
        }

        .form-card h1 {
            font-weight: 800;
            margin-bottom: 8px;
            color: #1A1C1E;
            font-size: 2rem;
        }

        /** INPUT STILI: ALTTAN CIZILI MODERN GORUNUM */
        .form-control {
            border: none;
            border-bottom: 2px solid #f0f0f0;
            border-radius: 0;
            padding: 15px 5px;
            margin-bottom: 25px;
            transition: 0.3s;
            background: transparent;
        }

        .form-control:focus {
            box-shadow: none;
            border-color: var(--primary-green);
        }

        /** BUTON VE GORSEL ALAN */
        .btn-login {
            background: #1A1C1E;
            color: #fff;
            border-radius: 30px;
            padding: 14px;
            font-weight: 600;
            width: 100%;
            margin-top: 15px;
        }

        .login-image-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .brand-content {
            text-align: center;
            margin-bottom: 12vh;
            z-index: 5;
        }

        .brand-logo {
            color: var(--primary-green);
            font-size: 4rem;
            font-weight: 800;
        }

        .hero-img {
            position: absolute;
            bottom: 0;
            width: 85%;
            max-height: 50%;
            object-fit: contain;
            object-position: bottom;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-form-section">
            <div class="form-card">
                <h1>Şifre Yenileme 🔑</h1>
                <p class="text-muted mb-4">E-posta adresinizi girin, size yardımcı olalım.</p>

                <?php if ($mesaj): ?>
                    <div class="alert alert-<?= $mesaj_turu ?> py-2 small"><?= $mesaj ?></div>
                <?php endif; ?>

                <form method="POST">
                    <label class="small fw-bold text-uppercase" style="font-size: 0.7rem; color: #777;">Email Adresiniz</label>
                    <input type="email" name="email" class="form-control" placeholder="Örn: ali@mail.com" required>

                    <button type="submit" class="btn btn-login">BAĞLANTI GÖNDER</button>
                    <p class="text-center mt-4 small"><a href="login.php" class="text-success fw-bold text-decoration-none"><i class="fa-solid fa-arrow-left me-2"></i>Giriş Sayfasına Dön</a></p>
                </form>
            </div>
        </div>

        <div class="login-image-section">
            <div class="brand-content">
                <div class="brand-logo">Smart Grocery</div>
                <p style="color: #4CAF50;">Güvenliğiniz bizim için önemli.</p>
            </div>
            <img src="../assets/img/login-hero.png" class="hero-img" alt="Smart Grocery">
        </div>
    </div>
</body>

</html>