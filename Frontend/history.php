<?php
require_once dirname(__DIR__) . '/Backend/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$message = '';

// Tema Dinamis sesuai peran
$themeClass = ($role === 'PENERBIT') ? 'purple' : 'blue';
$bg_soft = "bg-$themeClass-50";
$text_main = "text-$themeClass-700";
$border_main = "border-$themeClass-100";
$hover_soft = "hover:bg-$themeClass-50";
$btn_main = "bg-$themeClass-600 hover:bg-$themeClass-700";

if (isset($_POST['request_publisher'])) {
    mysqli_query($conn, "UPDATE users SET request_penerbit='1' WHERE id=$user_id");
    $message = "Permintaan dikirim! Tunggu konfirmasi Admin.";
}

$u_res = mysqli_query($conn, "SELECT * FROM users WHERE id=$user_id");
$current_user = mysqli_fetch_assoc($u_res);

// PERBAIKAN QUERY: Menghitung Like secara akurat (DISTINCT) dan mengambil waktu baca terbaru
$sql = "
    SELECT b.id, b.title, b.author, b.cover, b.type, MAX(h.read_at) as read_at,
    GROUP_CONCAT(DISTINCT g.name SEPARATOR ', ') as genre_names,
    COUNT(DISTINCT bl.id) as total_likes
    FROM history h
    JOIN books b ON h.book_id = b.id
    LEFT JOIN book_genres bg ON b.id = bg.book_id 
    LEFT JOIN genres g ON bg.genre_id = g.id 
    LEFT JOIN book_likes bl ON b.id = bl.book_id
    WHERE h.user_id = $user_id
    GROUP BY b.id 
    ORDER BY read_at DESC
";
$books = mysqli_query($conn, $sql);

