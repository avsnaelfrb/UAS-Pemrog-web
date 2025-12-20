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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Membaca: <?= htmlspecialchars($book['title']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            overscroll-behavior-y: contain;
        }

        /* Container viewer yang benar-benar mengisi sisa layar */
        .viewer-container {
            height: calc(100vh - 64px);
            width: 100%;
            position: relative;
            background-color: #1e293b;
        }

        /* Perbaikan scroll iframe pada beberapa mobile browser */
        .pdf-frame {
            width: 100%;
            height: 100%;
            border: none;
            display: block;
        }

        /* Animasi halus untuk header */
        header {
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }
    </style>
</head>

<body class="bg-slate-900 overflow-hidden text-slate-200">

    <!-- HEADER / NAVIGATION BAR -->
    <header class="h-16 bg-slate-800 flex items-center justify-between px-3 md:px-8 z-50 relative">
        <div class="flex items-center gap-2 md:gap-4 overflow-hidden">
            <!-- Tombol Kembali yang lebih compact di mobile -->
            <a href="javascript:history.back()" class="p-2 md:p-2.5 rounded-xl bg-slate-700/50 hover:bg-slate-700 transition-all text-white flex items-center gap-2 group shrink-0">
                <i data-lucide="arrow-left" class="w-5 h-5 group-hover:-translate-x-1 transition-transform"></i>
                <span class="hidden md:inline font-bold text-sm uppercase tracking-wider">Kembali</span>
            </a>

            <div class="h-8 w-[1px] bg-slate-700 hidden sm:block shrink-0"></div>

            <!-- Judul & Info (Truncated properly on mobile) -->
            <div class="flex flex-col overflow-hidden">
                <h1 class="text-xs md:text-sm font-extrabold text-white truncate max-w-[150px] sm:max-w-xs md:max-w-md lg:max-w-xl">
                    <?= htmlspecialchars($book['title']) ?>
                </h1>
                <p class="text-[9px] md:text-[10px] text-slate-400 font-bold uppercase tracking-widest leading-none truncate mt-0.5">
                    <span class="text-blue-400"><?= $book['type'] ?></span> • <?= htmlspecialchars($book['author']) ?>
                </p>
            </div>
        </div>

        <!-- Action Button (Info) -->
        <div class="flex items-center gap-2 shrink-0 ml-2">
            <a href="detail.php?id=<?= $id ?>" class="p-2 rounded-xl bg-slate-700/50 hover:bg-blue-600 hover:text-white transition-all text-slate-300 shadow-sm" title="Info Lengkap">
                <i data-lucide="info" class="w-5 h-5"></i>
            </a>
        </div>
    </header>

    <!-- VIEWER AREA -->
    <main class="viewer-container">
        <?php if ($file_exists): ?>
            <!-- PDF Viewer with direct stream -->
            <iframe
                src="<?= $file_path ?>#toolbar=1&navpanes=0&scrollbar=1&view=FitH"
                class="pdf-frame"
                title="Membaca Karya Digital">
            </iframe>
            
        <?php else: ?>
            <!-- State: File Not Found -->
            <div class="flex items-center justify-center h-full px-6 text-center">
                <div class="max-w-sm w-full p-8 bg-slate-800 rounded-[2.5rem] border border-slate-700 shadow-2xl">
                    <div class="w-20 h-20 bg-red-500/10 text-red-500 rounded-3xl flex items-center justify-center mx-auto mb-6 transform -rotate-6">
                        <i data-lucide="file-warning" class="w-10 h-10"></i>
                    </div>
                    <h2 class="text-white font-black text-2xl mb-3">Berkas Hilang</h2>
                    <p class="text-slate-400 text-sm leading-relaxed mb-8">Maaf, file PDF untuk karya ini tidak dapat ditemukan di server kami. Silakan hubungi admin jika masalah berlanjut.</p>
                    <a href="<?= $back_link ?>" class="flex items-center justify-center gap-3 bg-blue-600 hover:bg-blue-700 text-white py-4 rounded-2xl font-black transition-all transform active:scale-95 shadow-lg shadow-blue-900/20">
                        <i data-lucide="layout-grid" class="w-5 h-5"></i>
                        Kembali ke Katalog
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script>
        // Inisialisasi ikon Lucide
        lucide.createIcons();

        // Mencegah interaksi yang tidak diinginkan pada viewer di mobile (opsional)
        window.addEventListener('load', () => {
            console.log('Reader initialized');
        });
    </script>
</body>

</html>