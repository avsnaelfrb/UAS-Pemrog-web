<?php
require '../Backend/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: dashboard-user.php");
    exit;
}

$id = (int)$_GET['id'];

// Ambil Detail Buku
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
    echo "<script>alert('Buku tidak ditemukan!'); window.location='dashboard-user.php';</script>";
    exit;
}
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

    <!-- OVERLAY MOBILE -->
    <div id="mobile-overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>

    <div class="flex min-h-screen">

        <!-- SIDEBAR -->
        <aside id="sidebar" class="w-64 bg-white shadow-xl fixed inset-y-0 left-0 z-40 border-r transform -translate-x-full lg:translate-x-0 sidebar-transition h-full overflow-y-auto">
            <div class="p-6 border-b flex flex-col items-center relative">
                <button onclick="toggleSidebar()" class="absolute top-4 right-4 lg:hidden text-gray-500 hover:text-red-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center text-2xl mb-3">👤</div>
                <h1 class="text-xl font-bold text-blue-900">E-Library</h1>
                <p class="text-xs text-gray-500 mt-1 text-center">Halo, <?= htmlspecialchars($_SESSION['name']) ?></p>
            </div>

            <nav class="p-4 space-y-2">
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'ADMIN'): ?>
                    <a href="dashboard-admin.php" class="flex items-center gap-3 px-4 py-3 bg-indigo-600 text-white rounded-lg font-bold shadow-md hover:bg-indigo-700 transition mb-6 ring-2 ring-indigo-200">
                        <span>⚡</span> Admin Panel
                    </a>
                <?php endif; ?>

                <a href="dashboard-user.php" class="flex items-center gap-3 px-4 py-3 bg-blue-50 text-blue-700 rounded-lg font-medium border border-blue-100">
                    <span>📚</span> Katalog Buku
                </a>
                <!-- MENU HISTORY BARU -->
                <a href="history.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg font-medium transition">
                    <span>🕒</span> Riwayat Baca
                </a>

                <a href="profile.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg font-medium transition">
                    <span>⚙️</span> Profil Saya
                </a>
                <a href="logout.php" class="flex items-center gap-3 px-4 py-3 text-red-600 hover:bg-red-50 rounded-lg mt-auto pt-4 border-t">
                    <span>🚪</span> Keluar
                </a>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 lg:ml-64 p-4 lg:p-8 transition-all duration-300">

            <!-- HEADER MOBILE -->
            <div class="lg:hidden flex items-center justify-between bg-white p-4 rounded-xl shadow-sm border mb-6 sticky top-0 z-20">
                <div class="flex items-center gap-3">
                    <button onclick="toggleSidebar()" class="text-gray-700 p-2 hover:bg-gray-100 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path>
                        </svg>
                    </button>
                    <h1 class="font-bold text-blue-900 text-lg">Detail Buku</h1>
                </div>
            </div>

            <div class="max-w-5xl mx-auto">
                <!-- Tombol Kembali -->
                <a href="dashboard-user.php" class="inline-flex items-center text-gray-500 hover:text-blue-600 mb-6 font-medium transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Kembali ke Katalog
                </a>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="flex flex-col md:flex-row">

                        <!-- Kolom Kiri: Cover & Tombol Desktop -->
                        <div class="md:w-1/3 lg:w-1/4 bg-gray-50 p-6 md:p-8 border-r border-gray-100 flex flex-col items-center">
                            <!-- Cover Image -->
                            <div class="relative w-full aspect-[2/3] rounded-lg shadow-lg overflow-hidden bg-gray-200 mb-6">
                                <?php if ($book['cover']): ?>
                                    <img src="data:image/jpeg;base64,<?= base64_encode($book['cover']) ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full flex flex-col items-center justify-center text-gray-400">
                                        <span class="text-5xl mb-2">📚</span>
                                        <span class="text-sm">No Cover</span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- TOMBOL BACA (DESKTOP VERSION) -->
                            <!-- Class 'hidden md:block' artinya Sembunyi di Mobile, Muncul di Desktop -->
                            <div class="hidden md:block w-full">
                                <?php if ($book['file_exists']): ?>
                                    <a href="read.php?id=<?= $book['id'] ?>" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-lg shadow-blue-200 text-center transition transform hover:-translate-y-1 flex items-center justify-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                        </svg>
                                        Baca Sekarang
                                    </a>
                                <?php else: ?>
                                    <button disabled class="w-full py-3 bg-gray-300 text-gray-500 font-bold rounded-lg cursor-not-allowed">
                                        File Tidak Tersedia
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Kolom Kanan: Detail & Tombol Mobile -->
                        <div class="md:w-2/3 lg:w-3/4 p-6 md:p-8 flex flex-col h-full">

                            <!-- Informasi Buku -->
                            <div class="flex-1">
                                <div class="flex flex-wrap items-center gap-2 mb-4">
                                    <span class="px-3 py-1 bg-blue-100 text-blue-800 text-xs font-bold rounded-full border border-blue-200 uppercase tracking-wide">
                                        <?= $book['type'] ?>
                                    </span>
                                    <span class="px-3 py-1 bg-gray-100 text-gray-600 text-xs font-bold rounded-full border border-gray-200">
                                        Tahun: <?= $book['year'] ?>
                                    </span>
                                </div>

                                <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2 leading-tight">
                                    <?= htmlspecialchars($book['title']) ?>
                                </h1>
                                <p class="text-lg text-gray-600 mb-6 font-medium">
                                    Penulis: <span class="text-gray-900"><?= htmlspecialchars($book['author']) ?></span>
                                </p>

                                <!-- Genre List -->
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

                                <!-- Deskripsi -->
                                <div class="prose max-w-none text-gray-700">
                                    <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-2">Sinopsis / Deskripsi</h3>
                                    <div class="bg-gray-50 p-5 rounded-xl border border-gray-100 text-sm leading-relaxed whitespace-pre-line">
                                        <?= !empty($book['description']) ? htmlspecialchars($book['description']) : '<em class="text-gray-400">Tidak ada deskripsi tersedia untuk buku ini.</em>' ?>
                                    </div>
                                </div>
                            </div>

                            <!-- TOMBOL BACA (MOBILE VERSION) -->
                            <!-- Class 'block md:hidden' artinya Muncul di Mobile, Sembunyi di Desktop -->
                            <div class="block md:hidden mt-8 pt-6 border-t border-gray-100">
                                <?php if ($book['file_exists']): ?>
                                    <a href="read.php?id=<?= $book['id'] ?>" class="w-full py-4 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-lg shadow-blue-200 text-center transition flex items-center justify-center gap-2 text-lg">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                        </svg>
                                        Baca Sekarang
                                    </a>
                                <?php else: ?>
                                    <button disabled class="w-full py-4 bg-gray-300 text-gray-500 font-bold rounded-xl cursor-not-allowed">
                                        File Tidak Tersedia
                                    </button>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- Script Sidebar Mobile -->
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