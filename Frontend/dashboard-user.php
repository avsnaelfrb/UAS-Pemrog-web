<?php
require '../Backend/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Redirect jika login sebagai PENERBIT tapi masuk ke halaman USER
if ($_SESSION['role'] == 'PENERBIT') {
    header("Location: dashboard-publisher.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

// --- LOGIKA REQUEST PENERBIT ---
if (isset($_POST['request_publisher'])) {
    mysqli_query($conn, "UPDATE users SET request_penerbit='1' WHERE id=$user_id");
    $message = "Permintaan dikirim! Tunggu konfirmasi Admin.";
}

// Refresh User Data
$u_res = mysqli_query($conn, "SELECT * FROM users WHERE id=$user_id");
$current_user = mysqli_fetch_assoc($u_res);

// --- LOGIKA FILTER & PENCARIAN ---
// 1. Ambil Data Genre untuk Dropdown
$genres_list = mysqli_query($conn, "SELECT * FROM genres ORDER BY name ASC");

// 2. Tangkap Input Filter
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';
$filter_genres = isset($_GET['genres']) ? $_GET['genres'] : []; // Array genres

// 3. Susun Query Utama
$sql = "
    SELECT b.id, b.title, b.author, b.cover, b.type, b.year,
    GROUP_CONCAT(g.name SEPARATOR ', ') as genre_names 
    FROM books b 
    LEFT JOIN book_genres bg ON b.id = bg.book_id 
    LEFT JOIN genres g ON bg.genre_id = g.id 
    WHERE b.status = 'APPROVED' 
";

if ($search) {
    $sql .= " AND (title LIKE '%$search%' OR author LIKE '%$search%')";
}
if ($filter_type) {
    $sql .= " AND type = '$filter_type'";
}

// Filter Genre (Multi-select)
if (!empty($filter_genres)) {
    $genre_ids = array_map('intval', $filter_genres);
    $ids_string = implode(',', $genre_ids);
    $sql .= " AND b.id IN (SELECT book_id FROM book_genres WHERE genre_id IN ($ids_string))";
}

$sql .= " GROUP BY b.id ORDER BY b.created_at DESC";
$books = mysqli_query($conn, $sql);

// Helper Label Tipe
$type_map = [
    '' => '📄 Semua Tipe',
    'BOOK' => '📘 Buku',
    'JOURNAL' => '📓 Jurnal',
    'ARTICLE' => '📰 Artikel'
];
$active_type_label = isset($type_map[$filter_type]) ? $type_map[$filter_type] : $type_map[''];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog - E-Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .genre-scroll::-webkit-scrollbar {
            width: 6px;
        }

        .genre-scroll::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .genre-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .genre-scroll::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .sidebar-transition {
            transition: transform 0.3s ease-in-out;
        }
    </style>
</head>

<body class="bg-gray-50 font-sans">

    <!-- OVERLAY MOBILE -->
    <div id="mobile-overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden bg-blur"></div>

    <?php if ($message): ?>
        <div onclick="this.remove()" class="fixed top-4 right-4 bg-yellow-600 text-white px-6 py-3 rounded-lg shadow-lg z-50 cursor-pointer animate-bounce">
            🌟 <?= $message ?>
        </div>
    <?php endif; ?>

    <div class="flex min-h-screen">

        <!-- SIDEBAR -->
        <aside id="sidebar" class="w-64 bg-white shadow-xl fixed inset-y-0 left-0 z-40 border-r transform -translate-x-full lg:translate-x-0 sidebar-transition h-full overflow-y-auto">
            <div class="p-6 border-b flex flex-col items-center relative">
                <button onclick="toggleSidebar()" class="absolute top-4 right-4 lg:hidden text-gray-500 hover:text-red-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>

                <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-2xl mb-3">👤</div>
                <h1 class="text-xl font-bold text-blue-900">E-Library</h1>
                <p class="text-xs text-gray-500 mt-1 text-center">Halo, <?= htmlspecialchars($current_user['name']) ?></p>
            </div>

            <nav class="p-4 space-y-2">
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'ADMIN'): ?>
                    <a href="dashboard-admin.php" class="flex items-center gap-3 px-4 py-3 bg-indigo-600 text-white rounded-lg font-bold shadow-md hover:bg-indigo-700 transition mb-6 ring-2 ring-indigo-200">
                        <span>⚡</span> Admin Panel
                    </a>
                <?php endif; ?>

                <a href="dashboard-user.php" class="flex items-center gap-3 px-4 py-3 bg-blue-50 text-blue-700 rounded-lg font-medium border border-blue-100">
                    <span>📚</span> Katalog
                </a>
                <a href="history.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-blue-700 rounded-lg font-medium transition">
                    <span>🕒</span> Riwayat
                </a>
                <a href="profile.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-blue-700 rounded-lg font-medium transition">
                    <span>⚙️</span> Profile
                </a>

                <!-- TOMBOL REQUEST PENERBIT -->
                <div class="pt-4 mt-4 border-t border-gray-200">
                    <?php if ($current_user['request_penerbit'] == '0'): ?>
                        <form method="POST">
                            <button type="submit" name="request_publisher" onclick="return confirm('Ingin mengajukan diri sebagai Penerbit?')" class="w-full text-left flex items-center gap-3 px-4 py-3 bg-purple-50 text-purple-700 hover:bg-purple-100 rounded-lg font-medium transition duration-200">
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
            <!-- Header Mobile -->
            <div class="lg:hidden flex items-center justify-between bg-white p-4 rounded-xl shadow-sm border mb-6 sticky top-0 z-20">
                <div class="flex items-center gap-3">
                    <button onclick="toggleSidebar()" class="text-gray-700 p-2 hover:bg-gray-100 rounded-lg focus:ring-2 focus:ring-blue-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path>
                        </svg>
                    </button>
                    <h1 class="font-bold text-blue-900 text-lg">E-Library</h1>
                </div>
                <a href="profile.php" class="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-sm border border-blue-200">👤</a>
            </div>

            <!-- SEARCH & FILTER SECTION -->
            <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 mb-8 flex flex-col gap-4 relative z-10">
                <form method="GET" class="flex flex-col md:flex-row gap-3 w-full items-stretch">
                    <!-- Search Bar -->
                    <div class="flex-1 relative">
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari judul, penulis, atau topik..." class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 outline-none transition h-full text-gray-700 bg-gray-50 focus:bg-white text-sm">
                        <span class="absolute left-3.5 top-3.5 text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </span>
                    </div>

                    <!-- DROPDOWN GENRE -->
                    <div class="relative min-w-[180px]" id="genreDropdownContainer">
                        <button type="button" onclick="toggleGenreDropdown()" class="w-full h-full px-4 py-3 border border-gray-200 rounded-xl bg-white outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 text-left flex justify-between items-center text-gray-700 transition hover:bg-gray-50 text-sm">
                            <span id="genreLabel" class="truncate mr-2 font-medium">
                                <?= empty($filter_genres) ? '📂 Semua Genre' : count($filter_genres) . ' Genre' ?>
                            </span>
                            <span class="text-gray-400 text-xs transform transition-transform duration-200" id="genreArrow">▼</span>
                        </button>
                        <div id="genrePanel" class="hidden absolute top-full left-0 mt-2 w-full bg-white border border-gray-100 rounded-xl shadow-xl z-50 p-2 max-h-60 overflow-y-auto genre-scroll">
                            <?php
                            mysqli_data_seek($genres_list, 0);
                            while ($g = mysqli_fetch_assoc($genres_list)):
                                $isChecked = in_array($g['id'], $filter_genres) ? 'checked' : '';
                            ?>
                                <label class="flex items-center space-x-3 p-2 hover:bg-indigo-50 rounded-lg cursor-pointer transition">
                                    <input type="checkbox" name="genres[]" value="<?= $g['id'] ?>" <?= $isChecked ?> class="w-4 h-4 text-indigo-600 rounded focus:ring-indigo-500 border-gray-300">
                                    <span class="text-sm text-gray-700 font-medium select-none"><?= htmlspecialchars($g['name']) ?></span>
                                </label>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <!-- DROPDOWN TIPE -->
                    <div class="relative min-w-[150px]" id="typeDropdownContainer">
                        <input type="hidden" name="type" id="typeInput" value="<?= htmlspecialchars($filter_type) ?>">
                        <button type="button" onclick="toggleTypeDropdown()" class="w-full h-full px-4 py-3 border border-gray-200 rounded-xl bg-white outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 text-left flex justify-between items-center text-gray-700 transition hover:bg-gray-50 text-sm">
                            <span id="typeLabel" class="truncate mr-2 font-medium"><?= $active_type_label ?></span>
                            <span class="text-gray-400 text-xs transform transition-transform duration-200" id="typeArrow">▼</span>
                        </button>
                        <div id="typePanel" class="hidden absolute top-full left-0 mt-2 w-full bg-white border border-gray-100 rounded-xl shadow-xl z-50 py-1 overflow-hidden">
                            <?php foreach ($type_map as $val => $label): $isSelected = ($filter_type == $val); ?>
                                <div onclick="selectType('<?= $val ?>', '<?= $label ?>')" class="px-4 py-2.5 hover:bg-indigo-50 cursor-pointer text-sm flex items-center justify-between group transition <?= $isSelected ? 'bg-indigo-50 text-indigo-600 font-bold' : 'text-gray-700 font-medium' ?>">
                                    <span><?= $label ?></span>
                                    <?php if ($isSelected): ?><span class="text-indigo-600">✓</span><?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-xl hover:bg-indigo-700 font-bold transition shadow-md shadow-indigo-200 flex-shrink-0 flex items-center gap-2 justify-center text-sm">
                        Cari
                    </button>
                </form>

                <!-- Filter Tags -->
                <?php if (!empty($filter_genres) || $filter_type || $search): ?>
                    <div class="flex flex-wrap gap-2 pt-2 border-t border-gray-100">
                        <div class="text-[10px] text-gray-400 font-bold py-1 uppercase tracking-wider">Filter Aktif:</div>
                        <?php if ($search): ?>
                            <span class="bg-yellow-50 text-yellow-700 px-2.5 py-0.5 rounded-full text-xs font-semibold border border-yellow-200 flex items-center gap-1">
                                🔍 <?= htmlspecialchars($search) ?>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($filter_genres)):
                            mysqli_data_seek($genres_list, 0);
                            while ($gx = mysqli_fetch_assoc($genres_list)) {
                                if (in_array($gx['id'], $filter_genres)) echo '<span class="bg-indigo-50 text-indigo-700 px-2.5 py-0.5 rounded-full text-xs font-semibold border border-indigo-200">📂 ' . htmlspecialchars($gx['name']) . '</span>';
                            }
                        endif; ?>
                        <?php if ($filter_type && isset($type_map[$filter_type])): ?>
                            <span class="bg-green-50 text-green-700 px-2.5 py-0.5 rounded-full text-xs font-semibold border border-green-200"><?= $type_map[$filter_type] ?></span>
                        <?php endif; ?>
                        <a href="dashboard-user.php" class="text-gray-400 hover:text-red-500 text-xs font-bold underline ml-auto py-0.5">Reset</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- BOOK GRID (IMPROVED PORTRAIT CARD) -->
            <?php if (mysqli_num_rows($books) > 0): ?>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6 pb-20">
                    <?php while ($book = mysqli_fetch_assoc($books)) { ?>
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-xl hover:shadow-blue-100 hover:-translate-y-1 transition-all duration-300 group flex flex-col h-full overflow-hidden">

                            <!-- Cover Portrait (Aspect Ratio 2:3) -->
                            <div class="w-full aspect-[2/3] relative overflow-hidden bg-gray-100">
                                <?php
                                $coverPath = '../uploads/covers/' . $book['cover'];
                                if (!empty($book['cover']) && file_exists($coverPath)):
                                ?>
                                    <img src="<?= $coverPath ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                                <?php else: ?>
                                    <div class="w-full h-full flex flex-col items-center justify-center text-gray-300">
                                        <svg class="w-8 h-8 mb-1 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                        </svg>
                                        <span class="text-[10px] font-bold uppercase tracking-wider opacity-60">No Cover</span>
                                    </div>
                                <?php endif; ?>

                                <!-- Hover Overlay Button -->
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center p-4">
                                    <a href="detail.php?id=<?= $book['id'] ?>" class="px-5 py-2 bg-white text-gray-900 text-xs font-bold rounded-full hover:bg-indigo-50 transition transform hover:scale-105 shadow-lg">
                                        Baca
                                    </a>
                                </div>

                                <!-- Floating Type Badge (With Blue Hover Accent) -->
                                <div class="absolute top-2 left-2">
                                    <span class="text-[9px] font-bold tracking-wider uppercase px-2 py-0.5 rounded shadow-sm bg-white/90 backdrop-blur text-gray-600 border border-gray-100 group-hover:text-blue-600 group-hover:border-blue-200 transition-colors duration-300">
                                        <?= $book['type'] ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="p-3 flex-1 flex flex-col">
                                <h3 class="font-bold text-gray-800 text-sm mb-1 leading-tight line-clamp-2 group-hover:text-blue-600 transition-colors" title="<?= htmlspecialchars($book['title']) ?>">
                                    <?= htmlspecialchars($book['title']) ?>
                                </h3>
                                <p class="text-xs text-gray-500 mb-2 font-medium line-clamp-1">
                                    <?= htmlspecialchars($book['author']) ?>
                                </p>

                                <!-- Footer Info -->
                                <div class="mt-auto flex items-center justify-between pt-2 border-t border-gray-50">
                                    <span class="text-[10px] text-gray-400 font-medium"><?= $book['year'] ?></span>

                                    <!-- Simple Genre Tag (Max 1) -->
                                    <?php
                                    $g_names = explode(',', $book['genre_names']);
                                    if (!empty($g_names[0]) && trim($g_names[0]) != ''): ?>
                                        <span class="text-[9px] bg-gray-50 text-gray-500 px-1.5 py-0.5 rounded border border-gray-100 truncate max-w-[80px] group-hover:text-blue-600 group-hover:border-blue-200 transition-colors duration-300">
                                            <?= trim($g_names[0]) ?>
                                            <?php if (count($g_names) > 1) echo '<span class="text-gray-400 ml-0.5">+' . (count($g_names) - 1) . '</span>'; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            <?php else: ?>
                <div class="flex flex-col items-center justify-center py-20 bg-white rounded-xl border border-dashed border-gray-200 text-center">
                    <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-3">
                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-gray-800 mb-1">Tidak ada hasil ditemukan</h3>
                    <p class="text-xs text-gray-500 max-w-xs mx-auto mb-4">Coba gunakan kata kunci lain.</p>
                    <a href="dashboard-user.php" class="px-4 py-2 bg-indigo-50 text-indigo-600 font-bold rounded-lg hover:bg-indigo-100 transition text-xs">Reset Filter</a>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Script JavaScript -->
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
            document.getElementById('genreArrow').style.transform = 'rotate(0deg)';
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
            document.getElementById('typeArrow').style.transform = 'rotate(0deg)';
        }

        function selectType(value, label) {
            document.getElementById('typeInput').value = value;
            document.getElementById('typeLabel').innerText = label;
            closeTypeDropdown();
        }

        document.addEventListener('click', function(event) {
            const genreContainer = document.getElementById('genreDropdownContainer');
            if (!genreContainer.contains(event.target)) closeGenreDropdown();
            const typeContainer = document.getElementById('typeDropdownContainer');
            if (!typeContainer.contains(event.target)) closeTypeDropdown();
        });
    </script>
</body>

</html>