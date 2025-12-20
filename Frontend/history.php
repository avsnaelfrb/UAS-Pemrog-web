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
$theme = ($role == 'PENERBIT') ? 'purple' : 'blue';
$bg_soft = "bg-$theme-50";
$text_main = "text-$theme-700";
$border_main = "border-$theme-100";
$hover_soft = "hover:bg-$theme-50";
$btn_main = "bg-$theme-600 hover:bg-$theme-700";

if (isset($_POST['request_publisher'])) {
    mysqli_query($conn, "UPDATE users SET request_penerbit='1' WHERE id=$user_id");
    $message = "Permintaan dikirim! Tunggu konfirmasi Admin.";
}

$u_res = mysqli_query($conn, "SELECT * FROM users WHERE id=$user_id");
$current_user = mysqli_fetch_assoc($u_res);
$genres_list = mysqli_query($conn, "SELECT * FROM genres ORDER BY name ASC");

// PERBAIKAN QUERY: Menggunakan MAX(h.read_at) agar mengambil waktu terbaru jika ada data ganda
$sql = "
    SELECT b.id, b.title, b.author, b.cover, b.type, MAX(h.read_at) as read_at,
    GROUP_CONCAT(DISTINCT g.name SEPARATOR ', ') as genre_names 
    FROM history h
    JOIN books b ON h.book_id = b.id
    LEFT JOIN book_genres bg ON b.id = bg.book_id 
    LEFT JOIN genres g ON bg.genre_id = g.id 
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
    </style>
</head>

