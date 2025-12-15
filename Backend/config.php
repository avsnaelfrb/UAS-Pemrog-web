<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "elibrary_db";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

session_start();

mysqli_query($conn, "SET SESSION sql_mode = ''");
ini_set('memory_limit', '512M'); // Memory limit tetap dijaga

// Base URL Aplikasi (Sesuaikan jika folder project berubah)
$base_url = "http://localhost/web-perpus-UAS";

function redirect($url)
{
    echo "<script>window.location.href='$url';</script>";
    exit;
}

/**
 * Fungsi untuk mengupload file ke folder fisik
 * @param array $file - Array dari $_FILES['input_name']
 * @param string $destination - Path folder tujuan (misal: '../assets/books/')
 * @return string|false - Mengembalikan nama file baru jika sukses, false jika gagal
 */
function uploadFile($file, $destination)
{
    // Cek error upload dasar
    if ($file['error'] !== UPLOAD_ERR_OK) {
        if ($file['error'] === UPLOAD_ERR_NO_FILE) return false;

        // Kode error upload PHP untuk debugging
        echo "<script>alert('Upload Gagal! Kode Error PHP: " . $file['error'] . "');</script>";
        return false;
    }

    // Buat folder jika belum ada
    if (!file_exists($destination)) {
        if (!mkdir($destination, 0777, true)) {
            echo "<script>alert('Gagal membuat folder: $destination. Cek permission!');</script>";
            return false;
        }
    }

    // Validasi apakah folder bisa ditulis
    if (!is_writable($destination)) {
        echo "<script>alert('Folder tujuan tidak bisa ditulis: $destination. Jalankan chmod 777!');</script>";
        return false;
    }

    // Generate nama file unik: timestamp_random.ext
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = time() . '_' . rand(100, 999) . '.' . $ext;

    // Sanitasi nama file agar aman dari karakter aneh
    $fileName = preg_replace("/[^a-zA-Z0-9._-]/", "", $fileName);

    $target = $destination . '/' . $fileName;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        return $fileName;
    } else {
        echo "<script>alert('Gagal memindahkan file ke: $target. Cek permission folder!');</script>";
        return false;
    }
}

/**
 * Fungsi baru untuk menghapus file fisik
 * Penting agar server tidak penuh sampah saat buku dihapus dari DB
 */
function deleteFile($filePath)
{
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    return false;
}

// Fungsi Helper untuk Badge Status
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
