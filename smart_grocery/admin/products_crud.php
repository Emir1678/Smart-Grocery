<?php

/**
 * products_crud.php - URUN YONETIM PANELI(Gorsel ve Market Entegreli)
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

// TEMEL GEREKNSINIMLER VE GUVENLIK
require_once '../includes/functions.php';
require_login();

// Admin kontrolü
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../index.php");
    exit;
}

global $pdo;
$editing_product = null;
$mesaj = "";
$mesaj_turu = "info"; // Mesajın rengini belirlemek için (info, success, danger)

// --- MARKET LİSTESİNİ ÇEK ---
$markets = $pdo->query("SELECT * FROM markets ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Düzenleme modu kontrolü
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $editing_product = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Silme işlemi
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
        $mesaj = "Ürün başarıyla silindi.";
        $mesaj_turu = "success";
    } catch (PDOException $e) {
        $mesaj = "Hata: Ürün silinemedi. Bu ürüne bağlı market fiyatları veya sepet kayıtları olabilir.";
        $mesaj_turu = "danger";
    }
}

// Ekleme veya Güncelleme İşlemi
if (isset($_POST['add_product']) || isset($_POST['update_product'])) {
    $is_update = isset($_POST['update_product']);
    $id = $is_update ? intval($_POST['product_id']) : null;
    $name = sanitize($_POST['name']);
    $category = sanitize($_POST['category']);
    $barcode = sanitize($_POST['barcode']);
    $market_id = isset($_POST['market_id']) ? intval($_POST['market_id']) : null;
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0.00;

    $image_url = $is_update ? sanitize($_POST['current_image_url']) : 'default.jpg';
    $upload_success = true;

    // Resim yükleme
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['product_image']['type'], $allowed_types)) {
            $new_file_name = uniqid('prod_') . '.' . pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION); //aynı isimdeki isim resim yüklendiğinde eskisinin üzerine yazılması engellenir
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], __DIR__ . "/../uploads/products/" . $new_file_name)) {
                $image_url = "uploads/products/" . $new_file_name;
            }
        }
    }

    if ($upload_success) {
        try {
            if ($is_update) {
                // --- GÜNCELLEME ---
                $stmt = $pdo->prepare("UPDATE products SET name = ?, category = ?, barcode = ?, image_url = ? WHERE id = ?");
                $stmt->execute([$name, $category, $barcode, $image_url, $id]);
                $mesaj = "Başarılı: Ürün bilgileri güncellendi.";
                $mesaj_turu = "success";
            } else {
                // --- YENİ EKLEME ---
                $stmt = $pdo->prepare("INSERT INTO products (name, category, barcode, image_url) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $category, $barcode, $image_url]);
                $new_product_id = $pdo->lastInsertId();

                // Market bağlantısı
                if ($market_id && $price > 0) {
                    $pdo->prepare("INSERT INTO market_prices (product_id, market_id, price, stock) VALUES (?, ?, ?, ?)") //ürünün market_prices'a kaydederek hangi markette hangi fiyata satıldığını tanımlıyoruz
                        ->execute([$new_product_id, $market_id, $price, 100]);
                }
                $mesaj = "Başarılı: Ürün eklendi ve markete bağlandı.";
                $mesaj_turu = "success";
            }
        } catch (PDOException $e) {
            // Barkod Çakışması (Duplicate Entry) Kontrolü
            if ($e->errorInfo[1] == 1062) {
                $mesaj = "HATA: '$barcode' barkod numarası zaten başka bir ürüne ait. Lütfen farklı bir barkod giriniz.";
                $mesaj_turu = "danger";
            } else {
                $mesaj = "Veritabanı Hatası: " . $e->getMessage();
                $mesaj_turu = "danger";
            }
        }
    }
}

$products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ürün Yönetimi | Smart Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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

        .dashboard-container {
            display: flex;
            height: 100vh;
            background: linear-gradient(135deg, #e0f2fe 0%, #e8f5e9 50%, #fff9c4 100%);
        }

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

        .nav-item.active {
            background: #f0f7ff;
            color: var(--admin-blue);
            font-weight: 600;
        }

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
        }

        .table img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 10px;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <h2 class="fw-bold mb-5 text-primary">Smart Admin</h2>
            <nav class="flex-grow-1">
                <a href="../index.php" class="nav-item"><i class="fa-solid fa-arrow-left"></i> Siteye Dön</a>
                <a href="products_crud.php" class="nav-item active"><i class="fa-solid fa-box"></i> Ürünler</a>
                <a href="markets_crud.php" class="nav-item"><i class="fa-solid fa-shop"></i> Marketler</a>
                <a href="market_prices_crud.php" class="nav-item"><i class="fa-solid fa-tags"></i> Fiyat/Stok</a>
            </nav>
            <a href="../auth/logout.php" class="nav-item text-danger"><i class="fa-solid fa-power-off"></i> Çıkış</a>
        </div>

        <div class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="fw-bold m-0">Ürün Yönetimi</h1>
                <?php if ($mesaj): ?>
                    <div class="alert alert-<?= $mesaj_turu ?> py-2 px-4 rounded-pill shadow-sm mb-0">
                        <i class="fa-solid fa-<?= $mesaj_turu == 'danger' ? 'triangle-exclamation' : 'circle-check' ?> me-2"></i>
                        <?= $mesaj ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="content-panel mb-4">
                <h5 class="fw-bold mb-4 <?= $editing_product ? 'text-warning' : 'text-primary' ?>">
                    <i class="fa-solid fa-<?= $editing_product ? 'pen-to-square' : 'plus-circle' ?> me-2"></i>
                    <?= $editing_product ? 'Ürünü Düzenle' : 'Hızlı Ürün Ekle' ?>
                </h5>
                <form method="POST" enctype="multipart/form-data" class="row g-3">
                    <?php if ($editing_product): ?>
                        <input type="hidden" name="product_id" value="<?= $editing_product['id'] ?>">
                        <input type="hidden" name="current_image_url" value="<?= htmlspecialchars($editing_product['image_url']) ?>">
                    <?php endif; ?>

                    <div class="col-md-3">
                        <label class="small fw-bold mb-1">Ürün Adı</label>
                        <input type="text" name="name" class="form-control" required value="<?= $editing_product ? htmlspecialchars($editing_product['name']) : '' ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold mb-1">Kategori</label>
                        <input type="text" name="category" class="form-control" required value="<?= $editing_product ? htmlspecialchars($editing_product['category']) : '' ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold mb-1">Barkod</label>
                        <input type="text" name="barcode" class="form-control" required value="<?= $editing_product ? htmlspecialchars($editing_product['barcode']) : '' ?>">
                    </div>

                    <?php if (!$editing_product): ?>
                        <div class="col-md-2">
                            <label class="small fw-bold mb-1">Market</label>
                            <select name="market_id" class="form-select" required>
                                <option value="">Seçiniz...</option>
                                <?php foreach ($markets as $m): ?>
                                    <option value="<?= $m['id'] ?>"><?= $m['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="small fw-bold mb-1">Fiyat (₺)</label>
                            <input type="number" step="0.01" name="price" class="form-control" required placeholder="0.00">
                        </div>
                    <?php endif; ?>

                    <div class="col-md-10">
                        <label class="small fw-bold mb-1">Ürün Görseli</label>
                        <input type="file" name="product_image" class="form-control">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" name="<?= $editing_product ? 'update_product' : 'add_product' ?>" class="btn btn-primary w-100 py-2 shadow-sm">
                            <?= $editing_product ? 'Güncelle' : 'Kaydet' ?>
                        </button>
                    </div>
                </form>
            </div>

            <div class="content-panel">
                <h5 class="fw-bold mb-4 text-secondary">Kayıtlı Ürünler</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th>Görsel</th>
                                <th>Ürün Detayı</th>
                                <th>Barkod</th>
                                <th class="text-end">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $p): ?>
                                <tr>
                                    <td>
                                        <img src="<?= htmlspecialchars(empty($p['image_url']) || $p['image_url'] == 'default.jpg' ? '../assets/images/placeholder.png' : '../' . $p['image_url']) ?>" alt="Ürün">
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($p['name']) ?></div>
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($p['category']) ?></span>
                                    </td>
                                    <td class="text-muted small"><?= htmlspecialchars($p['barcode']) ?></td>
                                    <td class="text-end">
                                        <a href="?edit=<?= $p['id'] ?>" class="btn btn-sm btn-outline-info rounded-pill me-1"><i class="fa-solid fa-edit"></i></a>
                                        <a href="?delete=<?= $p['id'] ?>" onclick="return confirm('Emin misiniz?')" class="btn btn-sm btn-outline-danger rounded-pill"><i class="fa-solid fa-trash"></i></a>
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