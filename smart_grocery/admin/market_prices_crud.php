<?php

/**
 * market_prices_crud.php - FIYAT VE STOK YONETIM PANELI
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

// YARDIMCI DOSYALAR VE OTURUM KONTROLU
require_once '../includes/functions.php';
require_login();

// ADMIN Yetki Kontrolü
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../index.php");
    exit;
}

global $pdo;
$mesaj = "";
$editing_price = null;

// --- DÜZENLEME MODU: VERITABANINDAKI Seçili fiyat verisini çek ---
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM market_prices WHERE id = ?");
    $stmt->execute([$id]);
    $editing_price = $stmt->fetch(PDO::FETCH_ASSOC);
}

// --- KAYDET / GÜNCELLE İŞLEMİ ---
if (isset($_POST['save_price'])) {
    $product_id = intval($_POST['product_id']);
    $market_id = intval($_POST['market_id']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $discount = floatval($_POST['discount_rate'] ?? 0);
    $id = isset($_POST['price_id']) ? intval($_POST['price_id']) : null;

    try {
        if ($id) {
            // MEVCUT KAYDI GÜNCELLEME
            $pdo->prepare("UPDATE market_prices SET product_id = ?, market_id = ?, price = ?, stock = ?, discount_rate = ? WHERE id = ?")
                ->execute([$product_id, $market_id, $price, $stock, $discount, $id]);
            $mesaj = "Başarılı: Fiyat ve stok bilgisi güncellendi.";
        } else {
            // YENİ FIYAT KAYDI EKLEME
            $pdo->prepare("INSERT INTO market_prices (product_id, market_id, price, stock, discount_rate) VALUES (?, ?, ?, ?, ?)")
                ->execute([$product_id, $market_id, $price, $stock, $discount]);
            $mesaj = "Başarılı: Yeni fiyat kaydı eklendi.";
        }
        header("Location: market_prices_crud.php?mesaj=" . urlencode($mesaj));
        exit;
    } catch (PDOException $e) {
        // SQL Hata Kodu 1062: Duplicate Entry kontrolü
        if ($e->errorInfo[1] == 1062) {
            $hata_mesajı = "Hata: Bu ürün seçilen markette zaten tanımlı! Lütfen mevcut kaydı düzenleyin.";
            header("Location: market_prices_crud.php?mesaj=" . urlencode($hata_mesajı));
        } else {
            die("Veritabanı Hatası: " . $e->getMessage());
        }
        exit;
    }
}

// --- SİLME İŞLEMİ ---
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $pdo->prepare("DELETE FROM market_prices WHERE id = ?")->execute([$id]);
    // YÖNLENDİRME GÜNCELLENDİ
    header("Location: market_prices_crud.php?mesaj=" . urlencode("Kayıt silindi."));
    exit;
}

// ACILAN MENULER ICIN URUN VE MARKET LISTELERINI CEKME
$mesaj = $_GET['mesaj'] ?? $mesaj;
$products_list = $pdo->query("SELECT id, name FROM products ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$markets_list = $pdo->query("SELECT id, name FROM markets ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// ANA LISTE SORGUSU: Urun, market ve fiyat bilgilerini birlestirme (JOIN)
$all_prices = $pdo->query("
    SELECT mp.*, p.name as product_name, m.name as market_name, p.category 
    FROM market_prices mp 
    JOIN products p ON mp.product_id = p.id 
    JOIN markets m ON mp.market_id = m.id 
    ORDER BY mp.id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fiyat Yönetimi | Smart Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* TASARIM AYARLARI VE RENK PALETI */
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

        /* DASHBOARD ANA DIZILIMI */
        .dashboard-container {
            display: flex;
            height: 100vh;
            background: linear-gradient(135deg, #e0f2fe 0%, #e8f5e9 50%, #fff9c4 100%);
        }

        /* SOL SIDEBAR (NAVIGASYON) TASARIMI */
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

        /* ICERIK ALANI VE BLUR EFEKTI */
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
            margin-bottom: 30px;
        }

        /* BUTON VE LINK STILLERI */
        .form-select,
        .form-control {
            border-radius: 12px;
            padding: 10px 15px;
            border: 1px solid #eee;
            background: #fff !important;
        }

        .btn-edit-link {
            color: #0dcaf0;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            margin-right: 10px;
        }

        .btn-delete-link {
            color: #dc3545;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
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
                <a href="markets_crud.php" class="nav-item"><i class="fa-solid fa-shop"></i> Marketler</a>
                <a href="market_prices_crud.php" class="nav-item active"><i class="fa-solid fa-tags"></i> Fiyat/Stok</a>
            </nav>
            <div class="mt-auto"><a href="../auth/logout.php" class="nav-item text-danger"><i class="fa-solid fa-power-off"></i> Çıkış</a></div>
        </div>

        <div class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4 px-2">
                <h1 class="fw-800 m-0">Fiyat ve Stok Yönetimi 🏷️</h1>
                <?php if ($mesaj): ?><div class="alert alert-info py-2 px-4 rounded-pill small shadow-sm"><?= htmlspecialchars($mesaj) ?></div><?php endif; ?>
            </div>

            <div class="content-panel">
                <h5 class="fw-bold mb-4 <?= $editing_price ? 'text-warning' : 'text-primary' ?>">
                    <i class="fa-solid fa-<?= $editing_price ? 'pen-to-square' : 'plus-circle' ?> me-2"></i>
                    <?= $editing_price ? 'Kaydı Güncelle' : 'Yeni Fiyat/Stok Kaydı' ?>
                </h5>
                <form method="POST" class="row g-3 align-items-end">
                    <?php if ($editing_price): ?><input type="hidden" name="price_id" value="<?= $editing_price['id'] ?>"><?php endif; ?>
                    <div class="col-md-3">
                        <label class="small fw-bold mb-1">Ürün Seçin</label>
                        <select name="product_id" class="form-select" required>
                            <?php foreach ($products_list as $pl): ?>
                                <option value="<?= $pl['id'] ?>" <?= $editing_price && $editing_price['product_id'] == $pl['id'] ? 'selected' : '' ?>><?= htmlspecialchars($pl['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold mb-1">Market</label>
                        <select name="market_id" class="form-select" required>
                            <?php foreach ($markets_list as $ml): ?>
                                <option value="<?= $ml['id'] ?>" <?= $editing_price && $editing_price['market_id'] == $ml['id'] ? 'selected' : '' ?>><?= htmlspecialchars($ml['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold mb-1">Fiyat (₺)</label>
                        <input type="number" step="0.01" name="price" class="form-control" required value="<?= $editing_price ? $editing_price['price'] : '' ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold mb-1">İndirim (%)</label>
                        <input type="number" name="discount_rate" class="form-control" value="<?= $editing_price ? $editing_price['discount_rate'] : '0' ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold mb-1">Stok</label>
                        <input type="number" name="stock" class="form-control" required value="<?= $editing_price ? $editing_price['stock'] : '' ?>">
                    </div>
                    <div class="col-md-1 d-flex gap-2">
                        <button type="submit" name="save_price" class="btn btn-<?= $editing_price ? 'warning' : 'primary' ?> w-100 rounded-pill py-2 shadow-sm">
                            <i class="fa-solid fa-save"></i>
                        </button>
                        <?php if ($editing_price): ?>
                            <a href="market_prices_crud.php" class="btn btn-secondary rounded-pill py-2 px-3"><i class="fa-solid fa-xmark"></i></a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="content-panel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle border-0">
                        <thead class="text-muted small text-uppercase">
                            <tr>
                                <th class="border-0">Ürün & Kategori</th>
                                <th class="border-0">Market</th>
                                <th class="border-0">Fiyat</th>
                                <th class="border-0">Stok</th>
                                <th class="border-0 text-end">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_prices as $ap): ?>
                                <tr>
                                    <td class="border-0 fw-bold"><?= htmlspecialchars($ap['product_name']) ?><br><small class="text-muted fw-normal"><?= htmlspecialchars($ap['category']) ?></small></td>
                                    <td class="border-0"><span class="badge bg-light text-dark border px-3"><?= htmlspecialchars($ap['market_name']) ?></span></td>
                                    <td class="border-0 text-success fw-bold"><?= number_format($ap['price'], 2) ?> ₺</td>
                                    <td class="border-0"><?= $ap['stock'] ?> Adet</td>
                                    <td class="border-0 text-end">
                                        <a href="?edit=<?= $ap['id'] ?>" class="btn-edit-link"><i class="fa-solid fa-edit"></i> Düzenle</a>
                                        <a href="?delete=<?= $ap['id'] ?>" class="btn-delete-link" onclick="return confirm('Silinsin mi?')"><i class="fa-solid fa-trash"></i> Sil</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>

</html>