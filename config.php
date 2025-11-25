<?php
// Konfigurasi Database - Sesuai dengan server yang diberikan
define('DB_HOST', '10.34.0.116');
define('DB_PORT', '3306');
define('DB_USER', 'mahasiswa');
define('DB_PASS', 'rahasia');
define('DB_NAME', 'sakila'); // Sesuaikan dengan nama database Anda

// Koneksi ke database
function getDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        
        if ($conn->connect_error) {
            die("Koneksi gagal: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        die("Error koneksi database: " . $e->getMessage());
    }
}

// Mulai session jika belum dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fungsi untuk memeriksa apakah user sudah login
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Fungsi untuk redirect ke halaman login
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// Fungsi helper untuk escape output
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
?>