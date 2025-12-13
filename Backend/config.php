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
ini_set('memory_limit', '512M');
$base_url = "http://localhost/web-perpus-UAS";

function redirect($url)
{
    echo "<script>window.location.href='$url';</script>";
    exit;
}

function uploadFile($file, $destination)
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        if ($file['error'] === UPLOAD_ERR_NO_FILE) return false;

        echo "<script>alert('Upload Gagal! Kode Error PHP: " . $file['error'] . "');</script>";
        return false;
    }

    if (!file_exists($destination)) {
        if (!mkdir($destination, 0777, true)) {
            echo "<script>alert('Gagal membuat folder: $destination. Cek permission Linux!');</script>";
            return false;
        }
    }

    if (!is_writable($destination)) {
        echo "<script>alert('Folder tujuan tidak bisa ditulis: $destination. Jalankan chmod 777!');</script>";
        return false;
    }

    $fileName = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", basename($file['name'])); // Sanitasi nama file
    $target = $destination . '/' . $fileName;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        return $fileName;
    } else {
        echo "<script>alert('Gagal memindahkan file ke: $target. Cek permission!');</script>";
        return false;
    }
}
