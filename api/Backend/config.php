<?php

/**
 * KONFIGURASI DATABASE CLOUD (TiDB Cloud dengan SSL)
 */
mysqli_report(MYSQLI_REPORT_OFF);
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "gateway01.ap-southeast-1.prod.aws.tidbcloud.com";
$user = "2UWBFUk7cxV4hiB.root"; 
$pass = "qiDCLtau7KoVXS4L"; 
$db   = "test";
$port = 4000;

$conn = mysqli_init();

mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);

$success = mysqli_real_connect(
    $conn,
    $host,
    $user,
    $pass,
    $db,
    $port,
    NULL,
    MYSQLI_CLIENT_SSL
);

if (!$success) {
    die("Koneksi Database Cloud Gagal: " . mysqli_connect_error());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

mysqli_query($conn, "SET SESSION sql_mode = ''");

$base_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]";

/**
 * FUNGSI HELPER (Tetap Sama)
 */
function redirect($url)
{
    echo "<script>window.location.href='$url';</script>";
    exit;
}

function uploadFile($file, $destination)
{
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if (!file_exists($destination)) mkdir($destination, 0777, true);

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = time() . '_' . rand(100, 999) . '.' . $ext;
    $fileName = preg_replace("/[^a-zA-Z0-9._-]/", "", $fileName);
    $target = $destination . '/' . $fileName;

    if (move_uploaded_file($file['tmp_name'], $target)) return $fileName;
    return false;
}

function deleteFile($filePath)
{
    if (file_exists($filePath)) return unlink($filePath);
    return false;
}

function getStatusBadge($status)
{
    switch ($status) {
        case 'APPROVED':
            return '<span class="bg-green-100 text-green-800 text-xs font-bold px-2 py-1 rounded">Terbit</span>';
        case 'PENDING':
            return '<span class="bg-yellow-100 text-yellow-800 text-xs font-bold px-2 py-1 rounded">Menunggu</span>';
        case 'REJECTED':
            return '<span class="bg-red-100 text-red-800 text-xs font-bold px-2 py-1 rounded">Ditolak</span>';
        default:
            return '';
    }
}
