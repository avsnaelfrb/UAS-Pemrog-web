<?php

/**
 * File Streamer - Menampilkan PDF dari folder uploads
 * Disederhanakan untuk Localhost (Tanpa Buffer Vercel)
 */
require_once dirname(__DIR__) . '/Backend/config.php';

if (!isset($_SESSION['user_id'])) {
    die("Akses ditolak.");
}

if (!isset($_GET['id'])) {
    die("Buku tidak dipilih.");
}

$id = (int)$_GET['id'];
$query = "SELECT file_path FROM books WHERE id = $id";
$result = mysqli_query($conn, $query);
$book = mysqli_fetch_assoc($result);

if ($book && !empty($book['file_path'])) {
    $filePath = "../uploads/books/" . $book['file_path'];

    if (file_exists($filePath)) {
        header("Content-Type: application/pdf");
        header("Content-Disposition: inline; filename='document.pdf'");
        header("Content-Length: " . filesize($filePath));
        readfile($filePath);
        exit;
    } else {
        echo "File fisik tidak ditemukan.";
    }
} else {
    echo "Data file tidak ditemukan di database.";
}
