<?php
require '../Backend/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 1. Ambil Data Genre
$genres_list = mysqli_query($conn, "SELECT * FROM genres ORDER BY name ASC");

// 2. Tangkap Input Filter
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';
$filter_genres = isset($_GET['genres']) ? $_GET['genres'] : []; // Array genres

// 3. Susun Query Utama
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

// Filter Genre (Multi-select)
if (!empty($filter_genres)) {
    $genre_ids = array_map('intval', $filter_genres);
    $ids_string = implode(',', $genre_ids);
    $sql .= " AND b.id IN (SELECT book_id FROM book_genres WHERE genre_id IN ($ids_string))";
}

$sql .= " GROUP BY b.id ORDER BY b.created_at DESC";
$books = mysqli_query($conn, $sql);

// Helper: Map Label Tipe agar Tampil Lebih Cantik
$type_map = [
    '' => '📄 Semua Tipe',
    'BOOK' => '📘 Buku',
    'JOURNAL' => '📓 Jurnal',
    'ARTICLE' => '📰 Artikel'
];
// Label tipe yang sedang aktif untuk ditampilkan di tombol dropdown
$active_type_label = isset($type_map[$filter_type]) ? $type_map[$filter_type] : $type_map[''];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
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
    </style>
</head>

<body class="bg-gray-50 font-sans">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-xl fixed inset-y-0 left-0 z-20 hidden lg:block border-r">
            <div class="p-6 border-b flex flex-col items-center">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center text-2xl mb-3">👤</div>
                <h1 class="text-xl font-bold text-blue-900">E-Library</h1>
                <p class="text-xs text-gray-500 mt-1 text-center">Halo, <?= htmlspecialchars($_SESSION['name']) ?></p>
            </div>
            <nav class="p-4 space-y-2">
                <a href="dashboard-user.php" class="flex items-center gap-3 px-4 py-3 bg-blue-50 text-blue-700 rounded-lg font-medium border border-blue-100">
                    <span>📚</span> Katalog Buku
                </a>
                <a href="profile.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg font-medium transition">
                    <span>⚙️</span> Profil Saya
                </a>
                <a href="logout.php" class="flex items-center gap-3 px-4 py-3 text-red-600 hover:bg-red-50 rounded-lg mt-auto pt-4 border-t">
                    <span>🚪</span> Keluar
                </a>
            </nav>
        </aside>

        <main class="flex-1 lg:ml-64 p-4 lg:p-8">
            <div class="bg-white p-6 rounded-xl shadow-sm border mb-8 flex flex-col gap-4 relative z-10">

                <!-- Form Pencarian & Filter -->
                <form method="GET" class="flex flex-col md:flex-row gap-4 w-full items-stretch">
                    <!-- Search Bar -->
                    <div class="flex-1 relative">
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari judul atau penulis..." class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition h-full">
                        <span class="absolute left-3 top-3.5 text-gray-400">🔍</span>
                    </div>

                    <!-- DROPDOWN GENRE (Multi-Select) -->
                    <div class="relative min-w-[220px]" id="genreDropdownContainer">
                        <button type="button" onclick="toggleGenreDropdown()" class="w-full h-full px-4 py-3 border border-gray-200 rounded-lg bg-white outline-none focus:ring-2 focus:ring-blue-500 text-left flex justify-between items-center text-gray-700 transition hover:bg-gray-50">
                            <span id="genreLabel" class="truncate mr-2">
                                <?= empty($filter_genres) ? '📂 Pilih Genre' : count($filter_genres) . ' Genre Dipilih' ?>
                            </span>
                            <span class="text-gray-400 text-xs transform transition-transform duration-200" id="genreArrow">▼</span>
                        </button>

                        <div id="genrePanel" class="hidden absolute top-full left-0 mt-2 w-full bg-white border border-gray-200 rounded-lg shadow-xl z-50 p-2 max-h-60 overflow-y-auto genre-scroll">
                            <div class="text-xs text-gray-400 mb-2 px-2 uppercase font-bold tracking-wider">Kategori</div>
                            <?php
                            mysqli_data_seek($genres_list, 0);
                            while ($g = mysqli_fetch_assoc($genres_list)):
                                $isChecked = in_array($g['id'], $filter_genres) ? 'checked' : '';
                            ?>
                                <label class="flex items-center space-x-3 p-2 hover:bg-blue-50 rounded cursor-pointer transition">
                                    <input type="checkbox" name="genres[]" value="<?= $g['id'] ?>" <?= $isChecked ?> class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500 border-gray-300">
                                    <span class="text-sm text-gray-700 select-none"><?= htmlspecialchars($g['name']) ?></span>
                                </label>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <!-- DROPDOWN TIPE (Modern Style - Single Select) -->
                    <div class="relative min-w-[180px]" id="typeDropdownContainer">
                        <!-- Input Hidden untuk menyimpan value saat form disubmit -->
                        <input type="hidden" name="type" id="typeInput" value="<?= htmlspecialchars($filter_type) ?>">

                        <button type="button" onclick="toggleTypeDropdown()" class="w-full h-full px-4 py-3 border border-gray-200 rounded-lg bg-white outline-none focus:ring-2 focus:ring-blue-500 text-left flex justify-between items-center text-gray-700 transition hover:bg-gray-50">
                            <span id="typeLabel" class="truncate mr-2"><?= $active_type_label ?></span>
                            <span class="text-gray-400 text-xs transform transition-transform duration-200" id="typeArrow">▼</span>
                        </button>

                        <div id="typePanel" class="hidden absolute top-full left-0 mt-2 w-full bg-white border border-gray-200 rounded-lg shadow-xl z-50 py-1 overflow-hidden">
                            <?php foreach ($type_map as $val => $label):
                                $isSelected = ($filter_type == $val);
                            ?>
                                <div onclick="selectType('<?= $val ?>', '<?= $label ?>')"
                                    class="px-4 py-2.5 hover:bg-blue-50 cursor-pointer text-sm flex items-center justify-between group transition <?= $isSelected ? 'bg-blue-50 text-blue-600 font-medium' : 'text-gray-700' ?>">
                                    <span><?= $label ?></span>
                                    <?php if ($isSelected): ?>
                                        <span class="text-blue-600">✓</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <button type="submit" class="px-8 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-bold transition shadow-md shadow-blue-200 flex-shrink-0">
                        Cari
                    </button>
                </form>

                <!-- Badge Filter Aktif -->
                <?php if (!empty($filter_genres) || $filter_type || $search): ?>
                    <div class="flex flex-wrap gap-2 text-sm text-gray-600 items-center">
                        <span class="text-xs font-semibold mr-1">Filter Aktif:</span>

                        <?php if ($search): ?>
                            <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full border border-yellow-200 text-xs flex items-center gap-1">
                                🔍 "<?= htmlspecialchars($search) ?>"
                            </span>
                        <?php endif; ?>

                        <?php if (!empty($filter_genres)): ?>
                            <?php
                            mysqli_data_seek($genres_list, 0);
                            while ($gx = mysqli_fetch_assoc($genres_list)) {
                                if (in_array($gx['id'], $filter_genres)) {
                                    echo '<span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full border border-blue-200 text-xs flex items-center gap-1">📂 ' . htmlspecialchars($gx['name']) . '</span>';
                                }
                            }
                            ?>
                        <?php endif; ?>

                        <?php if ($filter_type && isset($type_map[$filter_type])): ?>
                            <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full border border-green-200 text-xs flex items-center gap-1">
                                <?= $type_map[$filter_type] ?>
                            </span>
                        <?php endif; ?>

                        <a href="dashboard-user.php" class="text-gray-400 hover:text-red-500 hover:underline ml-auto text-xs font-medium transition">
                            Reset Filter
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tombol Profil Mobile -->
            <div class="lg:hidden mb-4 flex justify-end">
                <a href="profile.php" class="bg-white px-4 py-2 rounded-lg shadow text-blue-600 font-bold flex items-center gap-2">
                    ⚙️ Edit Profil
                </a>
            </div>

            <!-- GRID BUKU -->
            <?php if (mysqli_num_rows($books) > 0): ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <?php while ($book = mysqli_fetch_assoc($books)) { ?>
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-lg transition duration-300 flex flex-col h-full group">
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
                    <div class="text-4xl mb-4">📂</div>
                    <h3 class="text-lg font-medium text-gray-900">Tidak ada buku ditemukan</h3>
                    <p class="text-gray-500 text-sm">Coba ubah kata kunci atau filter pencarian Anda.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Script Dropdown -->
    <script>
        // --- LOGIKA DROPDOWN GENRE ---
        function toggleGenreDropdown() {
            const panel = document.getElementById('genrePanel');
            const arrow = document.getElementById('genreArrow');

            // Tutup Type jika terbuka agar tidak tumpang tindih
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

        // --- LOGIKA DROPDOWN TYPE ---
        function toggleTypeDropdown() {
            const panel = document.getElementById('typePanel');
            const arrow = document.getElementById('typeArrow');

            // Tutup Genre jika terbuka
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

        // --- GLOBAL CLICK LISTENER ---
        document.addEventListener('click', function(event) {
            // Cek klik di luar Genre
            const genreContainer = document.getElementById('genreDropdownContainer');
            if (!genreContainer.contains(event.target)) {
                closeGenreDropdown();
            }

            // Cek klik di luar Type
            const typeContainer = document.getElementById('typeDropdownContainer');
            if (!typeContainer.contains(event.target)) {
                closeTypeDropdown();
            }
        });
    </script>
</body>

</html>