<body class="bg-gray-50 font-sans">

    <!-- OVERLAY MOBILE -->
    <div id="mobile-overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden bg-blur"></div>

    <?php if ($message): ?>
        <div onclick="this.remove()" class="fixed top-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg z-50 cursor-pointer animate-bounce flex items-center gap-2">
            <i data-lucide="check-circle" class="w-5 h-5"></i> <?= $message ?>
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
                <?php if ($role == 'ADMIN'): ?>
                    <a href="dashboard-admin.php" class="flex items-center gap-3 px-4 py-3 bg-indigo-600 text-white rounded-lg font-bold shadow-md hover:bg-indigo-700 transition mb-6 ring-2 ring-indigo-200">
                        <i data-lucide="zap" class="w-5 h-5"></i> Admin Panel
                    </a>
                <?php endif; ?>

                <?php $dash_link = ($role == 'PENERBIT') ? 'dashboard-publisher.php' : 'dashboard-user.php'; ?>
                <a href="<?= $dash_link ?>" class="flex items-center gap-3 px-4 py-3 text-gray-600 <?= $hover_soft ?> rounded-lg font-medium transition">
                    <i data-lucide="library" class="w-5 h-5"></i> Katalog
                </a>

                <?php if ($role == 'PENERBIT'): ?>
                    <a href="my_publications.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 <?= $hover_soft ?> rounded-lg font-medium transition">
                        <i data-lucide="folder" class="w-5 h-5"></i> Terbitan Saya
                    </a>

                    <a href="upload.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 <?= $hover_soft ?> rounded-lg font-medium transition">
                        <i data-lucide="upload" class="w-5 h-5"></i> Upload Karya
                    </a>
                <?php endif; ?>

                <a href="history.php" class="flex items-center gap-3 px-4 py-3 <?= $bg_soft ?> <?= $text_main ?> rounded-lg font-medium border <?= $border_main ?>">
                    <i data-lucide="history" class="w-5 h-5"></i> Riwayat
                </a>

                <a href="saved_books.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 <?= $hover_soft ?> rounded-lg font-medium transition">
                    <i data-lucide="bookmark" class="w-5 h-5"></i> Koleksi
                </a>

                <a href="profile.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 <?= $hover_soft ?> rounded-lg font-medium transition">
                    <i data-lucide="settings" class="w-5 h-5"></i> Profile
                </a>

                <?php if ($role == 'USER'): ?>
                    <div class="pt-4 mt-4 border-t border-gray-200">
                        <?php if ($current_user['request_penerbit'] == '0'): ?>
                            <form method="POST">
                                <button type="submit" name="request_publisher" onclick="return confirm('Ingin mengajukan diri sebagai Penerbit?')" class="w-full text-left flex items-center gap-3 px-4 py-3 bg-purple-50 text-purple-700 hover:bg-purple-50 hover:text-purple-700 rounded-lg font-medium transition duration-200">
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
                    <h1 class="font-bold <?= $text_main ?> text-lg">Riwayat Baca</h1>
                </div>
                <a href="profile.php" class="w-8 h-8 <?= ($role == 'PENERBIT') ? 'bg-purple-100' : 'bg-blue-100' ?> rounded-full flex items-center justify-center text-sm border">
                    <i data-lucide="user" class="w-4 h-4 text-gray-700"></i>
                </a>
            </div>

            <div class="max-w-6xl mx-auto">
                <div class="bg-white p-6 rounded-xl shadow-sm border mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800 mb-2 flex items-center gap-2">
                            <i data-lucide="history" class="w-6 h-6 text-<?= $theme ?>-500"></i> Aktivitas Terakhir
                        </h2>
                        <p class="text-gray-500 text-sm">Daftar buku yang baru saja Anda baca.</p>
                    </div>
                    <div class="bg-<?= $theme ?>-50 px-4 py-2 rounded-lg border text-sm font-bold text-<?= $theme ?>-600">
                        Total: <?= mysqli_num_rows($books) ?> Buku
                    </div>
                </div>

                <?php if (mysqli_num_rows($books) > 0): ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        <?php while ($book = mysqli_fetch_assoc($books)) { ?>
                            <div class="bg-white rounded-xl shadow-md border border-gray-100 hover:shadow-lg hover:shadow-blue-100 transition duration-300 flex flex-col h-full group relative">

                                <!-- Cover -->
                                <div class="h-64 bg-gray-100 relative overflow-hidden rounded-t-xl">
                                    <?php
                                    $coverPath = '../uploads/covers/' . $book['cover'];
                                    if (!empty($book['cover']) && file_exists($coverPath)):
                                    ?>
                                        <img src="<?= $coverPath ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                                    <?php else: ?>
                                        <div class="w-full h-full flex flex-col items-center justify-center text-gray-400 bg-gray-50">
                                            <i data-lucide="image-off" class="w-10 h-10 mb-2"></i>
                                            <span class="text-xs">No Cover</span>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Label Waktu Baca (Floating Style) -->
                                    <div class="absolute top-3 left-3 right-3">
                                        <div class="bg-black/60 backdrop-blur-sm text-white px-3 py-2 rounded-lg flex flex-col shadow-lg">
                                            <div class="flex items-center gap-1.5 text-[10px] font-bold text-orange-400 uppercase">
                                                <i data-lucide="clock" class="w-3 h-3"></i>
                                                Terakhir Dibaca
                                            </div>
                                            <div class="text-xs font-medium">
                                                <?= time_ago($book['read_at']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-5 flex-1 flex flex-col">
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="text-[10px] font-bold <?= ($role == 'PENERBIT') ? 'text-purple-600 bg-purple-50' : 'text-blue-600 bg-blue-50' ?> tracking-wide uppercase px-2 py-0.5 rounded">
                                            <?= $book['type'] ?>
                                        </div>
                                        <span class="text-[9px] text-gray-400 font-bold"><?= date('d/m/y', strtotime($book['read_at'])) ?></span>
                                    </div>

                                    <h3 class="font-bold text-gray-900 text-base mb-1 leading-snug line-clamp-2" title="<?= htmlspecialchars($book['title']) ?>">
                                        <?= htmlspecialchars($book['title']) ?>
                                    </h3>
                                    <p class="text-xs text-gray-500 mb-4"><?= htmlspecialchars($book['author']) ?></p>

                                    <div class="mt-auto pt-4 border-t border-gray-100 flex flex-col gap-2">
                                        <a href="read.php?id=<?= $book['id'] ?>" class="block w-full text-center py-2.5 <?= $btn_main ?> text-white rounded-lg font-bold text-xs transition flex items-center justify-center gap-2 shadow-sm">
                                            <i data-lucide="book-open" class="w-4 h-4"></i> Lanjutkan Membaca
                                        </a>
                                        <a href="detail.php?id=<?= $book['id'] ?>" class="block w-full text-center py-2 text-gray-400 hover:text-gray-700 text-[10px] font-bold transition">
                                            Lihat Detail
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center py-20 bg-white rounded-xl border border-dashed border-gray-300 text-center">
                        <i data-lucide="coffee" class="w-12 h-12 mb-4 text-gray-400"></i>
                        <h3 class="text-lg font-medium text-gray-900">Belum ada riwayat baca</h3>
                        <p class="text-gray-500 text-sm mb-4">Ayo mulai membaca buku pertamamu!</p>
                        <a href="<?= $dash_link ?>" class="px-6 py-2 <?= $btn_main ?> text-white rounded-lg font-bold transition flex items-center gap-2">
                            <i data-lucide="search" class="w-4 h-4"></i> Cari Buku
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Initialize Lucide Icons -->
    <script>
        lucide.createIcons();

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