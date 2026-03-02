<?php

/**
 * 
 *  KULLANICI GIRIS PANELI
 * 
 */
ob_start();
require_once __DIR__ . '/../includes/functions.php';

$mesaj = "";
// OTURUM KONTROLU
// KULLANICI ZATEN GIRIS YAPMISSA DIREKT ANA SAYFAYA GONDERIR
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

// GIRIS YAPMA MANTIGI (PHP)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? sanitize($_POST['email']) : '';
    $password = $_POST['password'] ?? '';

    if (isset($pdo)) {
        try {
            // VERITABANINDAN KULLANICIYI VE YETKISINI CEK
            $stmt = $pdo->prepare("SELECT id, name, password, is_admin FROM users WHERE email = ?"); //email bilgisi doğrudan sorguya yazılmıyor Prepared statement ile önceden hazırlanıyor
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // SIFRE DOGRULAMA VE OTURUM BASLATMA
            if ($user && password_verify($password, $user['password'])) { //hash ile veritabanındaki şifreyle karşılaştırılıyor
                session_regenerate_id(true); //Guvenlik icin session ID yenileme
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['is_admin'] = $user['is_admin'];
                header("Location: ../index.php");
                exit;
            } else {
                $mesaj = "Hatalı email veya şifre!";
            }
        } catch (PDOException $e) {
            $mesaj = "Sistem hatası!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap | Smart Grocery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /** TASARIM REHBERI: RENK PALETI */
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

        /* Arka Planın Temeli: GRADIENT VE KONTEYNER DUZENI */
        .login-container {
            display: flex;
            height: 100vh;
            width: 100vw;
            background: linear-gradient(135deg, #aecde2ff 0%, #e8f5e9 50%, #fff9c4 100%);
            position: relative;
        }

        /* Sol Taraf - Yüzen Beyaz Panel : FORM KARTI VE GIRIS ALANLARI */
        .login-form-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 20;
            /* Paneli en öne çıkarır */
            position: relative;
        }

        .form-card {
            background: #ffffff;
            width: 85%;
            max-width: 400px;
            padding: 50px 45px;
            border-radius: 30px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.1);
        }

        .form-card h1 {
            font-weight: 800;
            margin-bottom: 8px;
            color: #1A1C1E;
            font-size: 2.2rem;
        }

        /** OZEL INPUT STILI: ALTTAN CIZGILI */
        .form-control {
            border: none;
            border-bottom: 2px solid #f0f0f0;
            border-radius: 0;
            padding: 15px 5px;
            margin-bottom: 25px;
            transition: 0.3s;
            background: #fff !important;
        }

        .form-control:focus {
            box-shadow: none;
            border-color: var(--primary-green);
            outline: none;
        }

        /** BUTON VE SAG TARAF GORSEL ALANI */
        .btn-login {
            background: #1A1C1E;
            color: #fff;
            border-radius: 30px;
            padding: 14px;
            font-weight: 600;
            width: 100%;
            margin-top: 15px;
            border: none;
        }

        /* Sağ Taraf - Sabit Görsel Alanı */
        .login-image-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 10;
        }

        .brand-content {
            text-align: center;
            margin-bottom: 12vh;
        }

        .brand-logo {
            color: var(--primary-green);
            font-size: 4rem;
            font-weight: 800;
        }

        .brand-subtext {
            color: #4CAF50;
            font-size: 1.2rem;
            max-width: 350px;
            margin: 0 auto;
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
                <h1>Merhaba ✨</h1>
                <p class="text-muted mb-4">Lütfen giriş bilgilerinizi girin.</p>

                <?php if ($mesaj): ?>
                    <div class="alert alert-danger py-2 small"><?= $mesaj ?></div>
                <?php endif; ?>

                <form method="POST">
                    <label class="small fw-bold text-uppercase" style="font-size: 0.7rem; color: #777;">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="Email adresiniz" required>

                    <label class="small fw-bold text-uppercase" style="font-size: 0.7rem; color: #777;">Şifre</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>

                    <div class="text-end mb-4">
                        <a href="forgot_password.php" class="small text-decoration-none text-dark fw-bold">Şifrenizi mi unuttunuz?</a>
                    </div>

                    <button type="submit" class="btn btn-login">GİRİŞ YAP</button>
                    <p class="text-center mt-4 small">Hesabınız yok mu? <a href="register.php" class="text-success fw-bold text-decoration-none">Hemen Kaydolun</a></p>
                </form>
            </div>
        </div>

        <div class="login-image-section">
            <div class="brand-content">
                <div class="brand-logo">Smart Grocery</div>
                <p class="brand-subtext">Akıllı alışveriş asistanınız ile fiyatları karşılaştırın, tasarruf edin.</p>
            </div>
            <img src="../assets/img/login-hero.png" class="hero-img" alt="Görsel">
        </div>
    </div>
</body>

</html>