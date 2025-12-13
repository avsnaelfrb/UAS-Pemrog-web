<?php
// FILE INI BERGUNA UNTUK MENGAMBIL PDF DARI DATABASE DAN MENAMPILKANNYA KE BROWSER
require '../Backend/config.php';

if (!isset($_GET['id'])) {
    die("ID file tidak ditemukan.");
}

$id = (int)$_GET['id'];

// Ambil data binary file_path (yang isinya sekarang adalah konten PDF)
$query = "SELECT file_path FROM books WHERE id = $id";
$result = mysqli_query($conn, $query);
$book = mysqli_fetch_assoc($result);

if ($book && $book['file_path']) {
    // Beritahu browser bahwa yang dikirim ini adalah PDF
    header("Content-type: application/pdf");
    header("Content-Disposition: inline; filename=document.pdf");

    // Kirim data binary
    echo $book['file_path'];
} else {
    echo "File PDF tidak ditemukan di database.";
}
