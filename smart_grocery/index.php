<?php

/**
 * index.php - SMART GROCERY ANA KONTROL MERKEZİ
 * Projenin ana vitrini; veritabanı bağlantısı, ürün listeleme ve arama burada gerçekleşir.
 */
require_once 'includes/functions.php';
require_login(); // -GİRİŞ KONTROLÜ-

// -VERİTABANI BAĞLANTI AYARLARI-
$host = '127.0.0.1';
$db   = 'proje_db1';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options); // Veritabanı köprüsünü kurar
} catch (\PDOException $e) {
    die("Veritabanı bağlantısı kurulamadı: " . $e->getMessage());
}

$current_user_name = $_SESSION['user_name'] ?? 'Misafir';
$user_id = current_user_id();
$is_admin = (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1);

// -BACKEND: ARAMA VE FİLTRELEME MANTIĞI-
$search_query = isset($_GET['q']) ? sanitize($_GET['q']) : '';
$search_term = "%$search_query%";
$filter_favorites = isset($_GET['favori']) && $_GET['favori'] == '1';

// -1. TÜKENEN ÜRÜNLERİ SONA ATAN SIRALAMA ALGORİTMASI-
// Kullanıcı deneyimi için stoğu olan ürünleri her zaman en üstte gösterir.
$sort_option = $_GET['sort'] ?? 'price_asc';
switch ($sort_option) {
    case 'price_desc':
        $order_by = "ORDER BY (mp.stock > 0) DESC, (mp.price * (1 - mp.discount_rate/100)) DESC";
        break;
    case 'name_asc':
        $order_by = "ORDER BY (mp.stock > 0) DESC, p.name ASC";
        break;
    default: // price_asc
        $order_by = "ORDER BY (mp.stock > 0) DESC, (mp.price * (1 - mp.discount_rate/100)) ASC";
}
// -MARKETLERE GÖRE DIŞ GÖRÜNÜM (Badge) RENKLERİ-
function getStoreColor($storeName)
{
    $fixedColors = [
        'Migros' => 'text-white" style="background-color: #FF7F00;',
        'BİM' => 'bg-danger text-white',
        'Şok Market' => 'bg-warning text-dark',
        'A101' => 'bg-info text-dark',
        'AVM' => 'bg-success text-white'
    ];
    return $fixedColors[$storeName] ?? 'text-white" style="background-color: #4CAF50;';
}
// -DİNAMİK SQL SORGU MİMARİSİ (JOIN)-
$sql = "SELECT p.name, p.category, mp.price, mp.stock, m.name AS store_name, 
               mp.id AS market_price_id, p.barcode, p.image_url, mp.discount_rate, p.id AS product_id
        FROM products p
        JOIN market_prices mp ON p.id = mp.product_id   /** products, markets, market_prices birleştirilerek kullanıcıya karşılaştırmalı liste sunulur */
        JOIN markets m ON mp.market_id = m.id";
