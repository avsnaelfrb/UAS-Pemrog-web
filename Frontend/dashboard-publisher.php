<?php
require_once dirname(__DIR__) . '/Backend/config.php';
checkRole('PENERBIT');

// CEK KHUSUS: Hanya Penerbit
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'PENERBIT') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$genres_list = mysqli_query($conn, "SELECT * FROM genres ORDER BY name ASC");

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';
$filter_genres = isset($_GET['genres']) ? $_GET['genres'] : [];

// PERBAIKAN QUERY: Menambahkan penghitungan Like yang akurat (DISTINCT) dan genre
$sql = "SELECT b.id, b.title, b.author, b.cover, b.type, 
        GROUP_CONCAT(DISTINCT g.name SEPARATOR ', ') as genre_names,
        COUNT(DISTINCT bl.id) as total_likes
        FROM books b 
        LEFT JOIN book_genres bg ON b.id = bg.book_id 
        LEFT JOIN genres g ON bg.genre_id = g.id 
        LEFT JOIN book_likes bl ON b.id = bl.book_id
        WHERE b.status='APPROVED' ";

if ($search) {
    $sql .= " AND (title LIKE '%$search%' OR author LIKE '%$search%')";
}
if ($filter_type) {
    $sql .= " AND type = '$filter_type'";
}

if (!empty($filter_genres)) {
    $genre_ids = array_map('intval', $filter_genres);
    $ids_string = implode(',', $genre_ids);
    $sql .= " AND b.id IN (SELECT book_id FROM book_genres WHERE genre_id IN ($ids_string))";
}

$sql .= " GROUP BY b.id ORDER BY b.created_at DESC";
$books = mysqli_query($conn, $sql);

