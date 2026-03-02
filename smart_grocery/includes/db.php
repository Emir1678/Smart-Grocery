<?php
// Veritabanı bağlantı bilgileri
$host = '127.0.0.1';
$db   = 'proje_db1'; // Terminalde oluşturduğumuz veritabanı adı bu
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Veritabanında bir hata olduğunda php'nin exception olarak fırlatmasını sağlar
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,  // veri çekildiğinde, sonuçların assoc. array şeklinde gelmesini sağlar
    PDO::ATTR_EMULATE_PREPARES   => false, // doğrudan MySQL'in kendi prepared statm. öz. kullanmasını sağlar.SQL Injection'dan korur
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Veritabanı bağlantısı kurulamadı: " . $e->getMessage());
}
