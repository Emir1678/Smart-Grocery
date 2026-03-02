<?php

/**
 * shopping_list.php - Geri Dönüşüm Kuralları ve Detaylı Info Entegre Edilmiş Sürüm
 */
require_once __DIR__ . '/includes/functions.php';
require_login(); //guvenlik kontrolu

global $pdo;
$user_id = current_user_id();

// 1. SEPETTEKİ ÜRÜNLERİ ÇEK
//JOIN YAPISI ILE URUN DETAYLARI, MARKET BILGILERI VE STOK DURUMLARI TEK SEFERDE ALINIR
$stmt = $pdo->prepare('
    SELECT sl.id AS sepet_id, sl.quantity, p.name AS product_name, m.name AS market_name,        
           mp.price AS price_at_time, mp.stock AS current_stock, mp.discount_rate, p.image_url
    FROM shopping_list sl
    JOIN market_prices mp ON sl.market_price_id = mp.id       
    JOIN products p ON mp.product_id = p.id 
    JOIN markets m ON mp.market_id = m.id  
    WHERE sl.user_id = ? AND sl.status = "pending"
');
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = 0;
$total_stock_ok = true;  //SIPARIS ONAYLANABILMESI ICIN STOK KONTROL BAYRAGI

// 2. BİLDİRİM MESAJLARI
//islem.php'den DONEN SONUCLARA GORE KULLANICIYA GORSEL GERI BILDIRIM VERILIR
$checkout_message = '';
if (isset($_GET['durum'])) {
    $durumlar = [
        'tamamlandi' => ['success', 'fa-circle-check', 'Alışveriş başarıyla tamamlandı!'],
        'yetersizstok' => ['danger', 'fa-triangle-exclamation', 'Bazı ürünlerde yeterli stok kalmadı.'],
        'silindi' => ['warning', 'fa-trash-can', 'Ürün sepetten çıkarıldı.'],
        'guncellendi' => ['info', 'fa-sync', 'Ürün miktarı güncellendi.'],
        'stok_yetmedi' => ['danger', 'fa-circle-xmark', 'Miktar mevcut stoğu aşıyor!']
    ];
    if (isset($durumlar[$_GET['durum']])) {
        $d = $durumlar[$_GET['durum']];
        $checkout_message = "<div class='alert alert-{$d[0]} shadow-sm'><i class='fa-solid {$d[1]} me-2'></i>{$d[2]}</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sepetim | Smart Grocery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /** TASARIM REHBERI: RENK PALETI VE DASHBOARD DUZENI */
        :root {
            --primary-green: #1B5E20;
            --accent-green: #2e7d32;
        }

        body,
        html {
            height: 100%;
            margin: 0;
            font-family: 'Inter', sans-serif;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
            background: linear-gradient(135deg, #e0f2fe 0%, #e8f5e9 50%, #fff9c4 100%);
        }

        /** SOL SIDEBAR (NAVIGASYON) */
        .sidebar {
            width: 280px;
            background: #fff;
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            box-shadow: 10px 0 30px rgba(0, 0, 0, 0.03);
            position: sticky;
            top: 0;
            height: 100vh;
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

        /** ICERIK PANELI VE SEPET TABLOSU */
        .main-content {
            flex: 1;
            padding: 40px;

        }

        .content-panel {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(15px);
            border-radius: 40px;
            padding: 35px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
        }

        .cart-table td {
            vertical-align: middle;
            padding: 20px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        /** SIPARIS OZETI VE DOGA DOSTU OZELLIKLER */
        .eco-panel {
            background: #f6e1e1ff;
            border-radius: 25px;
            padding: 35px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        }

        .eco-badge {
            background: #e8f5e9;
            color: #df5b22ff;
            border-radius: 12px;
            padding: 35px;
            font-size: 0.85rem;
            border-left: 5px solid #2e7d32;
            position: relative;
        }

        .info-btn {
            color: #872219ff;
            cursor: pointer;
            transition: 0.2s;
            font-size: 1.1rem;
        }

        .info-btn:hover {
            color: #146c43;
            transform: scale(1.1);
        }

        .btn-checkout {
            background: #d58616ff;
            color: #fff;
            border-radius: 20px;
            padding: 15px 40px;
            font-weight: 700;
            border: none;
            width: 100%;
            transition: 0.3s;
        }

        .btn-checkout:hover {
            background: #144517;
            transform: translateY(-2px);
        }

        .empty-cart-icon {
            font-size: 5rem;
            color: #ccc;
            margin-bottom: 20px;
            display: block;
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
                <a href="index.php" class="nav-item"><i class="fa-solid fa-house"></i> Ana Sayfa</a>
                <a href="shopping_list.php" class="nav-item active"><i class="fa-solid fa-cart-shopping"></i> Sepetim</a>
                <a href="index.php?favori=1" class="nav-item"><i class="fa-solid fa-heart"></i> Favorilerim</a>
                <a href="auth/profile.php" class="nav-item"><i class="fa-solid fa-user"></i> Profil Ayarları</a>
            </nav>
            <div class="mt-auto"><a href="auth/logout.php" class="nav-item text-danger"><i class="fa-solid fa-power-off"></i> Çıkış Yap</a></div>
        </div>

        <div class="main-content">
            <div class="mb-4 d-flex justify-content-between align-items-center">
                <h1 class="fw-800 m-0">Sepetim 🛒</h1>
                <span class="badge bg-white text-dark rounded-pill px-4 py-2 shadow-sm"><?= count($cart_items) ?> Ürün</span>
            </div>

            <?= $checkout_message ?>

            <?php if (empty($cart_items)): ?>
                <div class="content-panel text-center py-5">
                    <i class="fa-solid fa-cart-plus empty-cart-icon"></i>
                    <h3 class="fw-bold">Sepetin şu an boş görünüyor</h3>
                    <p class="text-muted mb-4">Taze ve ekonomik ürünleri keşfetmeye başla!</p>
                    <a href="index.php" class="btn btn-success rounded-pill px-5 py-3 fw-bold shadow-sm">Alışverişe Başla</a>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="content-panel h-100">
                            <div class="table-responsive">
                                <table class="table cart-table">
                                    <thead>
                                        <tr class="text-muted small text-uppercase">
                                            <th colspan="2">Ürün & Market</th>
                                            <th>Miktar</th>
                                            <th>Toplam</th>
                                            <th class="text-end">İşlem</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cart_items as $item):
                                            $unit_price = (float)$item['price_at_time'];
                                            if ($item['discount_rate'] > 0) $unit_price *= (1 - $item['discount_rate'] / 100);
                                            $subtotal = $item['quantity'] * $unit_price;
                                            $total += $subtotal;
                                            $stock_ok = $item['current_stock'] >= $item['quantity'];
                                            if (!$stock_ok) $total_stock_ok = false;
                                        ?>
                                            <tr>
                                                <td style="width: 80px;"><img src="<?= htmlspecialchars($item['image_url'] ?: 'default.jpg') ?>" class="rounded-3" width="60"></td>
                                                <td>
                                                    <div class="fw-bold text-dark"><?= htmlspecialchars($item['product_name']) ?></div>
                                                    <small class="text-muted"><i class="fa-solid fa-shop me-1 small"></i><?= htmlspecialchars($item['market_name']) ?></small>
                                                </td>
                                                <td>
                                                    <form action="islem.php" method="POST" class="d-flex align-items-center gap-2">
                                                        <input type="hidden" name="type" value="update_quantity"><input type="hidden" name="sepet_id" value="<?= $item['sepet_id'] ?>">
                                                        <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['current_stock'] ?>" class="form-control form-control-sm text-center fw-bold" style="width: 60px; border-radius: 8px;">
                                                        <button type="submit" class="btn btn-sm btn-light border shadow-sm rounded-3"><i class="fa-solid fa-check text-success"></i></button>
                                                    </form>
                                                </td>
                                                <td class="fw-800 text-dark"><?= number_format($subtotal, 2) ?> ₺</td>
                                                <td class="text-end">
                                                    <form action="islem.php" method="POST"><input type="hidden" name="type" value="remove_from_list"><input type="hidden" name="sepet_id" value="<?= $item['sepet_id'] ?>"><button type="submit" class="btn btn-link text-danger p-0"><i class="fa-solid fa-trash-can fa-lg"></i></button></form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="eco-panel shadow-lg">
                            <h5 class="fw-800 mb-4">Sipariş Özeti</h5>

                            <div class="mb-4">
                                <label class="small fw-bold text-muted mb-3 d-block uppercase">Paketleme Tercihi</label>
                                <div class="form-check mb-2">
                                    <input class="form-check-input pack-option" type="radio" name="pack" id="p1" value="0.50" checked onchange="updateGrandTotal()">
                                    <label class="form-check-label small" for="p1">Plastik Poşet (+0.50 ₺)</label>
                                </div>
                                <div class="form-check mb-3 d-flex align-items-center gap-2">
                                    <input class="form-check-input pack-option" type="radio" name="pack" id="p2" value="5.00" onchange="updateGrandTotal()">
                                    <label class="form-check-label small" for="p2">Doğa Dostu Bez Çanta (+5.00 ₺)</label>
                                    <i class="fa-solid fa-circle-info info-btn" data-bs-toggle="tooltip" title="Nişasta bazlı, doğada 6 ayda çözünen özel materyal."></i>
                                </div>
                            </div>

                            <div class="eco-badge mb-4">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <i class="fa-solid fa-recycle me-1"></i><strong>Geri Dönüşüm Hareketi</strong>
                                        <p class="mb-0 mt-1 x-small opacity-75">Çantanızı teslim edin, iade alın!</p>
                                    </div>
                                    <i class="fa-solid fa-circle-info info-btn"
                                        data-bs-toggle="popover"
                                        data-bs-trigger="hover focus"
                                        title="İade Koşulları"
                                        data-bs-content="1. Sadece Smart Grocery logolu orijinal çantalar kabul edilir. 2. Çantanın hasarsız ve temiz olması şarttır. 3. Her çanta için 5.00 ₺ iade hesabınıza tanımlanır.">
                                    </i>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between mb-2"><span class="text-muted">Ara Toplam:</span> <span class="fw-bold"><?= number_format($total, 2) ?> ₺</span></div>
                            <div class="d-flex justify-content-between mb-2"><span class="text-muted">Paketleme:</span> <span id="pack-fee" class="fw-bold">0.50 ₺</span></div>
                            <hr class="my-3" style="opacity: 0.1;">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <span class="fs-5 fw-bold">Toplam:</span>
                                <span id="grand-total" class="fs-2 fw-800 text-success" data-subtotal="<?= $total ?>"><?= number_format($total + 0.50, 2) ?> ₺</span>
                            </div>

                            <form action="islem.php" method="POST">
                                <input type="hidden" name="type" value="checkout">
                                <button type="submit" class="btn btn-checkout py-3" <?= !$total_stock_ok ? 'disabled' : '' ?>>
                                    <i class="fa-solid fa-shield-check me-2"></i> Siparişi Onayla
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tooltip ve Popover'ları Aktifleştir
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function(el) {
            return new bootstrap.Tooltip(el)
        })

        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
        var popoverList = popoverTriggerList.map(function(el) {
            return new bootstrap.Popover(el)
        })

        function updateGrandTotal() { // islem.php'ye bir checkout isteği gönderilir. update stock = stock - quantity sorgusu çalışır
            const subtotal = parseFloat(document.getElementById('grand-total').getAttribute('data-subtotal'));
            const packFee = parseFloat(document.querySelector('.pack-option:checked').value);

            document.getElementById('pack-fee').innerText = packFee.toFixed(2) + " ₺";
            document.getElementById('grand-total').innerText = (subtotal + packFee).toFixed(2) + " ₺";
        }
    </script>
</body>

</html>