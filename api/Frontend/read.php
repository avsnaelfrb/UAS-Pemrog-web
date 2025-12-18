<?php
require './api/Backend/config.php';

if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

if (!isset($_GET['id'])) {
    die("Buku tidak dipilih.");
}

$id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// 1. Ambil Judul Buku (Logic DB masih sama karena table books masih punya kolom title)
$query = "SELECT title FROM books WHERE id = $id";
$result = mysqli_query($conn, $query);
$book = mysqli_fetch_assoc($result);

if (!$book) {
    die("Buku tidak ditemukan.");
}

// 2. Simpan ke History
// Menggunakan ON DUPLICATE KEY UPDATE agar jika sudah pernah baca, update waktunya saja
$insert_history = "INSERT INTO history (user_id, book_id, read_at) 
                   VALUES ($user_id, $id, NOW()) 
                   ON DUPLICATE KEY UPDATE read_at = NOW()";
mysqli_query($conn, $insert_history);

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Membaca: <?= htmlspecialchars($book['title']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body,
        html {
            height: 100%;
            margin: 0;
            overflow: hidden;
        }

        iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
    </style>
</head>

<body class="flex flex-col h-screen">
    <div class="bg-gray-900 text-white p-3 flex justify-between items-center shadow-lg z-10">
        <div class="flex items-center gap-3">
            <a href="detail.php?id=<?= $id ?>" class="text-gray-300 hover:text-white transition flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                </svg>
                Kembali
            </a>
            <h1 class="font-semibold text-sm md:text-base truncate max-w-md"><?= htmlspecialchars($book['title']) ?></h1>
        </div>
        <!-- Update Label info system -->
        <div class="text-xs text-gray-400 hidden sm:block">Mode File System Storage</div>
    </div>

    <div class="flex-1 bg-gray-100 relative">
        <iframe src="stream_file.php?id=<?= $id ?>#toolbar=0&view=FitH"></iframe>
    </div>
</body>

</html>