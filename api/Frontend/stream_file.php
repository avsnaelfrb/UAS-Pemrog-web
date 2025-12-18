<?php

error_reporting(0);
ini_set('display_errors', 0);

if (ob_get_level()) ob_end_clean();

require_once dirname(__DIR__) . '/Backend/config.php';

ob_clean();

if (!isset($_GET['id'])) {
    die("ID Kosong");
}
$id = (int)$_GET['id'];

$query = "SELECT file_path FROM books WHERE id = $id";
$result = mysqli_query($conn, $query);
$book = mysqli_fetch_assoc($result);

$folderPath = "../uploads/books/";

if ($book && !empty($book['file_path'])) {
    $fullPath = $folderPath . $book['file_path'];

    if (file_exists($fullPath)) {
        $size = filesize($fullPath);

        header("Content-Type: application/pdf");
        header("Content-Length: " . $size);

        header("Content-Disposition: inline; filename=\"document.pdf\"");

        header("Cache-Control: private, max-age=0, must-revalidate");
        header("Pragma: public");

        readfile($fullPath);
        exit;
    } else {
        header("Content-Type: text/plain");
        echo "Error: File fisik PDF tidak ditemukan di server ($fullPath).";
    }
} else {
    header("Content-Type: text/plain");
    echo "File PDF tidak ditemukan di database.";
}
