<?php
// Tampilkan Error untuk Debugging (PENTING)
mysqli_report(MYSQLI_REPORT_OFF);
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$user = "root";
$pass = "";
$db   = "elibrary_db";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set SQL Mode agar tidak strict (Opsional tapi membantu di beberapa XAMPP)
mysqli_query($conn, "SET SESSION sql_mode = ''");

// Base URL (Sesuaikan path project Anda)
$base_url = "http://localhost/web-perpus-UAS";

// --- FUNGSI HELPER ---

function redirect($url)
{
    echo "<script>window.location.href='$url';</script>";
    exit;
}

/**
 * Fungsi Upload File dengan Validasi Folder
 */
function uploadFile($file, $destination)
{
    // Cek error upload dasar
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    // Pastikan folder tujuan ada
    if (!file_exists($destination)) {
        if (!mkdir($destination, 0777, true)) {
            error_log("Gagal membuat folder: " . $destination);
            return false;
        }
    }

    // Generate nama file unik
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = time() . '_' . rand(100, 999) . '.' . $ext;

    // Sanitasi nama file
    $fileName = preg_replace("/[^a-zA-Z0-9._-]/", "", $fileName);

    $target = $destination . '/' . $fileName;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        return $fileName;
    }

    error_log("Gagal memindahkan file ke: " . $target);
    return false;
}

/**
 * Fungsi Hapus File Fisik
 */
function deleteFile($filePath)
{
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    return false;
}

/**
 * Fungsi Badge Status
 */
function getStatusBadge($status)
{
    switch ($status) {
        case 'APPROVED':
            return '<span class="bg-green-100 text-green-800 text-xs font-bold px-2 py-1 rounded">Terbit</span>';
        case 'PENDING':
            return '<span class="bg-yellow-100 text-yellow-800 text-xs font-bold px-2 py-1 rounded">Menunggu Review</span>';
        case 'REJECTED':
            return '<span class="bg-red-100 text-red-800 text-xs font-bold px-2 py-1 rounded">Ditolak</span>';
        default:
            return '';
    }
}
