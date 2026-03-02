<?php
// admin/admin_header.php YONETICI PANELI UST BILGI VE NAVIGASYON
// TUM ADMIN SAYFALARINDA ORTAK OLAN VE YETKI KONTROLLERINI ICERIR
//
require_once __DIR__ . '/../includes/functions.php';
require_login();      //OTURUM KONTROLU
// YETKI KONTROLU
//KULLANICI ADMIN DEGILSE ISLEMI DURDURUR VE HATA MESAJI VERIR
if (!is_admin()) {
    die("Bu sayfaya erişim izniniz yok!");
}

global $pdo; // PDO bağlantısını kullanıyoruz(VERITABANI BAGLANTISI)

// Başlık ve menü için verileri al
$page_title = $page_title ?? "Yönetici Paneli";
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /** OZEL NAVIGASYON VE TABLO STILLERI */
        .admin-nav .btn {
            margin-right: 10px;
        }

        .table th,
        .table td {
            vertical-align: middle;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <h2 class="mb-4 text-primary"><i class="fa-solid fa-user-gear"></i> <?= htmlspecialchars($page_title) ?></h2>

        <div class="admin-nav mb-4 pb-3 border-bottom">
            <a href="../index.php" class="btn btn-sm btn-outline-success"><i class="fa-solid fa-arrow-left"></i> Ana Sayfa</a>
            <a href="products_crud.php" class="btn btn-sm btn-<?= ($page_title == 'Ürün Yönetimi') ? 'primary' : 'outline-primary' ?>"><i class="fa-solid fa-box"></i> Ürünler</a>
            <a href="markets_crud.php" class="btn btn-sm btn-<?= ($page_title == 'Market Yönetimi') ? 'primary' : 'outline-primary' ?>"><i class="fa-solid fa-store"></i> Marketler</a>
            <a href="market_prices_crud.php" class="btn btn-sm btn-<?= ($page_title == 'Fiyat/Stok Yönetimi') ? 'primary' : 'outline-primary' ?>"><i class="fa-solid fa-tags"></i> Fiyat/Stok</a>
        </div>

        <?php if (isset($mesaj)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $mesaj ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>