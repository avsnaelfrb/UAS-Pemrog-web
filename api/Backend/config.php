<?php

/**
 * KONFIGURASI DATABASE & SESSION - FIXED FOR VERCEL & LOCAL
 * Menyertakan kembali fungsi uploadFile dan logic orisinal project.
 */

ob_start();

ini_set('session.cookie_path', '/');
ini_set('session.gc_maxlifetime', 3600);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Konfigurasi Database (Mendukung Environment Variables Railway)
$host = getenv('DB_HOST') ?: "localhost";
$user = getenv('DB_USER') ?: "root";
$pass = getenv('DB_PASSWORD') ?: "";
$db   = getenv('DB_NAME') ?: "elibrary_db";
$port = (int)(getenv('DB_PORT') ?: 3306);

try {
    $conn = mysqli_connect($host, $user, $pass, $db, $port);
    // Menonaktifkan strict mode untuk menghindari error SQL di beberapa server cloud
    mysqli_query($conn, "SET SESSION sql_mode = ''");
} catch (Exception $e) {
    die("Koneksi database gagal. Periksa Environment Variables Anda.");
}

/**
 * FUNGSI HELPER & LOGIC PROJECT
 */

function redirect($url)
{
    header("Location: $url");
    ob_end_flush();
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
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    return false;
}

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
