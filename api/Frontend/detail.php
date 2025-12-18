<?php
require './api/Backend/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Cek Role & ID
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'USER';
if (!isset($_GET['id'])) {
    if ($role == 'PENERBIT') header("Location: dashboard-publisher.php");
    else if ($role == 'ADMIN') header("Location: dashboard-admin.php");
    else header("Location: dashboard-user.php");
    exit;
}

$id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// --- CONFIG TEMA DINAMIS ---
$theme = ($role == 'PENERBIT') ? 'purple' : 'blue';

// Variabel Kelas CSS Tailwind
$bg_soft = "bg-$theme-50";
$bg_main = "bg-$theme-600";
$bg_hover = "hover:bg-$theme-700";
$text_main = "text-$theme-700";
$text_dark = "text-$theme-900";
$border_main = "border-$theme-100";
$hover_soft = "hover:bg-$theme-50";
$hover_text = "hover:text-$theme-700";

// --- LOGIKA REQUEST PENERBIT ---
if (isset($_POST['request_publisher']) && $role == 'USER') {
    mysqli_query($conn, "UPDATE users SET request_penerbit='1' WHERE id=$user_id");
    echo "<script>alert('Permintaan dikirim! Tunggu konfirmasi Admin.');</script>";
}

// --- LOGIKA SAVE/UNSAVE BOOK ---
$msg_save = '';
$check_save = mysqli_query($conn, "SELECT id FROM saved_books WHERE user_id=$user_id AND book_id=$id");
$is_saved = (mysqli_num_rows($check_save) > 0);

if (isset($_POST['toggle_save'])) {
    if ($is_saved) {
        mysqli_query($conn, "DELETE FROM saved_books WHERE user_id=$user_id AND book_id=$id");
        $is_saved = false;
        $msg_save = "Buku dihapus dari koleksi.";
    } else {
        mysqli_query($conn, "INSERT INTO saved_books (user_id, book_id) VALUES ($user_id, $id)");
        $is_saved = true;
        $msg_save = "Buku berhasil disimpan ke koleksi!";
    }
}

// Ambil Data User
$u_res = mysqli_query($conn, "SELECT * FROM users WHERE id=$user_id");
$current_user = mysqli_fetch_assoc($u_res);

// Ambil Detail Buku (Termasuk kolom link)
$query = "
    SELECT b.*, 
    GROUP_CONCAT(g.name SEPARATOR ', ') as genre_names,
    CASE WHEN file_path IS NOT NULL AND LENGTH(file_path) > 0 THEN 1 ELSE 0 END as file_exists
    FROM books b 
    LEFT JOIN book_genres bg ON b.id = bg.book_id 
    LEFT JOIN genres g ON bg.genre_id = g.id 
    WHERE b.id = $id
    GROUP BY b.id
";
$result = mysqli_query($conn, $query);
$book = mysqli_fetch_assoc($result);

if (!$book) {
    echo "<script>alert('Buku tidak ditemukan!'); window.history.back();</script>";
    exit;
}

$coverPath = '../uploads/covers/' . $book['cover'];
$hasCover = (!empty($book['cover']) && file_exists($coverPath));

