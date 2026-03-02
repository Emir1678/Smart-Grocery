<?php
// Session güvenli başlatma
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

//Güvenlik kalkanıdır
function sanitize($value)
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8'); //XSS saldırılarını engellemek icin güvenlik önlemi
}

// kullanıcının ID'si Session'damı kontrol eder
function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

// eğer kullanıcı giriş yapmamışsa, onu header() yönelndirmesiyle giriş sayfasına atar
function require_login()
{
    if (!is_logged_in()) {
        // Klasör yoluna dikkat et!
        header("Location: ../auth/login.php");
        exit;
    }
}

function current_user_id()
{
    return $_SESSION['user_id'] ?? null;
}

// kullanıcı yetkisi kontrol edilir
function is_admin()
{
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}
