<?php
// Frontend/stream_file.php

// 1. Matikan error reporting agar warning PHP tidak merusak header PDF
error_reporting(0);
ini_set('display_errors', 0);

// 2. Bersihkan buffer output sebelum script jalan
if (ob_get_level()) ob_end_clean();

require './api/Backend/config.php';

// 3. Bersihkan buffer lagi setelah require config
ob_clean();

if (!isset($_GET['id'])) {
    die("ID Kosong");
}
$id = (int)$_GET['id'];

// --- LOGIKA BARU: Ambil Nama File, Bukan Data BLOB ---
$query = "SELECT file_path FROM books WHERE id = $id";
$result = mysqli_query($conn, $query);
$book = mysqli_fetch_assoc($result);

// Path ke folder uploads (Naik satu level dari Frontend)
$folderPath = "../uploads/books/";

if ($book && !empty($book['file_path'])) {
    $fullPath = $folderPath . $book['file_path'];

    // Cek apakah file fisik ada di server
    if (file_exists($fullPath)) {
        $size = filesize($fullPath);

        // --- HEADER UTAMA ---
        header("Content-Type: application/pdf");
        header("Content-Length: " . $size);

        // 'inline' agar terbuka di browser
        header("Content-Disposition: inline; filename=\"document.pdf\"");

        // Header Cache
        header("Cache-Control: private, max-age=0, must-revalidate");
        header("Pragma: public");

        // Kirim File Fisik ke Output
        readfile($fullPath);
        exit;
    } else {
        // Fallback jika nama file ada di DB tapi fisiknya hilang
        header("Content-Type: text/plain");
        echo "Error: File fisik PDF tidak ditemukan di server ($fullPath).";
    }
} else {
    header("Content-Type: text/plain");
    echo "File PDF tidak ditemukan di database.";
}
