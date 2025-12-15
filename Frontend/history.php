<?php
require '../Backend/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Query History: Join tabel history dengan books dan genres
$sql = "
    SELECT b.id, b.title, b.author, b.cover, b.type, h.read_at,
    GROUP_CONCAT(g.name SEPARATOR ', ') as genre_names 
    FROM history h
    JOIN books b ON h.book_id = b.id
    LEFT JOIN book_genres bg ON b.id = bg.book_id 
    LEFT JOIN genres g ON bg.genre_id = g.id 
    WHERE h.user_id = $user_id
    GROUP BY b.id 
    ORDER BY h.read_at DESC
";

$books = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Baca - E-Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .sidebar-transition {
            transition: transform 0.3s ease-in-out;
        }
    </style>
</head>

<body class="bg-gray-50 font-sans">

    <!-- OVERLAY MOBILE -->
    <div id="mobile-overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden glass-effect"></div>

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

                <a href="dashboard-user.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg font-medium transition">
                    <span>📚</span> Katalog Buku
                </a>
                <!-- Menu History Aktif -->
                <a href="history.php" class="flex items-center gap-3 px-4 py-3 bg-blue-50 text-blue-700 rounded-lg font-medium border border-blue-100">
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
                    <h1 class="font-bold text-blue-900 text-lg">Riwayat Baca</h1>
                </div>
                <a href="profile.php" class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center text-sm border border-blue-200">
                    👤
                </a>
            </div>

            <div class="max-w-6xl mx-auto">
                <div class="bg-white p-6 rounded-xl shadow-sm border mb-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">🕒 Aktivitas Terakhir</h2>
                    <p class="text-gray-500 text-sm">Daftar buku yang baru saja Anda baca.</p>
                </div>

                <?php if (mysqli_num_rows($books) > 0): ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        <?php while ($book = mysqli_fetch_assoc($books)) { ?>
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-lg transition duration-300 flex flex-col h-full group relative">

                                <!-- Label Waktu Baca -->
                                <div class="absolute top-3 left-3 z-10">
                                    <span class="px-2 py-1 bg-black bg-opacity-60 text-white text-[10px] rounded backdrop-blur-sm">
                                        🕒 <?= date('d M Y, H:i', strtotime($book['read_at'])) ?>
                                    </span>
                                </div>

                                <!-- Cover -->
                                <div class="h-64 bg-gray-100 relative overflow-hidden rounded-t-xl">
                                    <?php if ($book['cover']): ?>
                                        <img src="data:image/jpeg;base64,<?= base64_encode($book['cover']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                                    <?php else: ?>
                                        <div class="w-full h-full flex flex-col items-center justify-center text-gray-400 bg-gray-50">
                                            <span class="text-4xl mb-2">📚</span>
                                            <span class="text-xs">No Cover</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="p-5 flex-1 flex flex-col">
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="text-xs font-semibold text-blue-600 tracking-wide uppercase bg-blue-50 px-2 py-1 rounded">
                                            <?= $book['type'] ?>
                                        </div>
                                    </div>

                                    <h3 class="font-bold text-gray-900 text-lg mb-1 leading-snug line-clamp-2" title="<?= htmlspecialchars($book['title']) ?>">
                                        <?= htmlspecialchars($book['title']) ?>
                                    </h3>
                                    <p class="text-sm text-gray-500 mb-3"><?= htmlspecialchars($book['author']) ?></p>

                                    <div class="mt-auto pt-4 border-t border-gray-100">
                                        <a href="detail.php?id=<?= $book['id'] ?>" class="block w-full text-center py-2.5 bg-gray-100 text-gray-700 hover:bg-blue-600 hover:text-white rounded-lg font-medium transition">
                                            Lanjutkan Membaca
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center py-20 bg-white rounded-xl border border-dashed border-gray-300 text-center">
                        <div class="text-4xl mb-4">💤</div>
                        <h3 class="text-lg font-medium text-gray-900">Belum ada riwayat baca</h3>
                        <p class="text-gray-500 text-sm mb-4">Ayo mulai membaca buku pertamamu!</p>
                        <a href="dashboard-user.php" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-bold transition">
                            Cari Buku
                        </a>
                    </div>
                <?php endif; ?>
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