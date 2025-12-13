<?php
// Frontend/stream_file.php
// Matikan semua error reporting agar tidak merusak binary
error_reporting(0);
ini_set('display_errors', 0);

require '../Backend/config.php';

// Bersihkan buffer output sebelum script jalan
if (ob_get_level()) ob_end_clean();

if (!isset($_GET['id'])) {
    die("ID tidak ditemukan");
}

$id = (int)$_GET['id'];

// Ambil data
$query = "SELECT file_path FROM books WHERE id = $id";
$result = mysqli_query($conn, $query);
$book = mysqli_fetch_assoc($result);

if ($book && !empty($book['file_path'])) {
    $file_content = $book['file_path'];
    $size = strlen($file_content);

    // Header lengkap untuk PDF
    header("Content-Type: application/pdf");
    header("Content-Length: " . $size); // Memberitahu browser ukuran file
    header("Content-Disposition: inline; filename=\"dokumen_pustaka.pdf\"");
    header("Cache-Control: private, max-age=0, must-revalidate");
    header("Pragma: public");

    echo $file_content;
    exit;
} else {
    echo "File PDF kosong atau rusak di database.";
}