// Link Kembali Dinamis
$back_link = 'dashboard-user.php';
if ($role == 'PENERBIT') $back_link = 'dashboard-publisher.php';
if ($role == 'ADMIN') $back_link = 'dashboard-admin.php';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail: <?= htmlspecialchars($book['title']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .sidebar-transition {
            transition: transform 0.3s ease-in-out;
        }
    </style>
</head>

<body class="bg-gray-50 font-sans">

    <?php if ($msg_save): ?>
        <div onclick="this.remove()" class="fixed top-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg z-50 cursor-pointer animate-bounce">
            ✅ <?= $msg_save ?>
        </div>
    <?php endif; ?>

    <div id="mobile-overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden bg-blur"></div>

    <div class="flex min-h-screen">

        <!-- SIDEBAR -->
        <aside id="sidebar" class="w-64 bg-white shadow-xl fixed inset-y-0 left-0 z-40 border-r transform -translate-x-full lg:translate-x-0 sidebar-transition h-full overflow-y-auto">
            <div class="p-6 border-b flex flex-col items-center relative">
                <button onclick="toggleSidebar()" class="absolute top-4 right-4 lg:hidden text-gray-500 hover:text-red-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                <div class="w-16 h-16 <?= $bg_soft ?> <?= $text_main ?> rounded-full flex items-center justify-center text-2xl mb-3">
                    <?= ($role == 'PENERBIT') ? '✒️' : '👤' ?>
                </div>
                <h1 class="text-xl font-bold <?= $text_dark ?>">
                    <?= ($role == 'PENERBIT') ? 'Publisher' : 'E-Library' ?>
                </h1>
                <p class="text-xs text-gray-500 mt-1 text-center">Halo, <?= htmlspecialchars($current_user['name']) ?></p>
            </div>

            <nav class="p-4 space-y-2">
                <?php if ($role == 'ADMIN'): ?>
                    <a href="dashboard-admin.php" class="flex items-center gap-3 px-4 py-3 bg-indigo-600 text-white rounded-lg font-bold shadow-md hover:bg-indigo-700 transition mb-6 ring-2 ring-indigo-200">
                        <span>⚡</span> Admin Panel
                    </a>
                <?php endif; ?>

                <a href="<?= $back_link ?>" class="flex items-center gap-3 px-4 py-3 <?= $bg_soft ?> <?= $text_main ?> rounded-lg font-medium border <?= $border_main ?> shadow-sm">
                    <span>📚</span> Katalog
                </a>

                <?php if ($role == 'PENERBIT'): ?>
                    <a href="my_publications.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 <?= $hover_soft ?> <?= $hover_text ?> rounded-lg font-medium transition">
                        <span>📂</span> Terbitan Saya
                    </a>
                    <a href="upload.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 <?= $hover_soft ?> <?= $hover_text ?> rounded-lg font-medium transition">
                        <span>📤</span> Upload Karya
                    </a>
                <?php endif; ?>

                <a href="history.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 <?= $hover_soft ?> <?= $hover_text ?> rounded-lg font-medium transition">
                    <span>🕒</span> Riwayat
                </a>
                <a href="saved_books.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 <?= $hover_soft ?> <?= $hover_text ?> rounded-lg font-medium transition">
                    <span>🔖</span> Koleksi
                </a>
                <a href="profile.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 <?= $hover_soft ?> <?= $hover_text ?> rounded-lg font-medium transition">
                    <span>⚙️</span> Profile
                </a>
                <!-- TOMBOL REQUEST PENERBIT -->
                <div class="pt-4 mt-4 border-t border-gray-200">
                    <?php if ($current_user['request_penerbit'] == '0'): ?>
                        <form method="POST">
                            <button type="submit" name="request_publisher" onclick="return confirm('Ingin mengajukan diri sebagai Penerbit?')" class="w-full text-left flex items-center gap-3 px-4 py-3 bg-purple-50 text-purple-700 hover:bg-purple-50 hover:text-purple-700 rounded-lg font-medium transition duration-200">
                                <span>✒️</span> Jadi Penerbit
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="px-4 py-3 bg-gray-100 text-gray-500 rounded-lg text-xs italic border text-center">
                            ⏳ Menunggu Konfirmasi Penerbit
                        </div>
                    <?php endif; ?>
                </div>
                <a href="logout.php" class="flex items-center gap-3 px-4 py-3 text-red-600 hover:bg-red-50 rounded-lg mt-auto pt-4 border-t">
                    <span>🚪</span> Keluar
                </a>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 lg:ml-64 p-4 lg:p-8 transition-all duration-300">
            <div class="lg:hidden flex items-center justify-between bg-white p-4 rounded-xl shadow-sm border mb-6 sticky top-0 z-20">
                <div class="flex items-center gap-3">
                    <button onclick="toggleSidebar()" class="text-gray-700 p-2 hover:bg-gray-100 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path>
                        </svg>
                    </button>
                    <h1 class="font-bold <?= $text_dark ?> text-lg">Detail Buku</h1>
                </div>
                <div class="w-8 h-8 <?= $bg_soft ?> rounded-full flex items-center justify-center text-sm border <?= $border_main ?>">
                    <?= ($role == 'PENERBIT') ? '✒️' : '👤' ?>
                </div>
            </div>

            <div class="max-w-5xl mx-auto">
                <a href="<?= $back_link ?>" class="inline-flex items-center text-gray-500 <?= $hover_text ?> mb-6 font-medium transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Kembali ke Katalog
                </a>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="flex flex-col md:flex-row">
                        <div class="md:w-1/3 lg:w-1/4 bg-gray-50 p-6 md:p-8 border-r border-gray-100 flex flex-col items-center">
                            <div class="relative w-full aspect-[2/3] rounded-lg shadow-lg overflow-hidden bg-gray-200 mb-6">
                                <?php if ($hasCover): ?>
                                    <img src="<?= $coverPath ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full flex flex-col items-center justify-center text-gray-400">
                                        <span class="text-5xl mb-2">📚</span>
                                        <span class="text-sm">No Cover</span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- TOMBOL ACTION (LOGIKA BARU) -->
                            <div class="w-full flex flex-col gap-3">
                                <?php if ($book['type'] == 'ARTICLE' && !empty($book['link'])): ?>
                                    <!-- Tipe Artikel: Buka Link -->
                                    <a href="<?= htmlspecialchars($book['link']) ?>" target="_blank" class="w-full py-3 <?= $bg_main ?> <?= $bg_hover ?> text-white font-bold rounded-lg shadow-lg shadow-<?= $theme ?>-200 text-center transition transform hover:-translate-y-1 flex items-center justify-center gap-2">
                                        <span>🌐</span> Buka Artikel
                                    </a>
                                <?php elseif ($book['file_exists']): ?>
                                    <!-- Tipe Buku/Jurnal: Buka PDF -->
                                    <a href="read.php?id=<?= $book['id'] ?>" class="w-full py-3 <?= $bg_main ?> <?= $bg_hover ?> text-white font-bold rounded-lg shadow-lg shadow-<?= $theme ?>-200 text-center transition transform hover:-translate-y-1 flex items-center justify-center gap-2">
                                        <span>📖</span> Baca Sekarang
                                    </a>
                                <?php else: ?>
                                    <button disabled class="w-full py-3 bg-gray-300 text-gray-500 font-bold rounded-lg cursor-not-allowed">
                                        File Tidak Tersedia
                                    </button>
                                <?php endif; ?>

                                <!-- Tombol Simpan -->
                                <form method="POST" class="w-full">
                                    <button type="submit" name="toggle_save" class="w-full py-3 border-2 <?= $is_saved ? 'border-yellow-400 bg-yellow-50 text-yellow-700' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300' ?> font-bold rounded-lg transition flex items-center justify-center gap-2">
                                        <span><?= $is_saved ? '🔖' : '🏷️' ?></span>
                                        <?= $is_saved ? 'Tersimpan' : 'Simpan' ?>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="md:w-2/3 lg:w-3/4 p-6 md:p-8 flex flex-col h-full">
                            <div class="flex-1">
                                <div class="flex flex-wrap items-center gap-2 mb-4">
                                    <span class="px-3 py-1 <?= $bg_soft ?> <?= $text_main ?> text-xs font-bold rounded-full border <?= $border_main ?> uppercase tracking-wide">
                                        <?= $book['type'] ?>
                                    </span>
                                    <span class="px-3 py-1 bg-gray-100 text-gray-600 text-xs font-bold rounded-full border border-gray-200">
                                        Tahun: <?= $book['year'] ?>
                                    </span>
                                </div>

                                <h1 class="text-3xl md:text-4xl font-bold <?= $text_dark ?> mb-2 leading-tight">
                                    <?= htmlspecialchars($book['title']) ?>
                                </h1>
                                <p class="text-lg text-gray-600 mb-6 font-medium">
                                    Penulis: <span class="text-gray-900"><?= htmlspecialchars($book['author']) ?></span>
                                </p>

                                <div class="mb-8">
                                    <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-2">Kategori Genre</h3>
                                    <div class="flex flex-wrap gap-2">
                                        <?php
                                        $genres = explode(',', $book['genre_names']);
                                        foreach ($genres as $g):
                                            if (trim($g) == '') continue;
                                        ?>
                                            <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium bg-gray-50 text-gray-700 border border-gray-200">
                                                🏷️ <?= trim($g) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="prose max-w-none text-gray-700">
                                    <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-2">Sinopsis / Deskripsi</h3>
                                    <div class="bg-gray-50 p-5 rounded-xl border border-gray-100 text-sm leading-relaxed whitespace-pre-line">
                                        <?= !empty($book['description']) ? htmlspecialchars($book['description']) : '<em class="text-gray-400">Tidak ada deskripsi tersedia untuk buku ini.</em>' ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobile-overlay');
            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            }
        }
    </script>
</body>

</html>