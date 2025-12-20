<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * KONFIGURASI OTOMATIS (Local vs Hosting)
 * Script ini mendeteksi alamat IP untuk menentukan kredensial database yang digunakan.
 */
if ($_SERVER['REMOTE_ADDR'] == '127.0.0.1' || $_SERVER['REMOTE_ADDR'] == '::1') {
    // --- PENGATURAN LOCALHOST (XAMPP) ---
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $db   = 'elibrary_db';
} else {
    // --- PENGATURAN INFINITYFREE ---
    // Silakan ganti nilai di bawah ini sesuai data di vPanel / MySQL Databases InfinityFree Anda
    $host = 'sql202.infinityfree.com';      // Ganti dengan MySQL Hostname dari InfinityFree
    $user = 'if0_40727091';          // Ganti dengan MySQL Username dari InfinityFree
    $pass = 'avsnaelfrb10';   // Ganti dengan Password FTP/Client Area Anda
    $db   = 'if0_40727091_elibrary_db';       // Ganti dengan Nama Database yang dibuat di hosting
}

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    // Pesan error umum untuk keamanan di hosting
    die("Maaf, koneksi database sedang bermasalah. Silakan hubungi administrator.");
}

mysqli_query($conn, "SET SESSION sql_mode = ''");
mysqli_query($conn, "SET time_zone = '+07:00'");


/**
 * HELPER FUNCTIONS
 */

function redirect($url)
{
    session_write_close();
    header("Location: $url");
    exit();
}

function uploadFile($file, $destination)
{
    if ($file['error'] !== UPLOAD_ERR_OK) return false;

    // Pastikan folder tujuan tersedia
    if (!file_exists($destination)) {
        mkdir($destination, 0777, true);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = time() . '_' . rand(100, 999) . '.' . $ext;

    // Bersihkan nama file agar tidak ada spasi atau karakter ilegal
    $fileName = preg_replace("/[^a-zA-Z0-9._-]/", "", $fileName);
    $target = $destination . DIRECTORY_SEPARATOR . $fileName;

    return move_uploaded_file($file['tmp_name'], $target) ? $fileName : false;
}

function deleteFile($filePath)
{
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    return false;
}

function getStatusBadge($status)
{
    $status = strtoupper(trim($status));
    $badges = [
        'APPROVED' => '<span class="bg-green-100 text-green-800 text-xs font-bold px-2 py-1 rounded">Terbit</span>',
        'PENDING'  => '<span class="bg-yellow-100 text-yellow-800 text-xs font-bold px-2 py-1 rounded">Menunggu</span>',
        'REJECTED' => '<span class="bg-red-100 text-red-800 text-xs font-bold px-2 py-1 rounded">Ditolak</span>'
    ];
    return $badges[$status] ?? $status;
}

function checkRole($expectedRole)
{
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    $currentRole = strtoupper(trim($_SESSION['role']));
    if ($currentRole !== strtoupper($expectedRole)) {
        if ($currentRole === 'ADMIN') header("Location: dashboard-admin.php");
        else if ($currentRole === 'PENERBIT') header("Location: dashboard-publisher.php");
        else header("Location: dashboard-user.php");
        exit();
    }
}
