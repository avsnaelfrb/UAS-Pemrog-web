<?php
require_once dirname(__DIR__) . '/Backend/config.php';

// Proteksi Halaman
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$name = $_SESSION['name'];
$message = '';

// Handle request publisher jika user biasa
if (isset($_POST['request_publisher'])) {
    mysqli_query($conn, "UPDATE users SET request_penerbit='1' WHERE id=$user_id");
    $message = "Permintaan dikirim! Tunggu konfirmasi Admin.";
}

$u_res = mysqli_query($conn, "SELECT * FROM users WHERE id=$user_id");
$current_user = mysqli_fetch_assoc($u_res);

// Pengaturan Tema Warna
$themeClass = ($role === 'PENERBIT') ? 'purple' : 'blue';
$bgGradient = ($role === 'PENERBIT') ? 'from-purple-800 to-indigo-900' : 'from-blue-800 to-indigo-900';

// 1. QUERY: BUKU PALING POPULER (Updated with genres)
$sql_popular = "
    SELECT b.*, COUNT(DISTINCT bl.id) as total_likes, 
    GROUP_CONCAT(DISTINCT g.name SEPARATOR ', ') as genre_names
    FROM books b 
    LEFT JOIN book_likes bl ON b.id = bl.book_id 
    LEFT JOIN book_genres bg ON b.id = bg.book_id
    LEFT JOIN genres g ON bg.genre_id = g.id
    WHERE b.status = 'APPROVED' 
    GROUP BY b.id 
    ORDER BY total_likes DESC 
    LIMIT 8";
$popular_books = mysqli_query($conn, $sql_popular);

// 2. QUERY: REKOMENDASI UNTUKMU (Updated with genres)
$sql_recommend = "
    SELECT DISTINCT b.*,
    (SELECT GROUP_CONCAT(g2.name SEPARATOR ', ') 
    FROM book_genres bg2 
    JOIN genres g2 ON bg2.genre_id = g2.id 
    WHERE bg2.book_id = b.id) as genre_names,
    (SELECT COUNT(*) FROM book_likes bl2 WHERE bl2.book_id = b.id) as total_likes
    FROM books b
    JOIN book_genres bg ON b.id = bg.book_id
    WHERE bg.genre_id IN (
        SELECT bg2.genre_id 
        FROM book_likes bl
        JOIN book_genres bg2 ON bl.book_id = bg2.book_id
        WHERE bl.user_id = $user_id
    )
    AND b.id NOT IN (SELECT book_id FROM book_likes WHERE user_id = $user_id)
    AND b.status = 'APPROVED'
    ORDER BY b.created_at DESC
    LIMIT 8";
$recommended_books = mysqli_query($conn, $sql_recommend);

