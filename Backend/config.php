<?php

/**
 * KONFIGURASI DATABASE & SESSION - RAILWAY PRODUCTION VERSION
 * Versi ini dilengkapi dengan error reporting untuk mempermudah debugging.
 */

// 1. Aktifkan Error Reporting untuk Debugging (Hanya selama masa perbaikan)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 2. Memulai session secara standar
if (session_status() === PHP_SESSION_NONE) {
    // Pastikan session tersimpan dengan benar di lingkungan cloud
    ini_set('session.cookie_path', '/');
    session_start();
}

// 3. Cek apakah ekstensi MySQLi terpasang (Penting untuk Railway)
if (!extension_loaded('mysqli')) {
    die("FATAL: Ekstensi 'mysqli' tidak ditemukan. Pastikan composer.json Anda sudah menyertakan 'ext-mysqli'.");
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/**
 * PENGATURAN DATABASE MENGGUNAKAN CONNECTION STRING
 */
// Railway menyediakan variabel 'MYSQL_URL' atau 'DATABASE_URL'.
$db_url = getenv('MYSQL_URL') ?: getenv('DATABASE_URL') ?: "mysql://root:FOWHIlcosnZmjplwIBrWnDxjTmpqENwC@trolley.proxy.rlwy.net:40029/railway";

// Bedah URL untuk mendapatkan komponen database
$db_parts = parse_url($db_url);

if (!$db_parts) {
    die("FATAL: Format DATABASE_URL tidak valid.");
}

$host = $db_parts['host'] ?? 'localhost';
$user = $db_parts['user'] ?? 'root';
$pass = $db_parts['pass'] ?? '';
$db   = isset($db_parts['path']) ? ltrim($db_parts['path'], '/') : 'railway';
$port = $db_parts['port'] ?? 3306;

try {
    // Koneksi menggunakan data hasil bedah URL
    $conn = mysqli_connect($host, $user, $pass, $db, $port);
    mysqli_query($conn, "SET SESSION sql_mode = ''");
    mysqli_query($conn, "SET time_zone = '+07:00'");
} catch (Exception $e) {
    // Tampilkan error spesifik jika koneksi gagal
    die("Koneksi database gagal: " . $e->getMessage() . " (Host: $host, DB: $db)");
}

/**
 * FUNGSI HELPER
 */

function redirect($url)
{
    // Pastikan session ditulis sebelum redirect
    session_write_close();
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
