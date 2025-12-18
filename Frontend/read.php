<?php

require_once dirname(__DIR__) . '/Backend/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("Buku tidak dipilih.");
}

$id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

$query = "SELECT * FROM books WHERE id = $id";
$result = mysqli_query($conn, $query);
$book = mysqli_fetch_assoc($result);

if (!$book) {
    die("Buku tidak ditemukan.");
}

$from_page = isset($_GET['from']) ? $_GET['from'] : '';

if ($from_page === 'detail') {
    $back_url = "detail.php?id=$id";
} elseif ($_SESSION['role'] == 'ADMIN') {
    $back_url = "dashboard-admin.php" . ($from_page ? "?page=$from_page" : "");
} elseif ($_SESSION['role'] == 'PENERBIT') {
    $back_url = "dashboard-publisher.php";
} else {
    $back_url = "detail.php?id=$id";
}

if ($_SESSION['role'] == 'USER') {
    mysqli_query($conn, "INSERT IGNORE INTO history (user_id, book_id) VALUES ($user_id, $id)");
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membaca: <?= htmlspecialchars($book['title']) ?> - E-Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-900 h-screen flex flex-col overflow-hidden">

    <!-- Top Navigation -->
    <header class="bg-gray-800 text-white p-4 flex justify-between items-center border-b border-gray-700 z-10">
        <div class="flex items-center gap-4">
            <a href="<?= $back_url ?>" class="flex items-center gap-2 bg-gray-700 hover:bg-gray-600 px-4 py-2 rounded-lg transition font-bold text-sm">
                <span>⬅️</span> Kembali
            </a>
            <div class="hidden md:block">
                <h1 class="text-sm font-bold truncate max-w-md"><?= htmlspecialchars($book['title']) ?></h1>
                <p class="text-[10px] text-gray-400">Mode Membaca Digital</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <span class="bg-blue-600 text-white text-[10px] font-black px-2 py-1 rounded"><?= $book['type'] ?></span>
        </div>
    </header>

    <!-- Reader Container -->
    <main class="flex-1 bg-gray-500 relative">
        <?php if (!empty($book['file_path'])): ?>
            <!-- Viewer PDF menggunakan stream_file.php agar file di folder uploads terlindungi -->
            <iframe
                src="stream_file.php?id=<?= $id ?>"
                class="w-full h-full border-none"
                title="PDF Viewer"></iframe>
        <?php elseif (!empty($book['link'])): ?>
            <!-- Jika berupa link eksternal -->
            <div class="flex flex-col items-center justify-center h-full text-white bg-gray-800 p-8 text-center">
                <div class="text-5xl mb-4">🔗</div>
                <h2 class="text-xl font-bold mb-2">Buku ini tersedia di platform eksternal</h2>
                <p class="text-gray-400 mb-6 max-w-sm">Klik tombol di bawah untuk membuka sumber asli buku ini.</p>
                <a href="<?= htmlspecialchars($book['link']) ?>" target="_blank" class="bg-blue-600 hover:bg-blue-700 px-8 py-3 rounded-full font-bold transition">Buka Tautan Eksternal</a>
            </div>
        <?php else: ?>
            <div class="flex items-center justify-center h-full text-white">
                <p>File tidak tersedia atau sedang bermasalah.</p>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer Status -->
    <footer class="bg-gray-800 p-2 text-center text-[10px] text-gray-500">
        © <?= date('Y') ?> E-Library Digital System - Menjaga Privasi & Keamanan Konten
    </footer>

</body>

</html>