if (!function_exists('time_ago')) {
    function time_ago($timestamp)
    {
        $time_ago = strtotime($timestamp);
        $current_time = time();
        $time_difference = $current_time - $time_ago;
        $seconds = $time_difference;

        $minutes      = round($seconds / 60);
        $hours        = round($seconds / 3600);
        $days         = round($seconds / 86400);
        $weeks        = round($seconds / 604800);
        $months       = round($seconds / 2629440);
        $years        = round($seconds / 31553280);

        if ($seconds <= 60) return "Baru saja";
        else if ($minutes <= 60) return "$minutes menit lalu";
        else if ($hours <= 24) return "$hours jam lalu";
        else if ($days <= 7) return "$days hari lalu";
        else if ($weeks <= 4.3) return "$weeks minggu lalu";
        else if ($months <= 12) return "$months bulan lalu";
        else return "$years tahun lalu";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Baca - E-Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .sidebar-transition {
            transition: transform 0.3s ease-in-out;
        }

        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .card-hover-effect:hover {
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }
    </style>
</head>

<body class="bg-gray-50 font-sans">

    <!-- OVERLAY MOBILE -->
    <div id="mobile-overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden backdrop-blur-sm"></div>

    <?php if ($message): ?>
        <div onclick="this.remove()" class="fixed top-4 right-4 bg-gray-800 text-white px-6 py-3 rounded-lg shadow-lg z-50 cursor-pointer animate-bounce flex items-center gap-2 border border-gray-700">
            <i data-lucide="info" class="w-5 h-5 text-yellow-400"></i> <?= $message ?>
        </div>
    <?php endif; ?>

    <div class="flex min-h-screen">

        <!-- SIDEBAR -->
        <aside id="sidebar" class="w-64 bg-white shadow-xl fixed inset-y-0 left-0 z-40 border-r transform -translate-x-full lg:translate-x-0 sidebar-transition h-full overflow-y-auto">
            <div class="p-6 border-b flex flex-col items-center relative">
                <button onclick="toggleSidebar()" class="absolute top-4 right-4 lg:hidden text-gray-500 hover:text-red-500">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
                <div class="w-16 h-16 <?= ($role == 'PENERBIT') ? 'bg-purple-100 text-purple-600' : 'bg-blue-100 text-blue-600' ?> rounded-full flex items-center justify-center text-2xl mb-3">
                    <?php if ($role == 'PENERBIT'): ?>
                        <i data-lucide="pen-tool" class="w-8 h-8"></i>
                    <?php else: ?>
                        <i data-lucide="user" class="w-8 h-8"></i>
                    <?php endif; ?>
                </div>
                <h1 class="text-xl font-bold <?= ($role == 'PENERBIT') ? 'text-purple-900' : 'text-blue-900' ?>">
                    <?= ($role == 'PENERBIT') ? 'Publisher' : 'E-Library' ?>
                </h1>
                <p class="text-xs text-gray-500 mt-1 text-center">Halo, <?= htmlspecialchars($current_user['name']) ?></p>
            </div>

            <nav class="p-4 space-y-2">
                <a href="home.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 <?= $hover_soft ?> hover:text-<?= $themeClass ?>-700 rounded-lg font-medium transition">
                    <i data-lucide="home" class="w-5 h-5"></i> Home
                </a>
                <?php $dash_link = ($role == 'PENERBIT') ? 'dashboard-publisher.php' : 'dashboard-user.php'; ?>
                <a href="<?= $dash_link ?>" class="flex items-center gap-3 px-4 py-3 text-gray-600 <?= $hover_soft ?> hover:text-<?= $themeClass ?>-700 rounded-lg font-medium transition">
                    <i data-lucide="library" class="w-5 h-5"></i> Katalog
                </a>

                <?php if ($role == 'PENERBIT'): ?>
                    <a href="my_publications.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 <?= $hover_soft ?> hover:text-<?= $themeClass ?>-700 rounded-lg font-medium transition">
                        <i data-lucide="folder" class="w-5 h-5"></i> Terbitan Saya
                    </a>
                    <a href="upload.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 <?= $hover_soft ?> hover:text-<?= $themeClass ?>-700 rounded-lg font-medium transition">
                        <i data-lucide="upload" class="w-5 h-5"></i> Upload Karya
                    </a>
                <?php endif; ?>

                <a href="history.php" class="flex items-center gap-3 px-4 py-3 <?= $bg_soft ?> <?= $text_main ?> rounded-lg font-medium border <?= $border_main ?> shadow-sm">
                    <i data-lucide="history" class="w-5 h-5"></i> Riwayat
                </a>

                <a href="saved_books.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 <?= $hover_soft ?> hover:text-<?= $themeClass ?>-700 rounded-lg font-medium transition">
                    <i data-lucide="bookmark" class="w-5 h-5"></i> Koleksi
                </a>

                <a href="profile.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 <?= $hover_soft ?> hover:text-<?= $themeClass ?>-700 rounded-lg font-medium transition">
                    <i data-lucide="settings" class="w-5 h-5"></i> Profile
                </a>

                <?php if ($role == 'USER'): ?>
                    <div class="pt-4 mt-4 border-t border-gray-200">
                        <?php if ($current_user['request_penerbit'] == '0'): ?>
                            <form method="POST">
                                <button type="submit" name="request_publisher" onclick="return confirm('Ingin mengajukan diri sebagai Penerbit?')" class="w-full text-left flex items-center gap-3 px-4 py-3 bg-purple-50 text-purple-700 hover:bg-purple-100 rounded-lg font-medium transition duration-200">
                                    <i data-lucide="pen-tool" class="w-5 h-5"></i> Jadi Penerbit
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="px-4 py-3 bg-gray-100 text-gray-500 rounded-lg text-xs italic border text-center flex items-center justify-center gap-2">
                                <i data-lucide="hourglass" class="w-4 h-4"></i> Menunggu Konfirmasi
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <a href="logout.php" class="flex items-center gap-3 px-4 py-3 text-red-600 hover:bg-red-50 rounded-lg mt-auto pt-4 border-t">
                    <i data-lucide="log-out" class="w-5 h-5"></i> Keluar
                </a>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 lg:ml-64 p-4 lg:p-8 transition-all duration-300">

            <!-- HEADER MOBILE -->
            <div class="lg:hidden flex items-center justify-between bg-white p-4 rounded-xl shadow-sm border mb-6 sticky top-0 z-20">
                <div class="flex items-center gap-3">
                    <button onclick="toggleSidebar()" class="text-gray-700 p-2 hover:bg-gray-100 rounded-lg">
                        <i data-lucide="menu" class="w-6 h-6"></i>
                    </button>
                    <h1 class="font-bold text-<?= $themeClass ?>-900 text-lg">Riwayat Baca</h1>
                </div>
                <a href="profile.php" class="w-9 h-9 bg-<?= $themeClass ?>-100 rounded-full flex items-center justify-center text-sm border border-<?= $themeClass ?>-200">
                    <i data-lucide="user" class="w-5 h-5 text-<?= $themeClass ?>-600"></i>
                </a>
            </div>

            <div class="max-w-6xl mx-auto">
                <!-- Banner Riwayat -->
                <div class="mb-8 bg-gradient-to-r from-<?= $themeClass ?>-800 to-indigo-900 text-white rounded-2xl p-6 lg:p-10 shadow-lg relative overflow-hidden flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="relative z-10 text-center md:text-left">
                        <h2 class="text-2xl lg:text-3xl font-bold mb-2 flex items-center justify-center md:justify-start gap-3">
                            <i data-lucide="history" class="w-8 h-8 text-<?= $themeClass ?>-300"></i> Aktivitas Terakhir
                        </h2>
                        <p class="text-<?= $themeClass ?>-200 text-sm lg:text-base">Lanjutkan perjalanan literasimu dari buku yang terakhir kamu baca.</p>
                    </div>
                    <div class="relative z-10 bg-white/10 backdrop-blur-md px-6 py-3 rounded-xl border border-white/20 text-center">
                        <div class="text-[10px] uppercase font-bold text-<?= $themeClass ?>-200 tracking-widest mb-1">Total Dibaca</div>
                        <div class="text-2xl font-black leading-none"><?= mysqli_num_rows($books) ?> Buku</div>
                    </div>
                    <i data-lucide="book-open" class="absolute -right-6 -bottom-6 w-32 h-32 text-white opacity-10 rotate-12"></i>
                </div>

                <?php if (mysqli_num_rows($books) > 0): ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 pb-20">
                        <?php while ($book = mysqli_fetch_assoc($books)) { ?>
                            <div class="bg-white rounded-2xl border border-gray-100 transition-all duration-300 flex flex-col h-full group card-hover-effect shadow-md hover:shadow-lg hover:shadow-<?= $themeClass ?>-100 transform hover:-translate-y-2 relative">

                                <div class="h-64 relative overflow-hidden rounded-t-2xl bg-gray-50">
                                    <?php
                                    $coverPath = '../uploads/covers/' . $book['cover'];
                                    if (!empty($book['cover']) && file_exists($coverPath)): ?>
                                        <img src="<?= $coverPath ?>" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                                    <?php else: ?>
                                        <div class="w-full h-full flex flex-col items-center justify-center text-gray-400">
                                            <i data-lucide="image-off" class="w-12 h-12 mb-2"></i>
                                            <span class="text-[10px] font-bold uppercase">No Cover</span>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Floating Labels: Type & Time -->
                                    <div class="absolute top-4 left-4 right-4 flex justify-between items-start">
                                        <div class="text-[10px] font-extrabold text-white tracking-wide uppercase bg-black/50 backdrop-blur-md px-2.5 py-1 rounded-lg border border-white/20">
                                            <?= $book['type'] ?>
                                        </div>
                                        <div class="bg-white/90 backdrop-blur-sm text-gray-900 px-2 py-1 rounded-lg flex items-center gap-1.5 shadow-sm border border-white">
                                            <i data-lucide="clock" class="w-3 h-3 text-<?= $themeClass ?>-600"></i>
                                            <span class="text-[9px] font-black uppercase text-gray-600"><?= time_ago($book['read_at']) ?></span>
                                        </div>
                                    </div>

                                    <div class="absolute bottom-4 left-4">
                                        <span class="flex items-center gap-1.5 px-3 py-1.5 bg-pink-600 text-white text-[11px] font-extrabold rounded-full shadow-lg">
                                            <i data-lucide="heart" class="w-3.5 h-3.5 fill-current"></i>
                                            <?= number_format($book['total_likes']) ?>
                                        </span>
                                    </div>

                                    <!-- Quick View Overlay -->
                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                                        <a href="detail.php?id=<?= $book['id'] ?>" class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-<?= $themeClass ?>-600 shadow-2xl transform scale-50 group-hover:scale-100 transition-transform duration-300">
                                            <i data-lucide="eye" class="w-6 h-6"></i>
                                        </a>
                                    </div>
                                </div>

                                <div class="p-5 flex flex-col flex-1">
                                    <div class="mb-3">
                                        <h4 class="font-extrabold text-gray-900 text-base mb-1 leading-tight line-clamp-2 group-hover:text-<?= $themeClass ?>-700 transition-colors" title="<?= htmlspecialchars($book['title']) ?>">
                                            <?= htmlspecialchars($book['title']) ?>
                                        </h4>
                                        <p class="text-xs font-medium text-gray-500 flex items-center gap-1">
                                            <i data-lucide="user" class="w-3.5 h-3.5"></i> <?= htmlspecialchars($book['author']) ?>
                                        </p>
                                    </div>

                                    <!-- Genre Tags -->
                                    <div class="flex flex-wrap gap-1.5 mb-5 mt-auto">
                                        <?php
                                        if ($book['genre_names']) {
                                            $genres = explode(',', $book['genre_names']);
                                            $displayGenres = array_slice($genres, 0, 2);
                                            foreach ($displayGenres as $gn) {
                                                if (trim($gn) == '') continue;
                                                echo '<span class="px-2.5 py-1 bg-gray-50 text-gray-600 text-[10px] font-extrabold rounded-md border border-gray-200 group-hover:bg-' . $themeClass . '-50 group-hover:text-' . $themeClass . '-600 group-hover:border-' . $themeClass . '-100 transition-all">' . trim($gn) . '</span>';
                                            }
                                            if (count($genres) > 2) {
                                                echo '<span class="text-[10px] text-gray-400 font-bold ml-1">+' . (count($genres) - 2) . '</span>';
                                            }
                                        }
                                        ?>
                                    </div>

                                    <div class="mt-auto pt-4 border-t border-gray-100">
                                        <a href="read.php?id=<?= $book['id'] ?>" class="flex items-center justify-center gap-2 w-full py-2.5 bg-<?= $themeClass ?>-600 hover:bg-<?= $themeClass ?>-700 text-white rounded-xl font-bold text-xs transition-all shadow-md shadow-<?= $themeClass ?>-100 group-hover:shadow-lg">
                                            Lanjutkan Membaca <i data-lucide="arrow-right" class="w-4 h-4"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center py-24 bg-white rounded-2xl border border-dashed border-gray-300 text-center">
                        <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4 text-gray-300">
                            <i data-lucide="coffee" class="w-10 h-10"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-1">Belum ada riwayat baca</h3>
                        <p class="text-gray-500 text-sm mb-6 max-w-xs mx-auto">Jelajahi perpustakaan dan temukan buku menarik untuk mulai dibaca.</p>
                        <a href="<?= $dash_link ?>" class="px-8 py-3 <?= $btn_main ?> text-white rounded-xl font-bold transition-all transform hover:scale-105 shadow-lg flex items-center gap-2">
                            <i data-lucide="search" class="w-5 h-5"></i> Mulai Membaca
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();

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
    </script>
</body>

</html>