// Fallback jika belum ada rekomendasi
if (mysqli_num_rows($recommended_books) == 0) {
    $sql_recommend = "
        SELECT b.*, 
        (SELECT GROUP_CONCAT(g.name SEPARATOR ', ') FROM book_genres bg JOIN genres g ON bg.genre_id = g.id WHERE bg.book_id = b.id) as genre_names,
        (SELECT COUNT(*) FROM book_likes bl2 WHERE bl2.book_id = b.id) as total_likes
        FROM books b 
        WHERE status='APPROVED' 
        ORDER BY RAND() 
        LIMIT 8";
    $recommended_books = mysqli_query($conn, $sql_recommend);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - E-Library</title>
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

        .card-hover-effect:hover {
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }
    </style>
</head>

<body class="bg-gray-50">

    <!-- OVERLAY MOBILE -->
    <div id="mobile-overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden backdrop-blur-sm"></div>

    <?php if ($message): ?>
        <div onclick="this.remove()" class="fixed top-4 right-4 bg-yellow-600 text-white px-6 py-3 rounded-lg shadow-lg z-50 cursor-pointer animate-bounce flex items-center gap-2">
            <i data-lucide="info" class="w-5 h-5"></i> <?= $message ?>
        </div>
    <?php endif; ?>

    <div class="flex min-h-screen">

        <!-- SIDEBAR -->
        <aside id="sidebar" class="w-64 bg-white shadow-xl fixed inset-y-0 left-0 z-40 border-r transform -translate-x-full lg:translate-x-0 sidebar-transition h-full overflow-y-auto">
            <div class="p-6 border-b flex flex-col items-center relative">
                <button onclick="toggleSidebar()" class="absolute top-4 right-4 lg:hidden text-gray-500 hover:text-red-500">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
                <div class="w-16 h-16 bg-<?= $themeClass ?>-100 rounded-full flex items-center justify-center mb-3">
                    <i data-lucide="<?= ($role === 'PENERBIT') ? 'pen-tool' : 'user' ?>" class="w-8 h-8 text-<?= $themeClass ?>-600"></i>
                </div>
                <h1 class="text-xl font-bold text-<?= $themeClass ?>-900"><?= ($role === 'PENERBIT') ? 'Publisher' : 'E-Library' ?></h1>
                <p class="text-xs text-gray-500 mt-1 text-center">Halo, <?= htmlspecialchars($name) ?></p>
            </div>

            <nav class="p-4 space-y-2">
                <a href="home.php" class="flex items-center gap-3 px-4 py-3 bg-<?= $themeClass ?>-50 text-<?= $themeClass ?>-700 rounded-lg font-medium border border-<?= $themeClass ?>-100">
                    <i data-lucide="home" class="w-5 h-5"></i> Home
                </a>
                <a href="<?= ($role === 'PENERBIT') ? 'dashboard-publisher.php' : 'dashboard-user.php' ?>" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-<?= $themeClass ?>-50 hover:text-<?= $themeClass ?>-700 rounded-lg font-medium transition">
                    <i data-lucide="library" class="w-5 h-5"></i> Katalog
                </a>

                <?php if ($role == 'PENERBIT'): ?>
                    <a href="my_publications.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-<?= $themeClass ?>-50 hover:text-<?= $themeClass ?>-700 rounded-lg font-medium transition">
                        <i data-lucide="folder" class="w-5 h-5"></i> Terbitan Saya
                    </a>
                    <a href="upload.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-<?= $themeClass ?>-50 hover:text-<?= $themeClass ?>-700 rounded-lg font-medium transition">
                        <i data-lucide="upload" class="w-5 h-5"></i> Upload Karya
                    </a>
                <?php endif; ?>

                <a href="history.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-<?= $themeClass ?>-50 hover:text-<?= $themeClass ?>-700 rounded-lg font-medium transition">
                    <i data-lucide="history" class="w-5 h-5"></i> Riwayat
                </a>
                <a href="saved_books.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-<?= $themeClass ?>-50 hover:text-<?= $themeClass ?>-700 rounded-lg font-medium transition">
                    <i data-lucide="bookmark" class="w-5 h-5"></i> Koleksi
                </a>
                <a href="profile.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-<?= $themeClass ?>-50 hover:text-<?= $themeClass ?>-700 rounded-lg font-medium transition">
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

        <!-- MAIN CONTENT AREA -->
        <main class="flex-1 lg:ml-64 p-4 lg:p-8 transition-all duration-300">

            <!-- HEADER MOBILE -->
            <div class="lg:hidden flex items-center justify-between bg-white p-4 rounded-xl shadow-sm border mb-6 sticky top-0 z-20">
                <div class="flex items-center gap-3">
                    <button onclick="toggleSidebar()" class="text-gray-700 p-2 hover:bg-gray-100 rounded-lg focus:ring-2 focus:ring-<?= $themeClass ?>-100">
                        <i data-lucide="menu" class="w-6 h-6"></i>
                    </button>
                    <h1 class="font-bold text-<?= $themeClass ?>-900 text-lg">E-Library</h1>
                </div>
                <a href="profile.php" class="w-9 h-9 bg-<?= $themeClass ?>-100 rounded-full flex items-center justify-center text-sm border border-<?= $themeClass ?>-200">
                    <i data-lucide="user" class="w-5 h-5 text-<?= $themeClass ?>-600"></i>
                </a>
            </div>

            <!-- Hero Banner -->
            <div class="mb-8 lg:mb-10 bg-gradient-to-r <?= $bgGradient ?> text-white rounded-2xl p-6 lg:p-10 shadow-lg relative overflow-hidden">
                <div class="relative z-10">
                    <h2 class="text-2xl lg:text-3xl font-bold mb-2">Selamat Datang Kembali, <?= htmlspecialchars($name) ?>!</h2>
                    <p class="text-<?= $themeClass ?>-100 max-w-lg text-sm lg:text-base">Temukan bacaan menarik hari ini berdasarkan preferensi yang kamu sukai.</p>
                </div>
                <i data-lucide="sparkles" class="absolute -right-6 -bottom-6 w-32 h-32 lg:w-48 lg:h-48 text-white opacity-10 rotate-12"></i>
            </div>

            <!-- SECTION: POPULER -->
            <section class="mb-10 lg:mb-12">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg lg:text-xl font-bold text-gray-800 flex items-center gap-2">
                        <i data-lucide="trending-up" class="text-orange-500 w-5 h-5 lg:w-6 lg:h-6"></i> Paling Populer
                    </h3>
                    <a href="<?= ($role === 'PENERBIT') ? 'dashboard-publisher.php' : 'dashboard-user.php' ?>" class="text-xs lg:text-sm font-semibold text-<?= $themeClass ?>-600 hover:underline flex items-center gap-1">
                        Semua <i data-lucide="chevron-right" class="w-3 h-3 lg:w-4 lg:h-4"></i>
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <?php while ($book = mysqli_fetch_assoc($popular_books)): ?>
                        <div class="bg-white rounded-2xl border border-gray-100 transition-all duration-300 flex flex-col h-full group card-hover-effect shadow-md hover:shadow-lg hover:shadow-<?= $themeClass ?>-100 transform hover:-translate-y-2">
                            <div class="h-64 relative overflow-hidden rounded-t-2xl">
                                <img src="../uploads/covers/<?= $book['cover'] ?>" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">

                                <!-- Floating Badges -->
                                <div class="absolute top-4 left-4 flex flex-col gap-2">
                                    <span class="px-2.5 py-1 bg-black/60 backdrop-blur text-white text-[10px] font-bold rounded-lg shadow-sm uppercase tracking-wider">
                                        <?= $book['type'] ?>
                                    </span>
                                </div>

                                <div class="absolute bottom-4 left-4">
                                    <span class="flex items-center gap-1.5 px-3 py-1.5 bg-pink-600 text-white text-[11px] font-bold rounded-full shadow-lg">
                                        <i data-lucide="heart" class="w-3.5 h-3.5 fill-current"></i>
                                        <?= number_format($book['total_likes']) ?>
                                    </span>
                                </div>

                                <!-- Overlay on Hover -->
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                                    <a href="detail.php?id=<?= $book['id'] ?>">
                                        <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-<?= $themeClass ?>-600 shadow-xl transform scale-50 group-hover:scale-100 transition-transform duration-300">
                                            <i data-lucide="eye" class="w-6 h-6"></i>
                                        </div>
                                    </a>
                                </div>
                            </div>

                            <div class="p-5 flex flex-col flex-1">
                                <div class="mb-3">
                                    <h4 class="font-bold text-gray-900 text-base mb-1 line-clamp-2 leading-tight group-hover:text-<?= $themeClass ?>-600 transition-colors" title="<?= $book['title'] ?>">
                                        <?= $book['title'] ?>
                                    </h4>
                                    <p class="text-xs font-medium text-gray-500 flex items-center gap-1">
                                        <i data-lucide="user" class="w-3 h-3"></i> <?= $book['author'] ?>
                                    </p>
                                </div>

                                <div class="flex flex-wrap gap-1.5 mb-5 mt-auto">
                                    <?php if ($book['genre_names']): ?>
                                        <?php
                                        $genres = explode(',', $book['genre_names']);
                                        $displayGenres = array_slice($genres, 0, 2);
                                        foreach ($displayGenres as $gn): ?>
                                            <span class="px-2 py-1 bg-gray-50 text-gray-600 text-[10px] font-bold rounded-md border border-gray-200 group-hover:bg-<?= $themeClass ?>-50 group-hover:text-<?= $themeClass ?>-600 group-hover:border-<?= $themeClass ?>-100 transition-all">
                                                <?= trim($gn) ?>
                                            </span>
                                        <?php endforeach; ?>
                                        <?php if (count($genres) > 2): ?>
                                            <span class="text-[10px] text-gray-400 font-bold ml-1">+<?= count($genres) - 2 ?></span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>

                                <a href="detail.php?id=<?= $book['id'] ?>" class="flex items-center justify-center gap-2 w-full py-2.5 bg-<?= $themeClass ?>-600 hover:bg-<?= $themeClass ?>-700 text-white rounded-xl font-bold text-xs transition-all shadow-md shadow-<?= $themeClass ?>-100 group-hover:shadow-lg">
                                    Lihat Detail <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </section>

            <!-- SECTION: REKOMENDASI -->
            <section class="mb-10 lg:mb-12">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg lg:text-xl font-bold text-gray-800 flex items-center gap-2">
                        <i data-lucide="sparkles" class="text-yellow-500 w-5 h-5 lg:w-6 lg:h-6"></i> Rekomendasi Untukmu
                    </h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 pb-10">
                    <?php while ($book = mysqli_fetch_assoc($recommended_books)): ?>
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-md hover:shadow-lg hover:shadow-<?= $themeClass ?>-100 transition-all duration-300 flex flex-col h-full group card-hover-effect transform hover:-translate-y-2">
                            <div class="h-64 relative overflow-hidden rounded-t-2xl">
                                <img src="../uploads/covers/<?= $book['cover'] ?>" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">

                                <div class="absolute top-4 left-4 flex flex-col gap-2">
                                    <span class="px-2.5 py-1 bg-black/60 backdrop-blur text-white text-[10px] font-bold rounded-lg shadow-sm uppercase tracking-wider">
                                        For You
                                    </span>
                                </div>

                                <div class="absolute bottom-4 left-4">
                                    <span class="flex items-center gap-1.5 px-3 py-1.5 bg-pink-600 text-white text-[11px] font-bold rounded-full shadow-lg">
                                        <i data-lucide="heart" class="w-3.5 h-3.5 fill-current"></i>
                                        <?= number_format($book['total_likes']) ?>
                                    </span>
                                </div>

                                <!-- Quick Action Overlay -->
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                                    <a href="detail.php?id=<?= $book['id'] ?>">
                                        <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-<?= $themeClass ?>-600 shadow-xl transform scale-50 group-hover:scale-100 transition-transform duration-300">
                                            <i data-lucide="eye" class="w-6 h-6"></i>
                                        </div>
                                    </a>
                                </div>
                            </div>

                            <div class="p-5 flex flex-col flex-1">
                                <div class="mb-3">
                                    <h4 class="font-extrabold text-gray-900 text-base mb-1 line-clamp-2 leading-tight group-hover:text-<?= $themeClass ?>-600 transition-colors" title="<?= $book['title'] ?>">
                                        <?= $book['title'] ?>
                                    </h4>
                                    <p class="text-xs font-medium text-gray-500 flex items-center gap-1">
                                        <i data-lucide="user" class="w-3 h-3"></i> <?= $book['author'] ?>
                                    </p>
                                </div>

                                <div class="flex flex-wrap gap-1.5 mb-5 mt-auto">
                                    <?php if ($book['genre_names']): ?>
                                        <?php
                                        $genres = explode(',', $book['genre_names']);
                                        foreach (array_slice($genres, 0, 2) as $gn): ?>
                                            <span class="px-2 py-1 bg-gray-50 text-gray-600 text-[10px] font-bold rounded-md border border-gray-200 group-hover:bg-<?= $themeClass ?>-50 group-hover:text-<?= $themeClass ?>-600 group-hover:border-<?= $themeClass ?>-100 transition-all">
                                                <?= trim($gn) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

                                <a href="detail.php?id=<?= $book['id'] ?>" class="flex items-center justify-center gap-2 w-full py-2.5 bg-<?= $themeClass ?>-600 hover:bg-<?= $themeClass ?>-700 text-white rounded-xl font-bold text-xs transition-all shadow-md shadow-<?= $themeClass ?>-100 group-hover:shadow-lg">
                                    Lihat Detail <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </section>

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