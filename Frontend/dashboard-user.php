<?php
require '../Backend/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';

$sql = "
    SELECT b.id, b.title, b.author, b.cover, b.type,
    GROUP_CONCAT(g.name SEPARATOR ', ') as genre_names 
    FROM books b 
    LEFT JOIN book_genres bg ON b.id = bg.book_id 
    LEFT JOIN genres g ON bg.genre_id = g.id 
    WHERE 1=1 
";

if ($search) {
    $sql .= " AND (title LIKE '%$search%' OR author LIKE '%$search%')";
}
if ($filter_type) {
    $sql .= " AND type = '$filter_type'";
}

$sql .= " GROUP BY b.id ORDER BY b.created_at DESC";
$books = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Katalog - E-Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 font-sans">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-xl fixed inset-y-0 left-0 z-10 hidden lg:block border-r">
            <div class="p-6 border-b flex flex-col items-center">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center text-2xl mb-3">👤</div>
                <h1 class="text-xl font-bold text-blue-900">E-Library</h1>
                <p class="text-xs text-gray-500 mt-1">Selamat datang, <?= htmlspecialchars($_SESSION['name']) ?></p>
            </div>
            <nav class="p-4 space-y-2">
                <a href="dashboard-user.php" class="flex items-center gap-3 px-4 py-3 bg-blue-50 text-blue-700 rounded-lg font-medium border border-blue-100">
                    <span>📚</span> Katalog Buku
                </a>
                <a href="logout.php" class="flex items-center gap-3 px-4 py-3 text-red-600 hover:bg-red-50 rounded-lg mt-auto">
                    <span>🚪</span> Keluar
                </a>
            </nav>
        </aside>

        <main class="flex-1 lg:ml-64 p-4 lg:p-8">
            <div class="bg-white p-6 rounded-xl shadow-sm border mb-8">
                <form method="GET" class="flex flex-col md:flex-row gap-4">
                    <div class="flex-1 relative">
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari judul atau penulis..." class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition">
                        <span class="absolute left-3 top-3.5 text-gray-400">🔍</span>
                    </div>
                    <select name="type" class="px-6 py-3 border border-gray-200 rounded-lg bg-white outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                        <option value="">Semua Tipe</option>
                        <option value="BOOK" <?= $filter_type == 'BOOK' ? 'selected' : '' ?>>Buku</option>
                        <option value="JOURNAL" <?= $filter_type == 'JOURNAL' ? 'selected' : '' ?>>Jurnal</option>
                        <option value="ARTICLE" <?= $filter_type == 'ARTICLE' ? 'selected' : '' ?>>Artikel</option>
                    </select>
                    <button type="submit" class="px-8 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-bold transition shadow-md shadow-blue-200">
                        Cari
                    </button>
                </form>
            </div>

            <?php if (mysqli_num_rows($books) > 0): ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <?php while ($book = mysqli_fetch_assoc($books)) { ?>
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-lg transition duration-300 flex flex-col h-full group">
                            <!-- Cover dari Database -->
                            <div class="h-64 bg-gray-100 relative overflow-hidden rounded-t-xl">
                                <?php if ($book['cover']): ?>
                                    <!-- MAGIC HAPPENS HERE: Base64 Encoding -->
                                    <img src="data:image/jpeg;base64,<?= base64_encode($book['cover']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                                <?php else: ?>
                                    <div class="w-full h-full flex flex-col items-center justify-center text-gray-400 bg-gray-50">
                                        <span class="text-4xl mb-2">📚</span>
                                        <span class="text-xs">No Cover</span>
                                    </div>
                                <?php endif; ?>

                                <div class="absolute top-3 right-3">
                                    <span class="px-3 py-1 text-xs font-bold rounded-full shadow-md bg-white text-blue-700 border border-blue-100">
                                        Database
                                    </span>
                                </div>
                            </div>

                            <div class="p-5 flex-1 flex flex-col">
                                <div class="mb-1 text-xs font-semibold text-blue-600 tracking-wide uppercase"><?= $book['type'] ?></div>
                                <h3 class="font-bold text-gray-900 text-lg mb-1 leading-snug line-clamp-2" title="<?= htmlspecialchars($book['title']) ?>">
                                    <?= htmlspecialchars($book['title']) ?>
                                </h3>
                                <p class="text-sm text-gray-500 mb-3"><?= htmlspecialchars($book['author']) ?></p>

                                <div class="flex flex-wrap gap-1 mb-4">
                                    <?php
                                    $g_names = explode(',', $book['genre_names']);
                                    foreach ($g_names as $gn):
                                        if (trim($gn) == '') continue;
                                    ?>
                                        <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-[10px] rounded-md border border-gray-200">
                                            <?= trim($gn) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>

                                <div class="mt-auto pt-4 border-t border-gray-100">
                                    <a href="read.php?id=<?= $book['id'] ?>" class="block w-full text-center py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium transition shadow-sm hover:shadow-md">
                                        Baca Sekarang
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            <?php else: ?>
                <div class="flex flex-col items-center justify-center py-20 bg-white rounded-xl border border-dashed border-gray-300">
                    <div class="text-4xl mb-4">🔍</div>
                    <h3 class="text-lg font-medium text-gray-900">Tidak ada buku ditemukan</h3>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>

</html>