// Updated Type Map with Lucide Icons
$type_map = [
    '' => '<div class="flex items-center gap-2"><i data-lucide="files" class="w-4 h-4"></i> Semua Tipe</div>',
    'BOOK' => '<div class="flex items-center gap-2"><i data-lucide="book" class="w-4 h-4"></i> Buku</div>',
    'JOURNAL' => '<div class="flex items-center gap-2"><i data-lucide="book-open" class="w-4 h-4"></i> Jurnal</div>',
    'ARTICLE' => '<div class="flex items-center gap-2"><i data-lucide="newspaper" class="w-4 h-4"></i> Artikel</div>'
];
$active_type_label = isset($type_map[$filter_type]) ? $type_map[$filter_type] : $type_map[''];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Penerbit - E-Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .genre-scroll::-webkit-scrollbar {
            width: 6px;
        }

        .genre-scroll::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .genre-scroll::-webkit-scrollbar-thumb {
            background: #d8b4fe;
            border-radius: 4px;
        }

        .genre-scroll::-webkit-scrollbar-thumb:hover {
            background: #c084fc;
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

    <div class="flex min-h-screen">
        <!-- Sidebar Penerbit -->
        <aside id="sidebar" class="w-64 bg-white shadow-xl fixed inset-y-0 left-0 z-40 border-r transform -translate-x-full lg:translate-x-0 transition-transform duration-300">
            <div class="p-6 border-b flex flex-col items-center relative">
                <button onclick="toggleSidebar()" class="absolute top-4 right-4 lg:hidden text-gray-500 hover:text-red-500">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
                <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center text-2xl mb-3">
                    <i data-lucide="pen-tool" class="w-8 h-8 text-purple-600"></i>
                </div>
                <h1 class="text-xl font-bold text-purple-900">Publisher</h1>
                <p class="text-xs text-gray-500 mt-1">Halo, <?= htmlspecialchars($_SESSION['name']) ?></p>
            </div>

            <nav class="p-4 space-y-2">
                <a href="home.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-purple-50 hover:text-purple-700 rounded-lg font-medium transition">
                    <i data-lucide="home" class="w-5 h-5"></i> Home
                </a>
                <a href="dashboard-publisher.php" class="flex items-center gap-3 px-4 py-3 bg-purple-50 text-purple-700 rounded-lg font-medium border border-purple-100 shadow-sm">
                    <i data-lucide="library" class="w-5 h-5"></i> Katalog
                </a>

                <a href="my_publications.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-purple-50 hover:text-purple-700 rounded-lg font-medium transition duration-200">
                    <i data-lucide="folder" class="w-5 h-5"></i> Terbitan Saya
                </a>

                <a href="upload.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-purple-50 hover:text-purple-700 rounded-lg font-medium transition duration-200">
                    <i data-lucide="upload" class="w-5 h-5"></i> Upload Karya
                </a>

                <a href="history.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-purple-50 hover:text-purple-700 rounded-lg font-medium transition duration-200">
                    <i data-lucide="history" class="w-5 h-5"></i> Riwayat
                </a>

                <a href="saved_books.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-purple-50 hover:text-purple-700 rounded-lg font-medium transition duration-200">
                    <i data-lucide="bookmark" class="w-5 h-5"></i> Koleksi
                </a>

                <a href="profile.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-purple-50 hover:text-purple-700 rounded-lg font-medium transition duration-200">
                    <i data-lucide="settings" class="w-5 h-5"></i> Profile
                </a>
                <a href="logout.php" class="flex items-center gap-3 px-4 py-3 text-red-600 hover:bg-red-50 rounded-lg mt-auto pt-4 border-t">
                    <i data-lucide="log-out" class="w-5 h-5"></i> Keluar
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 lg:ml-64 p-4 lg:p-8 transition-all duration-300">
            <!-- Header Mobile -->
            <div class="lg:hidden flex items-center justify-between bg-white p-4 rounded-xl shadow-sm border mb-6 sticky top-0 z-20">
                <button onclick="toggleSidebar()" class="text-gray-700 p-2 hover:bg-gray-100 rounded-lg">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
                <h1 class="font-bold text-purple-900 text-lg">Dashboard</h1>
                <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center text-sm border border-purple-200">
                    <i data-lucide="pen-tool" class="w-4 h-4 text-purple-600"></i>
                </div>
            </div>

            <!-- Banner Statistik Singkat Penerbit -->
            <div class="mb-8 bg-gradient-to-r from-purple-800 to-indigo-900 text-white rounded-2xl p-6 lg:p-10 shadow-lg relative overflow-hidden flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="relative z-10">
                    <h2 class="text-2xl lg:text-3xl font-bold mb-2">Selamat Datang, Kontributor!</h2>
                    <p class="text-purple-200 text-sm lg:text-base">Bagikan pengetahuanmu melalui karya tulis yang berkualitas.</p>
                </div>
                <a href="upload.php" class="relative z-10 bg-white text-purple-900 px-8 py-3 rounded-xl font-bold hover:bg-purple-50 hover:scale-105 transition transform shadow-xl w-full md:w-auto text-center flex items-center justify-center gap-2">
                    <i data-lucide="plus" class="w-5 h-5"></i> Upload Baru
                </a>
                <i data-lucide="sparkles" class="absolute -right-6 -bottom-6 w-32 h-32 text-white opacity-10 rotate-12"></i>
            </div>

            <!-- SEARCH & FILTER BAR -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 mb-8 flex flex-col gap-4 relative z-10">
                <form method="GET" class="flex flex-col md:flex-row gap-4 w-full items-stretch">
                    <div class="flex-1 relative">
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari judul atau penulis..." class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-purple-200 focus:border-purple-400 outline-none transition h-full text-sm">
                        <span class="absolute left-3 top-3.5 text-gray-400">
                            <i data-lucide="search" class="w-5 h-5"></i>
                        </span>
                    </div>

                    <div class="relative min-w-[220px]" id="genreDropdownContainer">
                        <button type="button" onclick="toggleGenreDropdown()" class="w-full h-full px-4 py-3 border border-gray-200 rounded-lg bg-white outline-none focus:ring-2 focus:ring-purple-200 text-left flex justify-between items-center text-gray-700 text-sm transition hover:bg-purple-50">
                            <span id="genreLabel" class="truncate mr-2 flex items-center gap-2">
                                <?php if (empty($filter_genres)): ?>
                                    <div class="flex items-center gap-2"><i data-lucide="folder" class="w-4 h-4 text-purple-500"></i> Pilih Genre</div>
                                <?php else: ?>
                                    <?= count($filter_genres) . ' Genre Dipilih' ?>
                                <?php endif; ?>
                            </span>
                            <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400 transition-transform duration-200" id="genreArrow"></i>
                        </button>

                        <div id="genrePanel" class="hidden absolute top-full left-0 mt-2 w-full bg-white border border-gray-200 rounded-lg shadow-xl z-50 p-2 max-h-60 overflow-y-auto genre-scroll">
                            <div class="text-xs text-gray-400 mb-2 px-2 uppercase font-bold tracking-wider">Kategori</div>
                            <?php
                            mysqli_data_seek($genres_list, 0);
                            while ($g = mysqli_fetch_assoc($genres_list)):
                                $isChecked = in_array($g['id'], $filter_genres) ? 'checked' : '';
                            ?>
                                <label class="flex items-center space-x-3 p-2 hover:bg-purple-50 rounded cursor-pointer transition">
                                    <input type="checkbox" name="genres[]" value="<?= $g['id'] ?>" <?= $isChecked ?> class="w-4 h-4 text-purple-600 rounded focus:ring-purple-500 border-gray-300">
                                    <span class="text-sm text-gray-700 select-none"><?= htmlspecialchars($g['name']) ?></span>
                                </label>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <div class="relative min-w-[180px]" id="typeDropdownContainer">
                        <input type="hidden" name="type" id="typeInput" value="<?= htmlspecialchars($filter_type) ?>">
                        <button type="button" onclick="toggleTypeDropdown()" class="w-full h-full px-4 py-3 border border-gray-200 rounded-lg bg-white outline-none focus:ring-2 focus:ring-purple-200 text-left flex justify-between items-center text-gray-700 text-sm transition hover:bg-purple-50">
                            <span id="typeLabel" class="truncate mr-2 flex items-center gap-2 text-sm"><?= $active_type_label ?></span>
                            <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400 transition-transform duration-200" id="typeArrow"></i>
                        </button>
                        <div id="typePanel" class="hidden absolute top-full left-0 mt-2 w-full bg-white border border-gray-200 rounded-lg shadow-xl z-50 py-1 overflow-hidden">
                            <?php foreach ($type_map as $val => $label): $isSelected = ($filter_type == $val); ?>
                                <div onclick="selectType('<?= $val ?>', '<?= htmlspecialchars($label) ?>')"
                                    class="px-4 py-2.5 hover:bg-purple-50 cursor-pointer text-sm flex items-center justify-between group transition <?= $isSelected ? 'bg-purple-50 text-purple-600 font-medium' : 'text-gray-700' ?>">
                                    <span><?= $label ?></span>
                                    <?php if ($isSelected): ?>
                                        <i data-lucide="check" class="w-4 h-4 text-purple-600"></i>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <button type="submit" class="px-8 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-bold transition shadow-md shadow-purple-200 flex-shrink-0">
                        Cari
                    </button>
                </form>

                <?php if (!empty($filter_genres) || $filter_type || $search): ?>
                    <div class="flex flex-wrap gap-2 text-sm text-gray-600 items-center mt-2 border-t pt-4 border-gray-50">
                        <span class="text-xs font-semibold mr-1">Filter Aktif:</span>
                        <?php if ($search): ?>
                            <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full border border-yellow-200 text-xs flex items-center gap-1">
                                <i data-lucide="search" class="w-3 h-3"></i> "<?= htmlspecialchars($search) ?>"
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($filter_genres)): ?>
                            <?php
                            mysqli_data_seek($genres_list, 0);
                            while ($gx = mysqli_fetch_assoc($genres_list)) {
                                if (in_array($gx['id'], $filter_genres)) echo '<span class="bg-purple-100 text-purple-800 px-3 py-1 rounded-full border border-purple-200 text-xs flex items-center gap-1"><i data-lucide="folder" class="w-3 h-3"></i> ' . htmlspecialchars($gx['name']) . '</span>';
                            }
                            ?>
                        <?php endif; ?>
                        <?php if ($filter_type && isset($type_map[$filter_type])): ?>
                            <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full border border-green-200 text-xs flex items-center gap-1">
                                <?= $type_map[$filter_type] ?>
                            </span>
                        <?php endif; ?>
                        <a href="dashboard-publisher.php" class="text-gray-400 hover:text-red-500 hover:underline ml-auto text-xs font-medium transition">Reset Filter</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- GRID BUKU (MODERN STYLE SYNCED WITH HOME.PHP) -->
            <?php if (mysqli_num_rows($books) > 0): ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 pb-20">
                    <?php while ($book = mysqli_fetch_assoc($books)) { ?>
                        <div class="bg-white rounded-2xl border border-gray-100 transition-all duration-300 flex flex-col h-full group card-hover-effect shadow-md hover:shadow-lg hover:shadow-purple-100 transform hover:-translate-y-2">
                            <div class="h-64 relative overflow-hidden rounded-t-2xl bg-gray-50">
                                <?php
                                $coverPath = '../uploads/covers/' . $book['cover'];
                                if (!empty($book['cover']) && file_exists($coverPath)): ?>
                                    <img src="<?= $coverPath ?>" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                                <?php else: ?>
                                    <div class="w-full h-full flex flex-col items-center justify-center text-gray-400">
                                        <i data-lucide="image-off" class="w-12 h-12 mb-2"></i>
                                        <span class="text-xs font-medium uppercase tracking-widest">No Cover</span>
                                    </div>
                                <?php endif; ?>

                                <!-- Floating Badge Tipe -->
                                <div class="absolute top-4 left-4">
                                    <div class="text-[10px] font-extrabold text-white tracking-wide uppercase bg-black/50 backdrop-blur-md px-2.5 py-1 rounded-lg border border-white/20">
                                        <?= $book['type'] ?>
                                    </div>
                                </div>

                                <!-- Floating Badge Like (NEW FOR PUBLISHER) -->
                                <div class="absolute bottom-4 left-4">
                                    <span class="flex items-center gap-1.5 px-3 py-1.5 bg-pink-600 text-white text-[11px] font-extrabold rounded-full shadow-lg">
                                        <i data-lucide="heart" class="w-3.5 h-3.5 fill-current"></i>
                                        <?= number_format($book['total_likes']) ?>
                                    </span>
                                </div>

                                <!-- Quick View Overlay on Hover -->
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                                    <a href="detail.php?id=<?= $book['id'] ?>" class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-purple-600 shadow-2xl transform scale-50 group-hover:scale-100 transition-transform duration-300">
                                        <i data-lucide="eye" class="w-6 h-6"></i>
                                    </a>
                                </div>
                            </div>

                            <div class="p-5 flex flex-col flex-1">
                                <div class="mb-3">
                                    <h4 class="font-extrabold text-gray-900 text-base mb-1 leading-tight line-clamp-2 group-hover:text-purple-700 transition-colors" title="<?= htmlspecialchars($book['title']) ?>">
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
                                            echo '<span class="px-2.5 py-1 bg-gray-50 text-gray-600 text-[10px] font-extrabold rounded-md border border-gray-200 group-hover:bg-purple-50 group-hover:text-purple-600 group-hover:border-purple-100 transition-all">' . trim($gn) . '</span>';
                                        }
                                        if (count($genres) > 2) {
                                            echo '<span class="text-[10px] text-gray-400 font-bold ml-1">+' . (count($genres) - 2) . '</span>';
                                        }
                                    }
                                    ?>
                                </div>

                                <div class="mt-auto pt-4 border-t border-gray-100">
                                    <a href="detail.php?id=<?= $book['id'] ?>" class="flex items-center justify-center gap-2 w-full py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-bold text-xs transition-all shadow-md shadow-purple-100 group-hover:shadow-lg">
                                        Baca Detail <i data-lucide="arrow-right" class="w-4 h-4"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            <?php else: ?>
                <div class="flex flex-col items-center justify-center py-24 bg-white rounded-2xl border border-dashed border-gray-300">
                    <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                        <i data-lucide="folder-search" class="w-10 h-10 text-gray-300"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-1">Tidak ada hasil ditemukan</h3>
                    <p class="text-gray-500 text-sm">Coba sesuaikan kata kunci atau filter pencarian Anda.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Overlay Mobile -->
    <div id="mobile-overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden backdrop-blur-sm"></div>

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

        function toggleGenreDropdown() {
            const panel = document.getElementById('genrePanel');
            const arrow = document.getElementById('genreArrow');
            closeTypeDropdown();
            if (panel.classList.contains('hidden')) {
                panel.classList.remove('hidden');
                arrow.style.transform = 'rotate(180deg)';
            } else {
                panel.classList.add('hidden');
                arrow.style.transform = 'rotate(0deg)';
            }
        }

        function closeGenreDropdown() {
            document.getElementById('genrePanel').classList.add('hidden');
            const arrow = document.getElementById('genreArrow');
            if (arrow) arrow.style.transform = 'rotate(0deg)';
        }

        function toggleTypeDropdown() {
            const panel = document.getElementById('typePanel');
            const arrow = document.getElementById('typeArrow');
            closeGenreDropdown();
            if (panel.classList.contains('hidden')) {
                panel.classList.remove('hidden');
                arrow.style.transform = 'rotate(180deg)';
            } else {
                panel.classList.add('hidden');
                arrow.style.transform = 'rotate(0deg)';
            }
        }

        function closeTypeDropdown() {
            document.getElementById('typePanel').classList.add('hidden');
            const arrow = document.getElementById('typeArrow');
            if (arrow) arrow.style.transform = 'rotate(0deg)';
        }

        function selectType(value, label) {
            document.getElementById('typeInput').value = value;
            document.getElementById('typeLabel').innerHTML = label;
            closeTypeDropdown();
        }

        document.addEventListener('click', function(event) {
            const genreContainer = document.getElementById('genreDropdownContainer');
            const typeContainer = document.getElementById('typeDropdownContainer');
            if (genreContainer && !genreContainer.contains(event.target)) closeGenreDropdown();
            if (typeContainer && !typeContainer.contains(event.target)) closeTypeDropdown();
        });

        lucide.createIcons();
    </script>
</body>

</html>