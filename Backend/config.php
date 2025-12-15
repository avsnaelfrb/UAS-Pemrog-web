<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "elibrary_db";

// Aktifkan Error Reporting untuk Debugging (PENTING SAAT ERROR 500)
mysqli_report(MYSQLI_REPORT_OFF); // Matikan auto-throw exception agar bisa kita tangkap manual
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

session_start();

// Base URL (Sesuaikan dengan folder projectmu)
$base_url = "http://localhost/web-perpus-UAS";

function redirect($url)
{
    echo "<script>window.location.href='$url';</script>";
    exit;
}

// FUNGSI UPLOAD FILE (Wajib ada agar tidak Error 500)
function uploadFile($file, $destination)
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    if (!file_exists($destination)) {
        mkdir($destination, 0777, true);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = time() . '_' . rand(100, 999) . '.' . $ext;
    $target = $destination . '/' . $fileName;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        return $fileName;
    }
    return false;
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
