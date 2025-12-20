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

if (isset($_POST['request_publisher'])) {
    mysqli_query($conn, "UPDATE users SET request_penerbit='1' WHERE id=$user_id");
    $message = "Permintaan dikirim! Tunggu konfirmasi Admin.";
}

$u_res = mysqli_query($conn, "SELECT * FROM users WHERE id=$user_id");
$current_user = mysqli_fetch_assoc($u_res);

// Pengaturan Tema Warna
$themeClass = ($role === 'PENERBIT') ? 'purple' : 'blue';
$bgGradient = ($role === 'PENERBIT') ? 'from-purple-800 to-indigo-900' : 'from-blue-800 to-indigo-900';

// 1. QUERY: BUKU PALING POPULER (Berdasarkan jumlah Like)
$sql_popular = "
    SELECT b.*, COUNT(bl.id) as total_likes 
    FROM books b 
    LEFT JOIN book_likes bl ON b.id = bl.book_id 
    WHERE b.status = 'APPROVED' 
    GROUP BY b.id 
    ORDER BY total_likes DESC 
    LIMIT 4";
$popular_books = mysqli_query($conn, $sql_popular);

// 2. QUERY: REKOMENDASI UNTUKMU (Berdasarkan Genre yang disukai User)
$sql_recommend = "
    SELECT DISTINCT b.* FROM books b
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
    LIMIT 4";
$recommended_books = mysqli_query($conn, $sql_recommend);

// Jika user belum like apapun, ambil buku terbaru secara acak sebagai fallback
if (mysqli_num_rows($recommended_books) == 0) {
    $sql_recommend = "SELECT * FROM books WHERE status='APPROVED' ORDER BY RAND() LIMIT 4";
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
</head>

<body class="bg-gray-50 font-sans">
    <div class="flex min-h-screen">

        <!-- SIDEBAR (Disesuaikan dengan peran) -->
        <aside class="w-64 bg-white shadow-xl fixed inset-y-0 left-0 z-40 border-r hidden lg:block">
            <div class="p-6 border-b flex flex-col items-center">
                <div class="w-16 h-16 bg-<?= $themeClass ?>-100 rounded-full flex items-center justify-center mb-3">
                    <i data-lucide="<?= ($role === 'PENERBIT') ? 'pen-tool' : 'user' ?>" class="w-8 h-8 text-<?= $themeClass ?>-600"></i>
                </div>
                <h1 class="text-xl font-bold text-<?= $themeClass ?>-900"><?= ($role === 'PENERBIT') ? 'Publisher' : 'E-Library' ?></h1>
                <p class="text-xs text-gray-500 mt-1">Halo, <?= htmlspecialchars($name) ?></p>
            </div>
            <nav class="p-4 space-y-2">
                <a href="home.php" class="flex items-center gap-3 px-4 py-3 bg-<?= $themeClass ?>-50 text-<?= $themeClass ?>-700 rounded-lg border border-<?= $themeClass ?>-100">
                    <i data-lucide="home" class="w-5 h-5"></i> Home
                </a>
                <a href="<?= ($role === 'PENERBIT') ? 'dashboard-publisher.php' : 'dashboard-user.php' ?>" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-<?= $themeClass ?>-50 hover:text-<?= $themeClass ?>-700 rounded-lg font-medium transition">
                    <i data-lucide="library" class="w-5 h-5"></i> Katalog
                </a>
                <!-- MENU KHUSUS PENERBIT (Updated) -->
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
                    <!-- TOMBOL REQUEST PENERBIT -->
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
        <main class="flex-1 lg:ml-64 p-4 lg:p-8">
            <!-- Hero Banner -->
            <div class="mb-10 bg-gradient-to-r <?= $bgGradient ?> text-white rounded-2xl p-8 shadow-lg relative overflow-hidden">
                <div class="relative z-10">
                    <h2 class="text-3xl font-bold mb-2">Selamat Datang Kembali, <?= htmlspecialchars($name) ?>!</h2>
                    <p class="text-<?= $themeClass ?>-100 max-w-lg">Temukan bacaan menarik hari ini berdasarkan preferensi yang kamu sukai.</p>
                </div>
                <i data-lucide="sparkles" class="absolute right-10 bottom-0 w-40 h-40 text-white opacity-10"></i>
            </div>

            <!-- SECTION: POPULER -->
            <section class="mb-12">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                        <i data-lucide="trending-up" class="text-orange-500"></i> Paling Populer
                    </h3>
                    <a href="<?= ($role === 'PENERBIT') ? 'dashboard-publisher.php' : 'dashboard-user.php' ?>" class="text-sm font-medium text-<?= $themeClass ?>-600 hover:underline">Lihat Semua</a>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <?php while ($book = mysqli_fetch_assoc($popular_books)): ?>
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition">
                            <div class="h-48 bg-gray-200">
                                <img src="../uploads/covers/<?= $book['cover'] ?>" class="w-full h-full object-cover">
                            </div>
                            <div class="p-4">
                                <h4 class="font-bold text-gray-900 truncate" title="<?= $book['title'] ?>"><?= $book['title'] ?></h4>
                                <p class="text-xs text-gray-500 mb-3"><?= $book['author'] ?></p>
                                <div class="flex items-center justify-between mt-4 pt-3 border-t">
                                    <span class="flex items-center gap-1 text-xs font-bold text-pink-600">
                                        <i data-lucide="heart" class="w-3 h-3 fill-current"></i> <?= $book['total_likes'] ?> Likes
                                    </span>
                                    <a href="detail.php?id=<?= $book['id'] ?>" class="text-xs font-bold text-<?= $themeClass ?>-600 hover:text-<?= $themeClass ?>-800">Detail</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

            </section>

            <!-- SECTION: REKOMENDASI -->
            <section class="mb-12">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                        <i data-lucide="sparkles" class="text-yellow-500"></i> Rekomendasi Untukmu
                    </h3>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <?php while ($book = mysqli_fetch_assoc($recommended_books)): ?>
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition group">
                            <div class="h-48 bg-gray-200 relative">
                                <img src="../uploads/covers/<?= $book['cover'] ?>" class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                                    <a href="detail.php?id=<?= $book['id'] ?>" class="bg-white text-gray-900 px-4 py-2 rounded-full text-xs font-bold">Baca Detail</a>
                                </div>
                            </div>
                            <div class="p-4">
                                <h4 class="font-bold text-gray-900 truncate"><?= $book['title'] ?></h4>
                                <p class="text-xs text-gray-500"><?= $book['author'] ?></p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </section>

        </main>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>