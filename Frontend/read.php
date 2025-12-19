<?php
require_once dirname(__DIR__) . '/Backend/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: dashboard-user.php");
    exit;
}

$id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Ambil data buku
$result = mysqli_query($conn, "SELECT * FROM books WHERE id=$id");
$book = mysqli_fetch_assoc($result);

if (!$book) {
    echo "<script>alert('Buku tidak ditemukan.'); window.history.back();</script>";
    exit;
}

// LOGIKA UPDATE RIWAYAT: Selalu perbarui waktu baca ke sekarang (NOW)
// Ini memastikan fitur "Terakhir Dibaca" di history.php selalu akurat
mysqli_query($conn, "INSERT INTO history (user_id, book_id, read_at) 
                     VALUES ($user_id, $id, NOW()) 
                     ON DUPLICATE KEY UPDATE read_at = NOW()");

// Jika tipe artikel/link, langsung arahkan ke link tersebut
if ($book['type'] == 'ARTICLE' && !empty($book['link'])) {
    header("Location: " . $book['link']);
    exit;
}

// Tentukan link kembali dinamis
$back_link = 'dashboard-user.php';
if ($role == 'PENERBIT') $back_link = 'dashboard-publisher.php';
if ($role == 'ADMIN') $back_link = 'dashboard-admin.php';

// Cek keberadaan file PDF
$file_path = '../uploads/books/' . $book['file_path'];
$file_exists = (!empty($book['file_path']) && file_exists($file_path));
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membaca: <?= htmlspecialchars($book['title']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        /* Menyesuaikan tinggi viewer agar memenuhi layar dikurangi tinggi header */
        .viewer-container {
            height: calc(100vh - 64px);
        }
    </style>
</head>

<body class="bg-gray-900 overflow-hidden">

    <!-- HEADER / NAVIGATION BAR -->
    <header class="h-16 bg-blue-300 border-b flex items-center justify-between px-4 lg:px-8 shadow-sm z-50 relative">
        <div class="flex items-center gap-4">
            <a href="javascript:history.back()" class="p-2 hover:bg-gray-100 rounded-full transition text-gray-600 flex items-center gap-2 group">
                <i data-lucide="arrow-left" class="w-5 h-5 group-hover:-translate-x-1 transition-transform"></i>
                <span class="hidden sm:inline font-medium">Kembali</span>
            </a>
            <div class="h-8 w-[1px] bg-gray-200 hidden sm:block"></div>
            <div class="flex flex-col">
                <h1 class="text-sm font-bold text-gray-900 line-clamp-1 max-w-[200px] md:max-w-md">
                    <?= htmlspecialchars($book['title']) ?>
                </h1>
                <p class="text-[10px] text-gray-600 uppercase font-black tracking-widest leading-none">
                    <?= $book['type'] ?> • <?= htmlspecialchars($book['author']) ?>
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a href="detail.php?id=<?= $id ?>" class="p-2 text-gray-500 hover:text-blue-600 transition" title="Info Buku">
                <i data-lucide="info" class="w-5 h-5"></i>
            </a>
        </div>
    </header>

    <!-- VIEWER AREA -->
    <main class="viewer-container w-full bg-gray-800 flex items-center justify-center">
        <?php if ($file_exists): ?>
            <!-- Menggunakan Iframe untuk menampilkan PDF agar UI HTML tetap terlihat -->
            <iframe
                src="<?= $file_path ?>#toolbar=1&navpanes=0&scrollbar=1"
                class="w-full h-full border-none"
                title="PDF Viewer">
            </iframe>
        <?php else: ?>
            <div class="text-center p-8 bg-gray-700 rounded-2xl border border-gray-600 shadow-xl max-w-sm mx-4">
                <div class="w-16 h-16 bg-red-900/30 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="file-warning" class="w-8 h-8"></i>
                </div>
                <h2 class="text-white font-bold text-xl mb-2">File Tidak Ditemukan</h2>
                <p class="text-gray-400 text-sm mb-6">Maaf, file PDF untuk buku ini tidak tersedia atau telah dihapus dari server.</p>
                <a href="<?= $back_link ?>" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-xl font-bold transition">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i> Kembali ke Katalog
                </a>
            </div>
        <?php endif; ?>
    </main>

    <script>
        // Inisialisasi ikon Lucide
        lucide.createIcons();

        // Mencegah klik kanan untuk keamanan dasar (opsional)
        /*
        document.addEventListener('contextmenu', event => event.preventDefault());
        */
    </script>
</body>

</html>