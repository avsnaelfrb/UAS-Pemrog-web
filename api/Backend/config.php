<?php

/**
 * KONFIGURASI DATABASE & SESSION - VERSI STABIL VERCEL
 * Menangani Session Persistence & Semua Fungsi Original Project.
 */

// 1. Buffering output untuk mencegah error 'headers already sent'
ob_start();

// 2. Konfigurasi Session untuk Vercel (Penting!)
ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', $_SERVER['HTTP_HOST']);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS'])); // Secure jika HTTPS
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Konfigurasi Database (Railway Environment)
$host = getenv('DB_HOST') ?: "localhost";
$user = getenv('DB_USER') ?: "root";
$pass = getenv('DB_PASSWORD') ?: "";
$db   = getenv('DB_NAME') ?: "elibrary_db";
$port = (int)(getenv('DB_PORT') ?: 3306);

try {
    $conn = mysqli_connect($host, $user, $pass, $db, $port);
    mysqli_query($conn, "SET SESSION sql_mode = ''");
} catch (Exception $e) {
    die("Koneksi database gagal. Periksa Environment Variables.");
}

/**
 * FUNGSI HELPER ORIGINAL & PERBAIKAN
 */

// Perbaikan fungsi redirect: Memaksa session tersimpan sebelum pindah halaman
function redirect($url)
{
    session_write_close(); // PAKSA simpan session ke storage sebelum redirect
    header("Location: $url");
    ob_end_flush();
    exit();
}

// Fungsi Management File (Upload) - DIPERTAHANKAN
function uploadFile($file, $destination)
{
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if (!file_exists($destination)) mkdir($destination, 0777, true);

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = time() . '_' . rand(100, 999) . '.' . $ext;
    $fileName = preg_replace("/[^a-zA-Z0-9._-]/", "", $fileName);
    $target = $destination . DIRECTORY_SEPARATOR . $fileName;

    return move_uploaded_file($file['tmp_name'], $target) ? $fileName : false;
}

// Fungsi Management File (Delete) - DIPERTAHANKAN
function deleteFile($filePath)
{
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    return false;
}

// Fungsi Badge Status - DIPERTAHANKAN
function getStatusBadge($status)
{
    $status = strtoupper(trim($status));
    $badges = [
        'APPROVED' => '<span class="bg-green-100 text-green-800 text-xs font-bold px-2 py-1 rounded">Terbit</span>',
        'PENDING'  => '<span class="bg-yellow-100 text-yellow-800 text-xs font-bold px-2 py-1 rounded">Menunggu</span>',
        'REJECTED' => '<span class="bg-red-100 text-red-800 text-xs font-bold px-2 py-1 rounded">Ditolak</span>'
    ];
    return $badges[$status] ?? '<span class="bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded">' . $status . '</span>';
}

/**
 * LOGIC KEAMANAN ROLE (Anti-Loop)
 */
function checkRole($expectedRole)
{

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        header("Location: login.php");
        exit();
    }

    $currentRole = strtoupper(trim($_SESSION['role']));
    $expectedRole = strtoupper(trim($expectedRole));

    if ($currentRole !== $expectedRole) {
        if ($currentRole === 'ADMIN') header("Location: dashboard-admin.php");
        else if ($currentRole === 'PENERBIT') header("Location: dashboard-publisher.php");
        else header("Location: dashboard-user.php");
        exit();
    }
}
