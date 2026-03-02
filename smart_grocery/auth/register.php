<?php

/**
 * auth/register.php - Kullanıcı Kayıt Sayfası
 */
require_once __DIR__ . '/../includes/functions.php';

// OTURUM KONTROLU
// KULLANICI ZATEN GIRIS YAPMISSA DIREKT ANA SAYFAYA YONLENDIRIR
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$mesaj = "";
//KAYIT ISLEMI MANTIGI(PHP)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $pdo;
    $name = isset($_POST['name']) ? sanitize($_POST['name']) : '';
    $email = isset($_POST['email']) ? sanitize($_POST['email']) : '';
    $password = $_POST['password'] ?? '';

    //VERI DOGRULAMA
    if (empty($name) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
        $mesaj = "Lütfen tüm alanları geçerli doldurun.";
    } else {
        try {
            // EPOSTA ADRESI SISTEMDE KAYITLI MI KONTROLU
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $mesaj = "Bu e-posta zaten kullanımda.";
            } else {
                //SIFRE GUVENLI HALE GETIRILIR VE KULLANICI KAYDEDILIR
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (name, email, password, is_admin) VALUES (?, ?, ?, 0)";
                if ($pdo->prepare($sql)->execute([$name, $email, $hashed])) {
                    header("Location: login.php?kayit=basarili");
                    exit;
                }
            }
        } catch (PDOException $e) {
            $mesaj = "Teknik bir hata oluştu.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol | Smart Grocery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /** TASARIM REHBERI: RENK PALETI VE FONT AYARLARI */
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

        /** ANA KONTEYNER VE GRADIENT ARKA PLAN */
        .login-container {
            display: flex;
            height: 100vh;
            width: 100vw;
            background: linear-gradient(135deg, #e0f2fe 0%, #e8f5e9 50%, #fff9c4 100%);
        }

        /** SOL TARAF: FORM KARTI TASARIMI */
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
            max-width: 420px;
            padding: 45px;
            border-radius: 30px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.06);
        }

        .form-card h1 {
            font-weight: 800;
            margin-bottom: 8px;
            color: #1A1C1E;
            font-size: 2.2rem;
        }

        /** OZEL INPUT STILI : ALTTAN CIZILI */
        .form-control {
            border: none;
            border-bottom: 2px solid #f0f0f0;
            border-radius: 0;
            padding: 12px 5px;
            margin-bottom: 20px;
            transition: 0.3s;
            background: transparent;
        }

        .form-control:focus {
            box-shadow: none;
            border-color: var(--primary-green);
        }

        /** BUTON VE SAG TARAF GORSEL ALANI */
        .btn-register {
            background: #1A1C1E;
            color: #fff;
            border-radius: 30px;
            padding: 14px;
            font-weight: 600;
            width: 100%;
            margin-top: 15px;
            border: none;
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

        .brand-subtext {
            color: #4CAF50;
            font-size: 1.2rem;
            max-width: 350px;
            margin: 0 auto;
            opacity: 0.8;
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
                <h1>Yeni Hesap Oluştur</h1>
                <p class="text-muted mb-4">Akıllı alışveriş dünyasına katılın</p>

                <?php if ($mesaj): ?>
                    <div class="alert alert-danger py-2 small"><?= $mesaj ?></div>
                <?php endif; ?>

                <form method="POST">
                    <label class="small fw-bold text-uppercase" style="font-size: 0.7rem; color: #777;">Kullanıcı Adı</label>
                    <input type="text" name="name" class="form-control" placeholder="Örn: Ali Yılmaz" required>

                    <label class="small fw-bold text-uppercase" style="font-size: 0.7rem; color: #777;">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="ali@örnek.com" required>

                    <label class="small fw-bold text-uppercase" style="font-size: 0.7rem; color: #777;">Şifre</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>

                    <button type="submit" class="btn btn-register text-uppercase">Kaydı Tamamla</button>
                    <p class="text-center mt-4 small">Zaten üye misiniz? <a href="login.php" class="text-success fw-bold text-decoration-none">Giriş Yapın</a></p>
                </form>
            </div>
        </div>
        <div class="login-image-section">
            <div class="brand-content">
                <div class="brand-logo">Smart Grocery</div>
                <p class="brand-subtext">Tasarrufa başlamak için bir adım kaldı.</p>
            </div>
            <img src="../assets/img/login-hero.png" class="hero-img" alt="Smart Grocery">
        </div>
    </div>
</body>

</html>