<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once dirname(__DIR__) . '/Backend/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'PENERBIT') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$name = $_SESSION['name'];

// Logika Hapus Buku
if (isset($_GET['delete'])) {
    $book_id = (int)$_GET['delete'];
    $check = mysqli_query($conn, "SELECT id FROM books WHERE id=$book_id AND uploaded_by=$user_id");

    if (mysqli_num_rows($check) > 0) {
        $q_file = mysqli_query($conn, "SELECT cover, file_path FROM books WHERE id=$book_id");
        $row_file = mysqli_fetch_assoc($q_file);

        $dirBooks = "../uploads/books/";
        $dirCovers = "../uploads/covers/";

        if (!empty($row_file['file_path']) && file_exists($dirBooks . $row_file['file_path'])) {
            unlink($dirBooks . $row_file['file_path']);
        }
        if (!empty($row_file['cover']) && file_exists($dirCovers . $row_file['cover'])) {
            unlink($dirCovers . $row_file['cover']);
        }

        if (mysqli_query($conn, "DELETE FROM books WHERE id=$book_id")) {
            $message = "Publikasi berhasil dihapus secara permanen.";
        }
    }
}

// Query Ambil Buku Milik Penerbit (Ditambah jumlah like dan genre)
$sql = "
    SELECT b.*, 
    COUNT(DISTINCT bl.id) as total_likes,
    GROUP_CONCAT(DISTINCT g.name SEPARATOR ', ') as genre_names
    FROM books b 
    LEFT JOIN book_likes bl ON b.id = bl.book_id 
    LEFT JOIN book_genres bg ON b.id = bg.book_id
    LEFT JOIN genres g ON bg.genre_id = g.id
    WHERE b.uploaded_by = $user_id 
    GROUP BY b.id 
    ORDER BY b.created_at DESC";
$my_books = mysqli_query($conn, $sql);
$u_res = mysqli_query($conn, "SELECT * FROM users WHERE id=$user_id");
$current_user = mysqli_fetch_assoc($u_res);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terbitan Saya - Publisher</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .sidebar-transition {
            transition: transform 0.3s ease-in-out;
        }

        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
    </style>
</head>

