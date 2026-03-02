// assets/js/script.js

// 1. Sepete Ekleme Fonksiyonu
async function addToCart(productId, marketId) {
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('market_id', marketId);
    formData.append('quantity', 1);

    try {
        const response = await fetch('api/add_to_cart.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.ok) {
            alert("Ürün sepete eklendi!");
            window.location.href = 'shopping_list.php'; // Sepete yönlendir
        } else {
            alert("Hata: " + result.msg);
        }
    } catch (error) {
        console.error('Hata:', error);
    }
}

// 2. Alışverişi Tamamlama (Checkout) Fonksiyonu
async function processCheckout() {
    if(!confirm("Satın almayı onaylıyor musunuz? Stoklar düşülecektir.")) return;

    try {
        const response = await fetch('api/checkout.php');
        const result = await response.json();

        // shopping_list.php içindeki mesaj kutusunu bul
        const msgBox = document.getElementById('checkout-message');
        
        if (result.ok) {
            if(msgBox) {
                msgBox.style.color = 'green';
                msgBox.textContent = "Siparişiniz alındı! Stoklar güncellendi.";
            } else {
                alert("Sipariş başarıyla tamamlandı.");
            }
            // 2 saniye sonra sayfayı yenile (sepet boşalsın)
            setTimeout(() => { window.location.reload(); }, 2000);
        } else {
            if(msgBox) {
                msgBox.style.color = 'red';
                msgBox.textContent = "Hata: " + result.msg;
            } else {
                alert("Hata: " + result.msg);
            }
        }
    } catch (error) {
        console.error('Hata:', error);
    }
}

// 3. Arama Fonksiyonu 
async function searchProduct() {
    // home.php'deki input ID'si 'searchInput' olmalı
    let input = document.getElementById('searchInput');
    if (!input) return; // Hata almamak için kontrol

    let query = input.value;
    
    // API'ye istek at (api/search.php)
    let response = await fetch(`api/search.php?q=${query}`);
    let result = await response.json();
    
    let html = '';
    if(result.ok && result.data) {
        // Gelen verileri döngüye sok ve HTML oluştur
        result.data.forEach(item => {
            html += `
            <div class="card">
                <h3>${item.product_name}</h3>
                <p>${item.brand || ''}</p>
                <a href="product_detail.php?id=${item.product_id}" class="btn btn-primary">Fiyatları Gör</a>
            </div>`;
        });
        
        // home.php'deki ürün listesi div'ini güncelle
        let listDiv = document.getElementById('productList');
        if(listDiv) listDiv.innerHTML = html;
    }
}