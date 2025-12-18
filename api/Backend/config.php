<?php

/**
 * KONFIGURASI DATABASE RAILWAY - FIXED VERSION
 */

// Memulai output buffering untuk mencegah error "headers already sent"
ob_start();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Mengambil environment variables dari Railway/Vercel
$host = getenv('DB_HOST') ?: "localhost";
$user = getenv('DB_USER') ?: "root";
$pass = getenv('DB_PASSWORD') ?: "";
$db   = getenv('DB_NAME') ?: "elibrary_db";
$port = (int)(getenv('DB_PORT') ?: 3306);

try {
    $conn = mysqli_connect($host, $user, $pass, $db, $port);
    // Menonaktifkan strict mode untuk kompatibilitas cloud
    mysqli_query($conn, "SET SESSION sql_mode = ''");
} catch (Exception $e) {
    die("Koneksi database gagal. Silakan cek Environment Variables Anda.");
}

// Session management yang lebih aman
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * FUNGSI HELPER
 */

// Perbaikan fungsi redirect menggunakan Header (Standar PHP)
function redirect($url)
{
    header("Location: $url");
    exit();
}

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

function deleteFile($filePath)
{
    return file_exists($filePath) ? unlink($filePath) : false;
}

function getStatusBadge($status)
{
    $status = strtoupper($status);
    $badges = [
        'APPROVED' => '<span class="bg-green-100 text-green-800 text-xs font-bold px-2 py-1 rounded">Terbit</span>',
        'PENDING'  => '<span class="bg-yellow-100 text-yellow-800 text-xs font-bold px-2 py-1 rounded">Menunggu</span>',
        'REJECTED' => '<span class="bg-red-100 text-red-800 text-xs font-bold px-2 py-1 rounded">Ditolak</span>'
    ];
    return $badges[$status] ?? '<span class="bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded">' . $status . '</span>';
}