<body class="bg-gray-50">

    <!-- OVERLAY MOBILE -->
    <div id="mobile-overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden backdrop-blur-sm"></div>

    <?php if ($message): ?>
        <div onclick="this.remove()" class="fixed top-4 right-4 bg-gray-800 text-white px-6 py-3 rounded-xl shadow-2xl z-50 cursor-pointer animate-bounce flex items-center gap-3 border border-gray-700">
            <div class="bg-green-500 p-1 rounded-full text-white">
                <i data-lucide="check" class="w-4 h-4"></i>
            </div>
            <span class="text-sm font-bold"><?= $message ?></span>
        </div>
    <?php endif; ?>

    <div class="flex min-h-screen">
        <!-- SIDEBAR -->
        <aside id="sidebar" class="w-64 bg-white shadow-xl fixed inset-y-0 left-0 z-40 border-r transform -translate-x-full lg:translate-x-0 sidebar-transition h-full overflow-y-auto">
            <div class="p-6 border-b flex flex-col items-center relative">
                <button onclick="toggleSidebar()" class="absolute top-4 right-4 lg:hidden text-gray-500 hover:text-red-500">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
                <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center text-2xl mb-3">
                    <i data-lucide="pen-tool" class="w-8 h-8 text-purple-600"></i>
                </div>
                <h1 class="text-xl font-bold text-purple-900">Publisher</h1>
                <p class="text-xs text-gray-500 mt-1">Halo, <?= htmlspecialchars($current_user['name']) ?></p>
            </div>
            <nav class="p-4 space-y-2">
                <a href="home.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-purple-50 hover:text-purple-700 rounded-lg font-medium transition">
                    <i data-lucide="home" class="w-5 h-5"></i> Home
                </a>
                <a href="dashboard-publisher.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-purple-50 hover:text-purple-700 rounded-lg font-medium transition duration-200">
                    <i data-lucide="library" class="w-5 h-5"></i> Katalog
                </a>
                <a href="my_publications.php" class="flex items-center gap-3 px-4 py-3 bg-purple-50 text-purple-600 border  border-purple-100  rounded-lg font-medium transition duration-200 shadow-sm">
                    <i data-lucide="folder" class="w-5 h-5"></i> Terbitan Saya
                </a>
                <a href="upload.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 rounded-lg font-medium hover:bg-purple-50 hover:text-purple-700">
                    <i data-lucide="upload" class="w-5 h-5"></i> Upload Karya
                </a>
                <a href="history.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-purple-50 hover:text-purple-700 rounded-lg font-medium transition">
                    <i data-lucide="history" class="w-5 h-5"></i> Riwayat
                </a>
                <a href="saved_books.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-purple-50 hover:text-purple-700 rounded-lg font-medium transition duration-200">
                    <i data-lucide="bookmark" class="w-5 h-5"></i> Koleksi
                </a>
                <a href="profile.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-purple-50 hover:text-purple-700 rounded-lg font-medium transition">
                    <i data-lucide="settings" class="w-5 h-5"></i> Profile
                </a>
                <a href="logout.php" class="flex items-center gap-3 px-4 py-3 text-red-600 hover:bg-red-50 rounded-lg mt-auto pt-4 border-t">
                    <i data-lucide="log-out" class="w-5 h-5"></i> Keluar
                </a>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 lg:ml-64 p-4 lg:p-8 transition-all duration-300">
            <!-- HEADER MOBILE -->
            <div class="lg:hidden flex items-center justify-between bg-white p-4 rounded-2xl shadow-sm border mb-6 sticky top-0 z-20">
                <div class="flex items-center gap-3">
                    <button onclick="toggleSidebar()" class="text-gray-700 p-2 hover:bg-gray-100 rounded-xl">
                        <i data-lucide="menu" class="w-6 h-6"></i>
                    </button>
                    <h1 class="font-bold text-purple-900">Terbitan Saya</h1>
                </div>
                <a href="profile.php" class="w-9 h-9 bg-purple-100 rounded-full flex items-center justify-center text-sm border border-purple-200">
                    <i data-lucide="user" class="w-5 h-5 text-purple-600"></i>
                </a>
            </div>

            <div class="max-w-6xl mx-auto">
                <!-- BANNER STATISTIK (RESIZED) -->
                <div class="mb-8 bg-gradient-to-br from-purple-800 via-purple-900 to-indigo-950 text-white rounded-2xl p-6 lg:p-8 shadow-xl relative overflow-hidden flex flex-col md:flex-row justify-between items-center gap-6">
                    <div class="relative z-10 text-center md:text-left flex-1">
                        <h2 class="text-2xl lg:text-3xl font-bold mb-2 leading-tight">Manajemen Publikasi</h2>
                        <p class="text-purple-200 text-xs lg:text-sm max-w-md">Pantau status moderasi dan interaksi pembaca terhadap karya yang telah Anda bagikan secara real-time.</p>
                        <div class="flex flex-wrap gap-3 mt-6 justify-center md:justify-start">
                            <div class="bg-white/10 backdrop-blur-md px-4 py-2 rounded-xl border border-white/20 flex items-center gap-3">
                                <div class="w-8 h-8 bg-purple-500/20 rounded-lg flex items-center justify-center">
                                    <i data-lucide="book" class="w-4 h-4 text-purple-300"></i>
                                </div>
                                <div>
                                    <div class="text-[9px] uppercase font-bold text-purple-300 leading-none mb-1">Total Karya</div>
                                    <div class="text-xl font-black leading-none"><?= mysqli_num_rows($my_books) ?></div>
                                </div>
                            </div>
                            <a href="upload.php" class="bg-white text-purple-900 px-6 py-3 rounded-xl font-black uppercase hover:bg-purple-50 transition-all transform hover:scale-105 shadow-lg flex items-center gap-2 text-xs">
                                <i data-lucide="plus" class="w-4 h-4"></i> Upload Baru
                            </a>
                        </div>
                    </div>
                    <div class="relative z-10 hidden md:block opacity-20">
                        <i data-lucide="folder-kanban" class="w-32 h-32"></i>
                    </div>
                    <i data-lucide="sparkles" class="absolute -right-4 -bottom-4 w-48 h-48 text-white opacity-5 rotate-12"></i>
                </div>

                <!-- DAFTAR BUKU -->
                <?php if (mysqli_num_rows($my_books) > 0): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pb-20">
                        <?php while ($mb = mysqli_fetch_assoc($my_books)): ?>
                            <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-5 card-hover flex flex-col sm:flex-row gap-6">
                                <!-- Bagian Cover & Badges -->
                                <div class="w-full sm:w-40 h-56 bg-gray-100 rounded-2xl relative overflow-hidden flex-shrink-0 group">
                                    <?php
                                    $coverPathMb = '../uploads/covers/' . ($mb['cover'] ?? '');
                                    if (!empty($mb['cover']) && file_exists($coverPathMb)): ?>
                                        <img src="<?= $coverPathMb ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-700">
                                    <?php else: ?>
                                        <div class="w-full h-full flex flex-col items-center justify-center text-gray-400">
                                            <i data-lucide="image-off" class="w-10 h-10 mb-2"></i>
                                            <span class="text-[10px] font-bold uppercase">No Cover</span>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Floating Type -->
                                    <div class="absolute top-3 left-3">
                                        <span class="px-2 py-1 bg-black/50 backdrop-blur-md text-white text-[9px] font-black uppercase rounded-lg border border-white/20">
                                            <?= $mb['type'] ?>
                                        </span>
                                    </div>
                                </div>

                                <!-- Bagian Detail Konten -->
                                <div class="flex-1 flex flex-col">
                                    <div class="flex justify-between items-start mb-2">
                                        <!-- Status Badge -->
                                        <?php
                                        $statusClass = 'bg-yellow-50 text-yellow-600 border-yellow-100';
                                        $statusIcon = 'clock';
                                        if ($mb['status'] == 'APPROVED') {
                                            $statusClass = 'bg-green-50 text-green-600 border-green-100';
                                            $statusIcon = 'check-circle';
                                        }
                                        if ($mb['status'] == 'REJECTED') {
                                            $statusClass = 'bg-red-50 text-red-600 border-red-100';
                                            $statusIcon = 'x-circle';
                                        }
                                        ?>
                                        <div class="flex items-center gap-1.5 px-3 py-1 rounded-full border <?= $statusClass ?> text-[10px] font-bold uppercase tracking-tight">
                                            <i data-lucide="<?= $statusIcon ?>" class="w-3 h-3"></i>
                                            <?= $mb['status'] ?>
                                        </div>
                                        <span class="text-[10px] font-bold text-gray-400 uppercase italic"><?= date('d M Y', strtotime($mb['created_at'])) ?></span>
                                    </div>

                                    <h4 class="text-lg font-bold text-gray-900 leading-tight line-clamp-2 mb-1"><?= htmlspecialchars($mb['title']) ?></h4>
                                    <p class="text-sm font-medium text-gray-500 mb-4"><?= htmlspecialchars($mb['author']) ?> • <?= $mb['year'] ?></p>

                                    <!-- Tags & Stats -->
                                    <div class="flex flex-wrap gap-2 mb-6">
                                        <span class="flex items-center gap-1.5 px-2.5 py-1 bg-pink-50 text-pink-600 text-[10px] font-bold rounded-lg border border-pink-100">
                                            <i data-lucide="heart" class="w-3 h-3 fill-current"></i> <?= number_format($mb['total_likes']) ?> Likes
                                        </span>
                                        <?php if ($mb['genre_names']): foreach (array_slice(explode(',', $mb['genre_names']), 0, 2) as $g): ?>
                                                <span class="px-2.5 py-1 bg-gray-50 text-gray-400 text-[10px] font-medium rounded-lg border border-gray-200"><?= trim($g) ?></span>
                                        <?php endforeach;
                                        endif; ?>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="mt-auto grid grid-cols-3 gap-2 border-t border-gray-50 pt-4">
                                        <a href="detail.php?id=<?= $mb['id'] ?>" class="flex flex-col items-center justify-center p-2 rounded-2xl hover:bg-purple-50 text-purple-600 transition group">
                                            <i data-lucide="eye" class="w-5 h-5 mb-1 group-hover:scale-110 transition"></i>
                                            <span class="text-[9px] font-bold uppercase">Lihat</span>
                                        </a>
                                        <a href="edit_book.php?id=<?= $mb['id'] ?>" class="flex flex-col items-center justify-center p-2 rounded-2xl hover:bg-blue-50 text-blue-600 transition group">
                                            <i data-lucide="edit-3" class="w-5 h-5 mb-1 group-hover:scale-110 transition"></i>
                                            <span class="text-[9px] font-bold uppercase">Edit</span>
                                        </a>
                                        <a href="?delete=<?= $mb['id'] ?>" onclick="return confirm('Yakin ingin menghapus publikasi ini secara permanen? Seluruh data file dan cover akan ikut terhapus.')" class="flex flex-col items-center justify-center p-2 rounded-2xl hover:bg-red-50 text-red-500 transition group">
                                            <i data-lucide="trash-2" class="w-5 h-5 mb-1 group-hover:scale-110 transition"></i>
                                            <span class="text-[9px] font-bold uppercase">Hapus</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center py-24 bg-white rounded-[3rem] border border-dashed border-gray-300 text-center px-6">
                        <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mb-6 text-gray-300">
                            <i data-lucide="file-warning" class="w-12 h-12"></i>
                        </div>
                        <h3 class="text-xl font-black text-gray-900 mb-2">Belum ada karya yang diterbitkan</h3>
                        <p class="text-gray-500 text-sm mb-8 max-w-sm">Dunia menantikan karya Anda. Mulai unggah buku, jurnal, atau artikel perdana Anda sekarang.</p>
                        <a href="upload.php" class="px-10 py-4 bg-purple-600 text-white rounded-2xl font-black hover:bg-purple-700 transition-all transform hover:scale-105 shadow-xl flex items-center gap-3 uppercase tracking-wider text-xs">
                            <i data-lucide="upload-cloud" class="w-5 h-5"></i> Upload Sekarang
                        </a>
                    </div>
                <?php endif; ?>
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
                document.body.style.overflow = 'hidden';
            } else {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
                document.body.style.overflow = 'auto';
            }
        }

        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('mobile-overlay');
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
                document.body.style.overflow = 'auto';
            }
        });

        lucide.createIcons();
    </script>
</body>

</html>