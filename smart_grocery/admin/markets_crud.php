<?php

/**
 * markets_crud.php - Yan Yana Butonlu Market Yönetimi
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../includes/functions.php';
require_login();  //GİRİŞ KONTROLÜ

//ADMİN YETKİ KONTROLÜ
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../index.php");
    exit;
}

global $pdo;
$mesaj = "";
$editing_market = null;

//DÜZENLENECEK MARKETİ SEÇME
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM markets WHERE id = ?");
    $stmt->execute([$id]);
    $editing_market = $stmt->fetch(PDO::FETCH_ASSOC);
}

// --- KAYDET / GÜNCELLE İŞLEMİ ---
if (isset($_POST['save_market'])) {
    $name = sanitize($_POST['name']);
    $id = isset($_POST['market_id']) ? intval($_POST['market_id']) : null;

    try {
        if ($id) {
            // Mevcut marketi güncelle
            $stmt = $pdo->prepare("UPDATE markets SET name = ? WHERE id = ?");
            $stmt->execute([$name, $id]);
            $mesaj = "Market başarıyla güncellendi.";
        } else {
            // Yeni market ekle
            $stmt = $pdo->prepare("INSERT INTO markets (name) VALUES (?)");
            $stmt->execute([$name]);
            $mesaj = "Market başarıyla eklendi.";
        }
        header("Location: markets_crud.php?status=success&message=" . urlencode($mesaj));
        exit;
    } catch (PDOException $e) {
        // SQL Hata Kodu 1062: Duplicate Entry (Mükerrer Kayıt)
        if ($e->errorInfo[1] == 1062) {
            $hata = "Hata: '$name' isimli bir market zaten sistemde mevcut!";
            header("Location: markets_crud.php?status=error&message=" . urlencode($hata));
        } else {
            // Diğer veritabanı hataları
            die("Veritabanı hatası oluştu: " . $e->getMessage());
        }
        exit;
    }
}

// SİLME İŞLEMİ
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        $pdo->prepare("DELETE FROM markets WHERE id = ?")->execute([$id]);
        $mesaj = "Başarılı: Market silindi.";
    } catch (PDOException $e) {
        $mesaj = "Hata: Market silinemedi.";
    }
}

// LİSTELEME SORGUSU
$mesaj = $_GET['mesaj'] ?? $mesaj;
$markets = $pdo->query("SELECT * FROM markets ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Market Yönetimi | Smart Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /*  RENK PALETİ VE ANA AYARLAR  */
        :root {
            --admin-blue: #0d6efd;
        }

        body,
        html {
            height: 100%;
            margin: 0;
            font-family: 'Inter', sans-serif;
            overflow: hidden;
        }

        /* ARKA PLAN RENK GEÇİŞİ   */
        .dashboard-container {
            display: flex;
            height: 100vh;
            background: linear-gradient(135deg, #e0f2fe 0%, #e8f5e9 50%, #fff9c4 100%);
        }

        /* SOL SIDEBAR TASARIMI */
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

        .nav-item.active {
            background: #f0f7ff;
            color: var(--admin-blue);
            font-weight: 600;
        }

        /* ICERIK PANELI VE BLUR EFEKTI  */
        .main-content {
            flex: 1;
            overflow-y: auto;
            padding: 40px;
        }

        .content-panel {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(15px);
            border-radius: 35px;
            padding: 35px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
            width: 100%;
            margin-bottom: 30px;
        }

        /* FORM VE BUTON STILLERI */
        .form-control {
            border-radius: 12px;
            padding: 12px 15px;
            border: 1px solid #eee;
            background: #fff !important;
        }

        .btn-action {
            border-radius: 12px;
            padding: 12px 25px;
            font-weight: 600;
        }

        /* Buton Grubu Stili */
        .btn-group-custom {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .btn-edit-link {
            color: #0dcaf0;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .btn-delete-link {
            color: #dc3545;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .btn-edit-link:hover,
        .btn-delete-link:hover {
            opacity: 0.7;
        }
    </style>
</head>

<body>

    <div class="dashboard-container">
        <div class="sidebar">
            <div class="mb-5 px-3">
                <h2 class="fw-800" style="color: var(--admin-blue);">Smart Admin</h2>
            </div>
            <nav class="flex-grow-1">
                <a href="../index.php" class="nav-item"><i class="fa-solid fa-arrow-left"></i> Siteye Dön</a>
                <a href="products_crud.php" class="nav-item"><i class="fa-solid fa-box"></i> Ürünler</a>
                <a href="markets_crud.php" class="nav-item active"><i class="fa-solid fa-shop"></i> Marketler</a>
                <a href="market_prices_crud.php" class="nav-item"><i class="fa-solid fa-tags"></i> Fiyat/Stok</a>
            </nav>
            <div class="mt-auto"><a href="../auth/logout.php" class="nav-item text-danger"><i class="fa-solid fa-power-off"></i> Çıkış</a></div>
        </div>

        <div class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4 px-2">
                <h1 class="fw-800 m-0">Market Yönetimi 🏪</h1>
                <?php if ($mesaj): ?>
                    <div class="alert alert-info py-2 px-4 rounded-pill m-0 small shadow-sm"><?= htmlspecialchars($mesaj) ?></div>
                <?php endif; ?>
            </div>

            <div class="content-panel">
                <h5 class="fw-bold mb-4 <?= $editing_market ? 'text-warning' : 'text-primary' ?>">
                    <i class="fa-solid fa-<?= $editing_market ? 'pen-to-square' : 'plus-circle' ?> me-2"></i>
                    <?= $editing_market ? 'Marketi Güncelle' : 'Yeni Market Tanımla' ?>
                </h5>
                <form method="POST" class="row g-3 align-items-end">
                    <?php if ($editing_market): ?>
                        <input type="hidden" name="market_id" value="<?= $editing_market['id'] ?>">
                    <?php endif; ?>
                    <div class="col-md-9">
                        <label class="small fw-bold mb-2 text-muted text-uppercase" style="font-size: 0.7rem;">Market Adı</label>
                        <input type="text" name="name" class="form-control" placeholder="Market Adı (Örn: BİM, Migros, Şok)" required
                            value="<?= $editing_market ? htmlspecialchars($editing_market['name']) : '' ?>">
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" name="save_market" class="btn btn-primary btn-action w-100 shadow-sm">
                            <i class="fa-solid fa-check-circle me-1"></i> <?= $editing_market ? 'Güncelle' : 'Ekle' ?>
                        </button>
                        <?php if ($editing_market): ?>
                            <a href="markets_crud.php" class="btn btn-secondary btn-action shadow-sm"><i class="fa-solid fa-xmark"></i></a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="content-panel">
                <h5 class="fw-bold mb-4 text-secondary">Kayıtlı Marketler</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle border-0">
                        <thead class="text-muted small text-uppercase">
                            <tr>
                                <th class="border-0" style="width: 80px;">ID</th>
                                <th class="border-0">Market İsmi</th>
                                <th class="border-0 text-end" style="width: 250px;">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($markets as $m): ?>
                                <tr>
                                    <td class="border-0 text-muted">#<?= $m['id'] ?></td>
                                    <td class="border-0">
                                        <div class="fw-bold fs-6"><?= htmlspecialchars($m['name']) ?></div>
                                    </td>
                                    <td class="border-0">
                                        <div class="btn-group-custom">
                                            <a href="?edit=<?= $m['id'] ?>" class="btn-edit-link">
                                                <i class="fa-solid fa-edit"></i> Düzenle
                                            </a>
                                            <a href="?delete=<?= $m['id'] ?>" onclick="return confirm('Emin misin?')" class="btn-delete-link">
                                                <i class="fa-solid fa-trash"></i> Sil
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>