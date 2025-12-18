<?php


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = getenv('DB_HOST') ?: "localhost";
$user = getenv('DB_USER') ?: "root";
$pass = getenv('DB_PASSWORD') ?: "";
$db   = getenv('DB_NAME') ?: "elibrary_db";
$port = (int)(getenv('DB_PORT') ?: 3306);

try {
    $conn = mysqli_connect($host, $user, $pass, $db, $port);
    mysqli_query($conn, "SET SESSION sql_mode = ''");
    mysqli_query($conn, "SET time_zone = '+07:00'");
} catch (Exception $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

/**
 * FUNGSI HELPER ORIGINAL
 */

function redirect($url)
{
    header("Location: $url");
    exit();
}

function uploadFile($file, $destination)
{
    if ($file['error'] !== UPLOAD_ERR_OK) return false;

    if (!file_exists($destination)) {
        mkdir($destination, 0777, true);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = time() . '_' . rand(100, 999) . '.' . $ext;
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
