<?php
// Frontend/stream_file.php

// 1. Matikan error reporting agar warning PHP tidak merusak binary PDF
error_reporting(0);
ini_set('display_errors', 0);

// 2. Bersihkan buffer output sebelum script jalan (PENTING)
if (ob_get_level()) ob_end_clean();

require '../Backend/config.php';

// 3. Bersihkan buffer lagi setelah require config (Jaga-jaga ada spasi di config.php)
ob_clean();

if (!isset($_GET['id'])) {
    die("ID Kosong");
}
$id = (int)$_GET['id'];

// Ambil data binary
$query = "SELECT file_path FROM books WHERE id = $id";
$result = mysqli_query($conn, $query);
$book = mysqli_fetch_assoc($result);

if ($book && !empty($book['file_path'])) {
    $size = strlen($book['file_path']);

    // --- HEADER UTAMA ---
    header("Content-Type: application/pdf");
    header("Content-Length: " . $size);

    // KUNCI: Gunakan 'inline' agar terbuka di browser (bukan 'attachment')
    header("Content-Disposition: inline; filename=\"document.pdf\"");

    // Header Cache (Agar browser tidak menyimpan file rusak/lama)
    header("Cache-Control: private, max-age=0, must-revalidate");
    header("Pragma: public");

    // Kirim Binary
    echo $book['file_path'];
    exit;
} else {
    // Jika data kosong, tampilkan pesan teks
    header("Content-Type: text/plain");
    echo "File PDF kosong atau tidak ditemukan di database.";
}
