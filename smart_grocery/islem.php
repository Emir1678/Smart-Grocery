<?php

/**
 * islem.php - Arka Plan İşlemleri (Sepet, Stok ve Favori Yönetimi)
 * BU DOSYA SEPET YONETIMI, STOK DUSURME, FAVORI SISTEMI VE TRANSACTION ISLEMLERINI YONETIR
 */

require_once 'includes/functions.php';

// Güvenlik: Tüm işlemler için kullanıcı girişi şarttır
require_login();

global $pdo;

// İşlem türünü belirle (POST veya GET üzerinden)
// ISTEKLERIN HANGI ISLEM GRUBUNA AIT OLDUGUNU POST VEYA GET VERISINDEN GELEN 'TYPE' ILE ANLAR
$islemTuru = $_POST['type'] ?? $_GET['type'] ?? '';

switch ($islemTuru) {

    case 'add_to_list':
        /**
         * Sepete Ürün Ekleme: Ürün zaten varsa miktarı artırır, yoksa yeni kayıt açar.
         */
        $market_price_id = intval($_POST['market_price_id']);
        $user_id = current_user_id();

        if ($market_price_id > 0) {
            // Sepette bekleyen (pending) aynı üründen var mı kontrol et
            $stmt = $pdo->prepare("SELECT id, quantity FROM shopping_list WHERE market_price_id = ? AND user_id = ? AND status = 'pending'"); // 'pending' bir kayıt olup olmadığı bakılır. eğer ürün sepetteyse yeni satır açmak yerine mevcut quantity arttırılır
            $stmt->execute([$market_price_id, $user_id]);
            $existing_item = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing_item) {
                // Mevcut miktarı bir artır
                $new_quantity = $existing_item['quantity'] + 1;
                $stmt = $pdo->prepare("UPDATE shopping_list SET quantity = ? WHERE id = ?");
                $stmt->execute([$new_quantity, $existing_item['id']]);
            } else {
                // Yeni sepet kaydı oluştur
                $stmt = $pdo->prepare("INSERT INTO shopping_list (market_price_id, user_id, quantity) VALUES (?, ?, 1)");
                $stmt->execute([$market_price_id, $user_id]);
            }
        }
        header("Location: index.php?durum=eklendi");
        exit;

    case 'remove_from_list':
        /**
         * Sepetten Ürün Silme
         * SECILI SEPET SATIRINI (id) VERITABANINDAN TAMAMEN SILER
         */
        $sepet_id = intval($_POST['sepet_id']);
        if ($sepet_id > 0) {
            $stmt = $pdo->prepare("DELETE FROM shopping_list WHERE id = ? AND user_id = ?");
            $stmt->execute([$sepet_id, current_user_id()]);
            header("Location: shopping_list.php?durum=silindi");
            exit;
        }
        break;

    case 'update_quantity':
        /**
         * Sepet Miktarı Güncelleme ve Stok Kontrolü
         * KULLANICI SEPETINDE SAYI DEGISTIRIRKEN MARKETIN GUNCEL STOGUNU ASMAMASINI SAGLAR
         */
        $sepet_id = intval($_POST['sepet_id']);
        $new_quantity = intval($_POST['quantity']);

        if ($sepet_id > 0) {
            // Miktar 0 veya altıysa ürünü sepetten çıkar
            if ($new_quantity <= 0) {
                $stmt = $pdo->prepare("DELETE FROM shopping_list WHERE id = ? AND user_id = ?");
                $stmt->execute([$sepet_id, current_user_id()]);
                header("Location: shopping_list.php?durum=silindi");
                exit;
            }

            // Ürünü ve güncel stoğunu bul
            $stmt = $pdo->prepare("SELECT market_price_id FROM shopping_list WHERE id = ? AND user_id = ?");
            $stmt->execute([$sepet_id, current_user_id()]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($item) {
                /** GUNCEL MARKET STOGUNU SORGULA */
                $stmt_stock = $pdo->prepare("SELECT stock FROM market_prices WHERE id = ?");
                $stmt_stock->execute([$item['market_price_id']]);
                $current_stock = $stmt_stock->fetchColumn();

                // Stok yeterli mi kontrol et FAZLA SIPARISI ENGELLE
                if ($new_quantity > $current_stock) {
                    header("Location: shopping_list.php?durum=stok_yetmedi");
                    exit;
                }

                // Miktarı güncelle
                $stmt_update = $pdo->prepare("UPDATE shopping_list SET quantity = ? WHERE id = ?");
                $stmt_update->execute([$new_quantity, $sepet_id]);
                header("Location: shopping_list.php?durum=guncellendi");
                exit;
            }
        }
        break;

    case 'checkout':
        /**
         * Satın Alma (Ödeme Simülasyonu): Stok düşürme ve siparişi tamamlama.
         * PDO Transaction kullanılarak verilerin tutarlılığı korunur.
         * BIR HATA CIKARSA (ornegin bir urunun stogu o an biterse) TUM SEPETI ESKI HALINE DONDURUR(ROLLBACK)
         */
        $user_id = current_user_id();
        $stok_yetersiz = false;

        try {
            $pdo->beginTransaction(); // verıtabanında guvenli islem alanini baslatir

            // Bekleyen sepet ürünlerini ve güncel stoklarını getir
            $sepet_stmt = $pdo->prepare("
                SELECT sl.id AS sepet_id, sl.quantity, mp.id AS market_price_id, mp.stock AS current_stock
                FROM shopping_list sl
                JOIN market_prices mp ON sl.market_price_id = mp.id 
                WHERE sl.user_id = ? AND sl.status = 'pending'
            ");
            $sepet_stmt->execute([$user_id]);
            $sepet_urunleri = $sepet_stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($sepet_urunleri)) {
                $pdo->rollBack();
                header("Location: shopping_list.php");
                exit;
            }

            foreach ($sepet_urunleri as $item) {
                $yeni_stok = $item['current_stock'] - $item['quantity'];

                // STOK EKSIYE DUSERSE ISLEMI HEMEN DURDUR
                if ($yeni_stok < 0) {
                    $stok_yetersiz = true;
                    break;
                }

                // Market fiyat tablosundaki stoğu düşür
                $update_stmt = $pdo->prepare("UPDATE market_prices SET stock = ? WHERE id = ?"); //sipariş onaylandığı anda ilgili ürünün stoğu azaltılır
                $update_stmt->execute([$yeni_stok, $item['market_price_id']]);

                // Ürünü 'completed' (tamamlandı) olarak işaretle ve zaman damgası ekle
                $update_sl_stmt = $pdo->prepare("UPDATE shopping_list SET status = 'completed', bought_at = NOW() WHERE id = ?");
                $update_sl_stmt->execute([$item['sepet_id']]);
            }

            if ($stok_yetersiz) {
                $pdo->rollBack(); // BIR URUN BILE YETERSIZSE HICBIR STOGU DUSME, ISLEMI GERI AL
                header("Location: shopping_list.php?durum=yetersizstok");
            } else {
                $pdo->commit(); // HER SEY BASARILIYSA VERITABANINA KALICI OLARAK ISLE
                header("Location: shopping_list.php?durum=tamamlandi");
            }
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();  // SISTEMSEL BIR HATA OLURSA GUVENLIGI SAGLA
            header("Location: shopping_list.php?durum=hata");
            exit;
        }

    case 'toggle_favorite':
        /**
         * Favorilere Ekleme/Çıkarma: AJAX ile çalışır ve JSON yanıt döner.
         * JSON FORMATINDA YANIT DONEREK SAYFA YENILENMEDEN FAVORI ISLEMLERINI GERCEKLESTIRIR
         */
        $user_id = current_user_id();
        $market_price_id = intval($_POST['market_price_id'] ?? 0);

        header('Content-Type: application/json'); // YANITIN JSON OLDUGUNU TARAYICIYA BILDIR

        if ($market_price_id > 0) {
            // Ürün zaten favorilerde mi?
            $stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND market_price_id = ?");
            $stmt->execute([$user_id, $market_price_id]);

            if ($stmt->rowCount() > 0) {
                // Varsa: Favorilerden çıkar
                $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND market_price_id = ?")->execute([$user_id, $market_price_id]);
                echo json_encode(['status' => 'removed', 'message' => 'Ürün favorilerinizden çıkarıldı.']);
            } else {
                // Yoksa: Favorilere ekle
                $pdo->prepare("INSERT INTO favorites (user_id, market_price_id) VALUES (?, ?)")->execute([$user_id, $market_price_id]);
                echo json_encode(['status' => 'added', 'message' => 'Ürün favorilerinize eklendi.']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Geçersiz parametre.']);
        }
        exit;

    default:
        // TANIMSIZ BIR 'type' GELIRSE ANA SAYFAYA GONDER
        header("Location: index.php");
        exit;
}