// -KOŞULLU FİLTRELEME- (Favoriler, Arama Sorgusu vb.)
$where_clauses = ["mp.stock >= 0"]; // stoğu biten elemanlar en alta gider
if ($filter_favorites) {
    $sql .= " JOIN favorites f ON mp.id = f.market_price_id AND f.user_id = {$user_id} ";
}
if (!empty($search_query)) {
    $where_clauses[] = " (p.name LIKE ? OR p.category LIKE ? OR p.barcode LIKE ?) "; // kullanıcıya arama yerinde hem adıyla hem kategoriyle hem de barkodla arama olanağı sağlanır
}
if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}
$sql .= " " . $order_by;
// -VERİ ÇEKME İŞLEMİ-
try {
    $stmt = $pdo->prepare($sql);
    !empty($search_query) ? $stmt->execute([$search_term, $search_term, $search_term]) : $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $products = [];
}
// -KULLANICIYA ÖZEL FAVORİ LİSTESİNİ ÇEK-
$favorite_market_price_ids = [];
if ($user_id) {
    $fav_stmt = $pdo->prepare("SELECT market_price_id FROM favorites WHERE user_id = ?");
    $fav_stmt->execute([$user_id]);
    $favorite_market_price_ids = $fav_stmt->fetchAll(PDO::FETCH_COLUMN, 0);
}
// -SEPETTEKİ TOPLAM ÜRÜN SAYISINI HESAPLA-
$sepet_sayisi = 0;
if ($user_id) {
    $sepet_stmt = $pdo->prepare("SELECT SUM(quantity) FROM shopping_list WHERE user_id = ? AND status = 'pending'");
    $sepet_stmt->execute([$user_id]);
    $sepet_sayisi = $sepet_stmt->fetchColumn() ?: 0;
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Grocery | Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* -ARAYÜZ STİLLERİ VE RENK PALETİ- */
        :root {
            --primary-green: #1B5E20;
            --accent-green: #4CAF50;
        }

        body,
        html {
            height: 100%;
            margin: 0;
            font-family: 'Inter', sans-serif;
            overflow: hidden;
        }

        /* -DASHBOARD KONTEYNER VE GRADIENT ARKA PLAN- */
        .dashboard-container {
            display: flex;
            height: 100vh;
            background: linear-gradient(135deg, #e0f2fe 0%, #e8f5e9 50%, #fff9c4 100%);
        }

        /* -SIDEBAR (SOL MENÜ) TASARIMI- */
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

        /* -İÇERİK PANELİ VE BUZLU CAM (BLUR) EFEKTİ- */
        .main-content {
            flex: 1;
            overflow-y: auto;
            padding: 40px;
        }

        .content-panel {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(15px);
            border-radius: 40px;
            padding: 35px;
            min-height: 85vh;
            border: 1px solid rgba(255, 255, 255, 0.4);
        }

        /* -ÜRÜN KARTI VE ETKİLEŞİM STİLLERİ- */
        .product-card {
            background: #fff;
            border-radius: 25px;
            border: none;
            transition: 0.3s;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.02);
            height: 100%;
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.08);
        }

        .product-image-wrapper {
            height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
        }

        .product-image {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
        }

        .search-container {
            background: #fff;
            border-radius: 20px;
            padding: 5px 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
        }

        /* -BARKOD SORGULAMA BUTONU VE PANELİ- */
        .barcode-circle-btn {
            width: 50px;
            height: 50px;
            background: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            color: var(--primary-green);
            cursor: pointer;
            transition: 0.3s;
            border: 4px solid #000000ff;
        }

        .barcode-circle-btn:hover {
            transform: scale(1.1);
            background: #E8F5E9;
        }

        #barcodePanel {
            display: none;
            position: fixed;
            top: 100px;
            right: 50px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(15px);
            z-index: 1000;
            padding: 25px;
            border-radius: 30px;
            border: 1px solid rgba(255, 255, 255, 0.5);
            width: 320px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .fav-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255, 255, 255, 0.8);
            border: none;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.3s;
            z-index: 5;
        }

        .user-badge {
            font-size: 0.7rem;
            padding: 3px 10px;
            border-radius: 50px;
            margin-top: 5px;
            display: inline-block;
            font-weight: 700;
            text-transform: uppercase;
        }

        .deals-section {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border: 1px solid #b1dfbb;
            border-radius: 40px;
            padding: 40px;
            margin-bottom: 40px;
        }

        .sort-select {
            border-radius: 20px;
            border: none;
            padding: 10px 20px;
            background: #fff;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
            cursor: pointer;
            height: 50px;
        }

        .hero-slider {
            border-radius: 40px;
            overflow: hidden;
            margin-bottom: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .carousel-item {
            height: 400px;
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .carousel-caption {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            color: #1b5e20;
            border-radius: 30px;
            padding: 30px;
            bottom: 40px;
            left: 5%;
            right: auto;
            text-align: left;
            width: 400px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
    </style>
</head>

<body>

    <div class="dashboard-container">
        <div class="sidebar">
            <div class="mb-5 px-3">
                <h2 class="fw-800" style="color: var(--primary-green); letter-spacing: -1px;">Smart Grocery</h2>
            </div>
            <nav class="flex-grow-1">
                <a href="index.php" class="nav-item <?= !$filter_favorites ? 'active' : '' ?>"><i class="fa-solid fa-house"></i> Ana Sayfa</a>
                <a href="shopping_list.php" class="nav-item"><i class="fa-solid fa-cart-shopping"></i> Sepetim (<?= $sepet_sayisi ?>)</a>
                <a href="index.php?favori=1" class="nav-item <?= $filter_favorites ? 'active' : '' ?>"><i class="fa-solid fa-heart"></i> Favorilerim</a>
                <a href="auth/profile.php" class="nav-item"><i class="fa-solid fa-user"></i> Profil Ayarları</a>
                <?php if ($is_admin): ?><a href="admin/products_crud.php" class="nav-item text-warning fw-bold"><i class="fa-solid fa-shield-halved"></i> Yönetim</a><?php endif; ?>
            </nav>
            <div class="mt-auto px-3 mb-4">
                <div class="fw-bold text-dark mb-1 small"><?= htmlspecialchars($current_user_name) ?></div>
                <span class="user-badge <?= $is_admin ? 'bg-warning text-dark' : 'bg-success text-white' ?>"><?= $is_admin ? 'Sistem Yöneticisi' : 'Standart Kullanıcı' ?></span>
            </div>
            <div class="mt-auto"><a href="auth/logout.php" class="nav-item text-danger"><i class="fa-solid fa-power-off"></i> Çıkış Yap</a></div>
        </div>

        <div class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4 px-2">
                <div>
                    <h1 class="fw-800">Merhaba, <?= htmlspecialchars($current_user_name) ?> ✨</h1>
                    <p class="text-muted">Senin için en iyi fiyatları getirdik.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <select class="sort-select" onchange="window.location.href='index.php?sort=' + this.value + '&q=<?= $search_query ?>'">
                        <option value="price_asc" <?= $sort_option == 'price_asc' ? 'selected' : '' ?>>En Düşük Fiyat</option>
                        <option value="price_desc" <?= $sort_option == 'price_desc' ? 'selected' : '' ?>>En Yüksek Fiyat</option>
                        <option value="name_asc" <?= $sort_option == 'name_asc' ? 'selected' : '' ?>>A'dan Z'ye</option>
                    </select>
                    <div class="search-container d-flex align-items-center mb-0" style="width: 300px; height: 50px;">
                        <form action="" method="GET" class="d-flex align-items-center flex-grow-1 mb-0">
                            <i class="fa-solid fa-magnifying-glass text-muted me-3"></i>
                            <input type="text" name="q" class="form-control border-0 shadow-none" placeholder="Ürün ara..." value="<?= htmlspecialchars($search_query) ?>">
                        </form>
                    </div>
                    <button class="barcode-circle-btn" onclick="toggleBarcodePanel()">
                        <i class="fa-solid fa-barcode fs-4"></i>
                    </button>
                </div>
            </div>

            <div id="barcodePanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold m-0 text-success"><i class="fa-solid fa-barcode me-2"></i>Barkod Sorgula</h6>
                    <button class="btn-close" onclick="toggleBarcodePanel()"></button>
                </div>
                <p class="small text-muted mb-3">Ürün barkod numarasını girerek hızlıca arama yapabilirsin.</p>
                <form action="index.php" method="GET">
                    <input type="text" name="q" class="form-control rounded-pill mb-3" placeholder="Barkod No..." id="barcodeInput">
                    <button type="submit" class="btn btn-success w-100 rounded-pill fw-bold">Ürünü Bul</button>
                </form>
            </div>

            <div id="heroCarousel" class="carousel slide hero-slider" data-bs-ride="carousel">
                <div class="carousel-indicators">
                    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active"></button>
                    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
                    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2"></button>
                </div>
                <div class="carousel-inner">
                    <div class="carousel-item active" style="background-image: url('assets/img/banner1.jpg'); background-position: 40% center; background-repeat: no-repeat; background-color: #fdf5e6;">
                        <div class="carousel-caption d-none d-md-block shadow-lg">
                            <span class="badge bg-success mb-2 px-3 py-2 rounded-pill">Akıllı Alışveriş</span>
                            <h2 class="fw-bold">Bütçeni Koruyan Akıllı Asistanın!</h2>
                            <p class="text-muted small">Tüm marketlerdeki fiyatları karşılaştır, en uygun seçeneği anında bul.</p>
                            <a href="#all-products" class="btn btn-success rounded-pill px-4 fw-bold">Şimdi Keşfet</a>
                        </div>
                    </div>
                    <div class="carousel-item" style="background-image: url('assets/img/banner2.jpg');">
                        <div class="carousel-caption d-none d-md-block shadow-lg">
                            <span class="badge bg-danger mb-2 px-3 py-2 rounded-pill">Taze & Doğal</span>
                            <h2 class="fw-bold">En Taze Ürünler Senin İçin Listelendi</h2>
                            <p class="text-muted small">Meyve, sebze ve temel gıdada en güncel market stokları burada.</p>
                        </div>
                    </div>
                    <div class="carousel-item" style="background-image: url('assets/img/banner3.jpg');">
                        <div class="carousel-caption d-none d-md-block shadow-lg">
                            <span class="badge bg-warning text-dark mb-2 px-3 py-2 rounded-pill">Sana Özel</span>
                            <h2 class="fw-bold">Favori Ürünlerini Takip Et!</h2>
                            <p class="text-muted small">Sevdiğin ürünleri listene ekle, fiyat değişimlerini kaçırma.</p>
                            <a href="index.php?favori=1" class="btn btn-dark rounded-pill px-4 fw-bold">Favorilerime Git</a>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (empty($search_query) && !$filter_favorites): ?>
                <div class="deals-section">
                    <h3 class="fw-bold mb-4 text-dark"><i class="fa-solid fa-exclamation-circle me-2"></i>Fırsatları Kaçırma!</h3>
                    <div class="row g-4">
                        <?php
                        $deals_sql = "SELECT p.name, mp.price, mp.discount_rate, p.image_url, m.name as store_name, mp.id as market_price_id
                                  FROM market_prices mp JOIN products p ON mp.product_id = p.id JOIN markets m ON mp.market_id = m.id
                                  WHERE mp.discount_rate > 0 ORDER BY mp.discount_rate DESC LIMIT 3";
                        $deals = $pdo->query($deals_sql)->fetchAll();
                        foreach ($deals as $deal):
                            $final_price = $deal['price'] * (1 - $deal['discount_rate'] / 100); ?>
                            <div class="col-md-4">
                                <div class="deal-card p-4 bg-white shadow-sm border-0 text-center" style="border-radius: 25px;">
                                    <span class="badge bg-danger rounded-pill mb-3">-%<?= round($deal['discount_rate']) ?> Fırsatı</span>
                                    <div class="product-image-wrapper mb-3"><img src="<?= htmlspecialchars($deal['image_url'] ?: 'default.jpg') ?>" class="product-image"></div>
                                    <h6 class="fw-bold text-truncate mb-1"><?= htmlspecialchars($deal['name']) ?></h6>
                                    <span class="badge <?= getStoreColor($deal['store_name']) ?> mb-3"><?= $deal['store_name'] ?></span>
                                    <div class="d-flex justify-content-center align-items-center gap-3">
                                        <div><small class="text-decoration-line-through text-muted"><?= number_format($deal['price'], 2) ?> ₺</small>
                                            <div class="fw-bold text-success fs-4"><?= number_format($final_price, 2) ?> ₺</div>
                                        </div>
                                        <form action="islem.php" method="POST"><input type="hidden" name="market_price_id" value="<?= $deal['market_price_id'] ?>"><input type="hidden" name="type" value="add_to_list"><button type="submit" class="btn btn-dark rounded-circle"><i class="fa-solid fa-cart-plus"></i></button></form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="content-panel" id="all-products">
                <h4 class="fw-bold mb-4 text-dark">Tüm Ürünler</h4>
                <div class="row g-4">
                    <?php if (!empty($products)): ?>
                        <?php foreach ($products as $row): ?>
                            <div class="col-xl-3 col-lg-4 col-md-6">
                                <div class="card product-card p-3 <?= ($row['stock'] <= 0) ? 'opacity-100' : '' ?>">
                                    <?php $is_favorite = in_array($row['market_price_id'], $favorite_market_price_ids); ?>
                                    <button class="fav-btn favorite-toggle" data-market-price-id="<?= $row['market_price_id'] ?>"><i class="fa-<?= $is_favorite ? 'solid' : 'regular' ?> fa-heart <?= $is_favorite ? 'text-danger' : 'text-muted' ?>"></i></button>
                                    <div class="product-image-wrapper">
                                        <img src="<?= htmlspecialchars(empty($row['image_url']) || $row['image_url'] == 'default.jpg' ? 'assets/images/placeholder.png' : $row['image_url']) ?>" class="product-image">
                                    </div>
                                    <div class="card-body px-0">
                                        <span class="badge mb-2 <?= getStoreColor($row['store_name']) ?>"><?= $row['store_name'] ?></span>
                                        <h6 class="fw-bold text-truncate mb-1"><?= htmlspecialchars($row['name']) ?></h6>

                                        <?php if ($row['stock'] <= 10 && $row['stock'] > 0): ?>
                                            <div class="small fw-bold text-danger mb-2"><i class="fa-solid fa-clock-rotate-left"></i> Son <?= $row['stock'] ?> ürün kaldı!</div>
                                        <?php elseif ($row['stock'] == 0): ?>
                                            <div class="small fw-bold text-muted mb-2"><i class="fa-solid fa-circle-xmark"></i> Tükendi</div>
                                        <?php endif; ?>

                                        <div class="d-flex justify-content-between align-items-center mt-3">
                                            <div>
                                                <?php if (($row['discount_rate'] ?? 0) > 0):
                                                    $final_price = (float)$row['price'] * (1 - (float)$row['discount_rate'] / 100); ?>
                                                    <small class="text-decoration-line-through text-muted small"><?= number_format($row['price'], 2) ?> ₺</small>
                                                    <div class="fw-bold text-success fs-5"><?= number_format($final_price, 2) ?> ₺</div>
                                                <?php else: ?><div class="fw-bold text-success fs-5"><?= number_format($row['price'], 2) ?> ₺</div><?php endif; ?>
                                            </div>
                                            <?php if ($row['stock'] > 0): ?>
                                                <form action="islem.php" method="POST"><input type="hidden" name="market_price_id" value="<?= $row['market_price_id'] ?>"><input type="hidden" name="type" value="add_to_list"><button type="submit" class="btn btn-dark btn-sm rounded-circle" style="width:35px; height:35px;"><i class="fa-solid fa-plus"></i></button></form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?><div class="col-12 text-center py-5">
                            <h4>Ürün bulunamadı...</h4>
                        </div><?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // -BARKOD PANELİNİ AÇMA/KAPAMA FONKSİYONU-
        function toggleBarcodePanel() {
            const panel = document.getElementById('barcodePanel');
            const input = document.getElementById('barcodeInput');
            if (panel.style.display === 'block') {
                panel.style.display = 'none';
            } else {
                panel.style.display = 'block';
                input.focus();
            }
        }

        // -FRONTEND: AJAX İLE FAVORİ YÖNETİMİ-
        // Sayfa yenilenmeden veritabanına istek atar
        document.querySelectorAll('.favorite-toggle').forEach(button => {
            button.addEventListener('click', function() {
                const marketPriceId = this.getAttribute('data-market-price-id');
                const icon = this.querySelector('i');
                fetch('islem.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `type=toggle_favorite&market_price_id=${marketPriceId}`
                    })
                    .then(res => res.json()).then(data => {
                        if (data.status === 'added') {
                            icon.classList.replace('fa-regular', 'fa-solid');
                            icon.classList.replace('text-muted', 'text-danger');
                        } else if (data.status === 'removed') {
                            icon.classList.replace('fa-solid', 'fa-regular');
                            icon.classList.replace('text-danger', 'text-muted');
                            if (window.location.search.includes('favori=1')) {
                                this.closest('.col-xl-3').style.display = 'none';
                            }
                        }
                    });
            });
        });

        // -FRONTEND: AJAX İLE SEPETE ÜRÜN EKLEME-
        // Animasyonlu geri bildirim ile ürünü sepete gönderir
        document.querySelectorAll('form[action="islem.php"]').forEach(form => {
            form.addEventListener('submit', function(e) {
                const typeField = this.querySelector('input[name="type"]');
                if (typeField && typeField.value === 'add_to_list') {
                    e.preventDefault();

                    const formData = new FormData(this);
                    const button = this.querySelector('button[type="submit"]');
                    const originalIcon = button.innerHTML;

                    button.innerHTML = '<i class="fa-solid fa-check"></i>';
                    button.classList.replace('btn-dark', 'btn-success');
                    button.disabled = true;

                    fetch('islem.php', { // kullanıcı bir ürünü favoriye eklediğinde veya sepete eklediğinde sayfa yeniden yüklenmez 
                            method: 'POST', // ajax(fetch api sayesinde) 
                            body: formData
                        })
                        .then(() => {
                            // Sepet sayacını anlık günceller
                            const sepetBadge = document.querySelector('.nav-item[href="shopping_list.php"]');
                            if (sepetBadge) {
                                let currentCount = parseInt(sepetBadge.textContent.match(/\d+/) || 0);
                                sepetBadge.innerHTML = `<i class="fa-solid fa-cart-shopping"></i> Sepetim (${currentCount + 1})`;
                            }
                            setTimeout(() => {
                                button.innerHTML = originalIcon;
                                button.classList.replace('btn-success', 'btn-dark');
                                button.disabled = false;
                            }, 1000);
                        })
                        .catch(err => alert('Hata oluştu!'));
                }
            });
        });
    </script>
</body>

</html>