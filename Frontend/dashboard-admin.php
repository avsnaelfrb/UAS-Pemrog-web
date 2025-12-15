<?php
require '../Backend/config.php';

// Cek Sesi & Role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'ADMIN') {
    header("Location: login.php");
    exit;
}

// ==========================================
// 1. LOGIKA UTAMA (HANDLER)
// ==========================================

$message = '';
$error_msg = '';
$active_page = isset($_GET['page']) ? $_GET['page'] : 'books'; // Default page: books

// --- HANDLER: BUKU (Create/Update/Delete) ---
if (isset($_POST['save_book'])) {
    $mode = $_POST['mode'];
    $book_id = isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0;
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $author = mysqli_real_escape_string($conn, $_POST['author']);
    $year = (int)$_POST['year'];
    $type = $_POST['type'];
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $selected_genres = isset($_POST['genres']) ? $_POST['genres'] : [];

    if (empty($selected_genres)) {
        $error_msg = "Wajib memilih minimal satu genre!";
    } else {
        $coverData = null;
        if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
            $coverData = file_get_contents($_FILES['cover']['tmp_name']);
        }
        $pdfData = null;
        if (isset($_FILES['file_book']) && $_FILES['file_book']['error'] === UPLOAD_ERR_OK) {
            $pdfData = file_get_contents($_FILES['file_book']['tmp_name']);
        }

        if ($mode == 'add' && $pdfData === null) {
            $error_msg = "File PDF wajib diupload untuk buku baru!";
        }

        if (empty($error_msg)) {
            $null = null;
            if ($mode == 'add') {
                $stmt = mysqli_prepare($conn, "INSERT INTO books (title, author, description, year, type, cover, file_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "sssisbb", $title, $author, $description, $year, $type, $null, $null);
                if ($coverData) mysqli_stmt_send_long_data($stmt, 5, $coverData);
                if ($pdfData) mysqli_stmt_send_long_data($stmt, 6, $pdfData);

                if (mysqli_stmt_execute($stmt)) {
                    $new_id = mysqli_insert_id($conn);
                    foreach ($selected_genres as $gid) mysqli_query($conn, "INSERT INTO book_genres VALUES ($new_id, $gid)");
                    $message = "Buku berhasil ditambahkan!";
                } else $error_msg = "Gagal: " . mysqli_error($conn);
            } else {
                // Edit Logic
                $stmt = mysqli_prepare($conn, "UPDATE books SET title=?, author=?, description=?, year=?, type=? WHERE id=?");
                mysqli_stmt_bind_param($stmt, "sssisi", $title, $author, $description, $year, $type, $book_id);
                mysqli_stmt_execute($stmt);

                if ($coverData) {
                    $stmt = mysqli_prepare($conn, "UPDATE books SET cover=? WHERE id=?");
                    mysqli_stmt_bind_param($stmt, "bi", $null, $book_id);
                    mysqli_stmt_send_long_data($stmt, 0, $coverData);
                    mysqli_stmt_execute($stmt);
                }
                if ($pdfData) {
                    $stmt = mysqli_prepare($conn, "UPDATE books SET file_path=? WHERE id=?");
                    mysqli_stmt_bind_param($stmt, "bi", $null, $book_id);
                    mysqli_stmt_send_long_data($stmt, 0, $pdfData);
                    mysqli_stmt_execute($stmt);
                }

                mysqli_query($conn, "DELETE FROM book_genres WHERE book_id=$book_id");
                foreach ($selected_genres as $gid) mysqli_query($conn, "INSERT INTO book_genres VALUES ($book_id, $gid)");
                $message = "Buku berhasil diperbarui!";
            }
        }
    }
}

if (isset($_GET['delete_book'])) {
    $id = (int)$_GET['delete_book'];
    mysqli_query($conn, "DELETE FROM book_genres WHERE book_id=$id");
    mysqli_query($conn, "DELETE FROM books WHERE id=$id");
    header("Location: dashboard-admin.php?page=books&msg=deleted");
    exit;
}

// --- HANDLER: USER (Delete Only) ---
if (isset($_GET['delete_user'])) {
    $uid = (int)$_GET['delete_user'];
    if ($uid == $_SESSION['user_id']) {
        $error_msg = "Tidak bisa menghapus akun sendiri!";
    } else {
        mysqli_query($conn, "DELETE FROM users WHERE id=$uid");
        header("Location: dashboard-admin.php?page=users&msg=deleted");
        exit;
    }
}

// --- HANDLER: GENRE (CRUD BARU) ---
if (isset($_POST['save_genre'])) {
    $genre_name = mysqli_real_escape_string($conn, $_POST['genre_name']);
    $genre_mode = $_POST['genre_mode'];
    $genre_id = isset($_POST['genre_id']) ? (int)$_POST['genre_id'] : 0;

    if (empty($genre_name)) {
        $error_msg = "Nama genre tidak boleh kosong!";
    } else {
        $cek = mysqli_query($conn, "SELECT id FROM genres WHERE name='$genre_name' AND id != $genre_id");
        if (mysqli_num_rows($cek) > 0) {
            $error_msg = "Genre '$genre_name' sudah ada!";
        } else {
            if ($genre_mode == 'add') {
                mysqli_query($conn, "INSERT INTO genres (name) VALUES ('$genre_name')");
                $message = "Genre baru berhasil ditambahkan!";
            } else {
                mysqli_query($conn, "UPDATE genres SET name='$genre_name' WHERE id=$genre_id");
                $message = "Genre berhasil diperbarui!";
            }
        }
    }
}

if (isset($_GET['delete_genre'])) {
    $gid = (int)$_GET['delete_genre'];
    if (mysqli_query($conn, "DELETE FROM genres WHERE id=$gid")) {
        header("Location: dashboard-admin.php?page=genres&msg=deleted");
        exit;
    } else {
        $error_msg = "Gagal menghapus genre.";
    }
}

// Pesan dari redirect
if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') {
    $message = "Data berhasil dihapus.";
}

// ==========================================
// 2. DATA FETCHING
// ==========================================

$genres_all = mysqli_query($conn, "SELECT * FROM genres ORDER BY name ASC");

// Data Edit Buku
$edit_book = null;
$edit_book_genres = [];
if ($active_page == 'books' && isset($_GET['edit_book'])) {
    $bid = (int)$_GET['edit_book'];
    $q = mysqli_query($conn, "SELECT id, title, author, description, year, type, CASE WHEN cover IS NOT NULL AND LENGTH(cover)>0 THEN 1 ELSE 0 END as has_cover, CASE WHEN file_path IS NOT NULL AND LENGTH(file_path)>0 THEN 1 ELSE 0 END as has_file FROM books WHERE id=$bid");
    $edit_book = mysqli_fetch_assoc($q);
    if ($edit_book) {
        $qg = mysqli_query($conn, "SELECT genre_id FROM book_genres WHERE book_id=$bid");
        while ($r = mysqli_fetch_assoc($qg)) $edit_book_genres[] = $r['genre_id'];
    }
}

// Data Edit Genre
$edit_genre = null;
if ($active_page == 'genres' && isset($_GET['edit_genre'])) {
    $gid = (int)$_GET['edit_genre'];
    $q = mysqli_query($conn, "SELECT * FROM genres WHERE id=$gid");
    $edit_genre = mysqli_fetch_assoc($q);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - E-Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .table-container {
            overflow-x: auto;
        }

        .active-nav {
            background-color: #eff6ff;
            color: #1e40af;
        }

        .sidebar-transition {
            transition: transform 0.3s ease-in-out;
        }
    </style>
</head>

<body class="bg-gray-50 font-sans">

    <!-- OVERLAY MOBILE -->
    <div id="mobile-overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>

    <div class="flex min-h-screen">

        <!-- ================= SIDEBAR ================= -->
        <aside id="sidebar" class="w-64 bg-white shadow-xl fixed inset-y-0 left-0 z-40 border-r transform -translate-x-full lg:translate-x-0 sidebar-transition h-full overflow-y-auto">
            <div class="p-6 border-b flex flex-col items-center relative">
                <button onclick="toggleSidebar()" class="absolute top-4 right-4 lg:hidden text-gray-400 hover:text-red-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>

                <div class="w-16 h-16 bg-blue-900 rounded-xl flex items-center justify-center text-3xl mb-3 shadow-lg text-white">
                    🏛️
                </div>
                <h1 class="text-xl font-bold text-gray-800">Admin Panel</h1>
                <p class="text-xs text-gray-500 mt-1">E-Library System</p>
            </div>

            <nav class="p-4 space-y-1">
                <div class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2 px-4 mt-2">Main Menu</div>

                <a href="?page=books" class="flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition hover:bg-gray-50 <?= $active_page == 'books' ? 'active-nav' : 'text-gray-600' ?>">
                    <span>📚</span> Kelola Buku
                </a>

                <a href="?page=genres" class="flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition hover:bg-gray-50 <?= $active_page == 'genres' ? 'active-nav' : 'text-gray-600' ?>">
                    <span>📂</span> Kelola Genre
                </a>

                <a href="?page=users" class="flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition hover:bg-gray-50 <?= $active_page == 'users' ? 'active-nav' : 'text-gray-600' ?>">
                    <span>👥</span> Kelola User
                </a>

                <a href="?page=history" class="flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition hover:bg-gray-50 <?= $active_page == 'history' ? 'active-nav' : 'text-gray-600' ?>">
                    <span>⏳</span> Riwayat Baca
                </a>

                <div class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2 px-4 mt-6">Akun</div>

                <a href="profile.php" class="flex items-center gap-3 px-4 py-3 rounded-lg font-medium text-gray-600 hover:bg-gray-50">
                    <span>⚙️</span> Edit Profil
                </a>
                <a href="dashboard-user.php" class="flex items-center gap-3 px-4 py-3 rounded-lg font-medium text-blue-600 hover:bg-blue-50">
                    <span>👀</span> Mode User
                </a>
                <a href="logout.php" class="flex items-center gap-3 px-4 py-3 rounded-lg font-medium text-red-600 hover:bg-red-50 mt-2">
                    <span>🚪</span> Logout
                </a>
            </nav>
        </aside>

        <!-- ================= MAIN CONTENT ================= -->
        <main class="flex-1 lg:ml-64 p-4 lg:p-8 transition-all duration-300">

            <!-- Header Mobile -->
            <div class="lg:hidden bg-white p-4 rounded-lg shadow-sm mb-4 flex justify-between items-center sticky top-0 z-20 border-b">
                <div class="flex items-center gap-3">
                    <button onclick="toggleSidebar()" class="text-gray-700 p-2 hover:bg-gray-100 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path>
                        </svg>
                    </button>
                    <span class="font-bold text-lg text-gray-800">Admin Panel</span>
                </div>
                <a href="logout.php" class="text-red-500 text-sm font-medium">Logout</a>
            </div>

            <!-- Global Notifications -->
            <?php if ($message): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm mb-6 flex items-center justify-between">
                    <span>✅ <?= $message ?></span>
                    <button onclick="this.parentElement.remove()" class="text-green-700 font-bold">×</button>
                </div>
            <?php endif; ?>

            <?php if ($error_msg): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm mb-6 flex items-center justify-between">
                    <span>❌ <?= $error_msg ?></span>
                    <button onclick="this.parentElement.remove()" class="text-red-700 font-bold">×</button>
                </div>
            <?php endif; ?>


            <!-- SWITCH CONTENT -->
            <?php switch ($active_page):

                    // ================= CASE: HISTORY =================
                case 'history':
                    // Query untuk mengambil data history dengan join ke users dan books
                    $histories = mysqli_query($conn, "
                        SELECT h.*, u.name as user_name, u.email as user_email, b.title as book_title, b.cover
                        FROM history h
                        JOIN users u ON h.user_id = u.id
                        JOIN books b ON h.book_id = b.id
                        ORDER BY h.read_at DESC
                    ");
            ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                        <div class="p-6 border-b flex justify-between items-center bg-gray-50 rounded-t-xl">
                            <div>
                                <h2 class="text-xl font-bold text-gray-800">Riwayat Pembacaan</h2>
                                <p class="text-gray-500 text-sm">Monitor aktivitas membaca seluruh pengguna</p>
                            </div>
                            <div class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-bold">Total: <?= mysqli_num_rows($histories) ?></div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse min-w-[600px]">
                                <thead class="bg-gray-100 text-gray-600 text-sm uppercase">
                                    <tr>
                                        <th class="p-4 w-16">Cover</th>
                                        <th class="p-4">Judul Buku</th>
                                        <th class="p-4">Pembaca</th>
                                        <th class="p-4">Waktu Akses</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php while ($h = mysqli_fetch_assoc($histories)): ?>
                                        <tr class="hover:bg-gray-50 transition">
                                            <td class="p-4 align-top">
                                                <?php if ($h['cover']): ?>
                                                    <img src="data:image/jpeg;base64,<?= base64_encode($h['cover']) ?>" class="w-10 h-14 object-cover rounded shadow border bg-white">
                                                <?php else: ?>
                                                    <div class="w-10 h-14 bg-gray-200 rounded flex items-center justify-center text-xs text-gray-400">N/A</div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-4 align-top font-medium text-gray-800">
                                                <?= htmlspecialchars($h['book_title']) ?>
                                            </td>
                                            <td class="p-4 align-top">
                                                <div class="font-bold text-sm text-gray-700"><?= htmlspecialchars($h['user_name']) ?></div>
                                                <div class="text-xs text-gray-500"><?= htmlspecialchars($h['user_email']) ?></div>
                                            </td>
                                            <td class="p-4 align-top text-sm text-gray-600">
                                                📅 <?= date('d M Y', strtotime($h['read_at'])) ?><br>
                                                ⏰ <?= date('H:i', strtotime($h['read_at'])) ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php break; ?>


                <?php
                    // ================= CASE: USERS =================
                case 'users':
                    $users = mysqli_query($conn, "SELECT * FROM users ORDER BY role ASC, created_at DESC");
                ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                        <div class="p-6 border-b flex justify-between items-center bg-gray-50 rounded-t-xl">
                            <div>
                                <h2 class="text-xl font-bold text-gray-800">Manajemen Pengguna</h2>
                                <p class="text-gray-500 text-sm">Lihat dan hapus akun pengguna</p>
                            </div>
                            <div class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-bold">Total: <?= mysqli_num_rows($users) ?></div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse min-w-[600px]">
                                <thead class="bg-gray-100 text-gray-600 text-sm uppercase">
                                    <tr>
                                        <th class="p-4">Nama User</th>
                                        <th class="p-4">Email / Login</th>
                                        <th class="p-4">NIM</th>
                                        <th class="p-4">Role</th>
                                        <th class="p-4 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php while ($u = mysqli_fetch_assoc($users)): ?>
                                        <tr class="hover:bg-gray-50 transition">
                                            <td class="p-4 font-medium">
                                                <?= htmlspecialchars($u['name']) ?>
                                                <?php if ($u['id'] == $_SESSION['user_id']) echo "<span class='text-xs text-blue-600 bg-blue-50 px-1 rounded ml-1'>You</span>"; ?>
                                            </td>
                                            <td class="p-4 text-gray-600"><?= htmlspecialchars($u['email']) ?></td>
                                            <td class="p-4 text-gray-500"><?= htmlspecialchars($u['nim']) ?></td>
                                            <td class="p-4">
                                                <span class="px-2 py-1 rounded text-xs font-bold border <?= $u['role'] == 'ADMIN' ? 'bg-purple-100 text-purple-700 border-purple-200' : 'bg-green-100 text-green-700 border-green-200' ?>">
                                                    <?= $u['role'] ?>
                                                </span>
                                            </td>
                                            <td class="p-4 text-right">
                                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                                    <a href="?page=users&delete_user=<?= $u['id'] ?>" onclick="return confirm('Hapus user ini?')" class="text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded text-sm transition font-medium">Hapus</a>
                                                <?php else: ?>
                                                    <span class="text-gray-300 cursor-not-allowed">Locked</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php break; ?>


                <?php
                    // ================= CASE: GENRES =================
                case 'genres':
                    $genres_list = mysqli_query($conn, "SELECT g.*, (SELECT COUNT(*) FROM book_genres bg WHERE bg.genre_id = g.id) as total_books FROM genres g ORDER BY name ASC");
                ?>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="md:col-span-1">
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 md:sticky md:top-6">
                                <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b">
                                    <?= $edit_genre ? '✏️ Edit Genre' : '➕ Tambah Genre' ?>
                                </h2>
                                <form method="POST">
                                    <input type="hidden" name="genre_mode" value="<?= $edit_genre ? 'edit' : 'add' ?>">
                                    <?php if ($edit_genre): ?>
                                        <input type="hidden" name="genre_id" value="<?= $edit_genre['id'] ?>">
                                    <?php endif; ?>

                                    <div class="mb-4">
                                        <label class="block text-gray-700 text-sm font-bold mb-2">Nama Kategori</label>
                                        <input type="text" name="genre_name" required value="<?= $edit_genre['name'] ?? '' ?>" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Contoh: Fiksi Ilmiah">
                                    </div>

                                    <div class="flex gap-2">
                                        <button type="submit" name="save_genre" class="flex-1 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 font-bold shadow transition">Simpan</button>
                                        <?php if ($edit_genre): ?>
                                            <a href="?page=genres" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-bold">Batal</a>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="md:col-span-2">
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                                <div class="p-4 bg-gray-50 border-b">
                                    <h3 class="font-bold text-gray-700">Daftar Genre Buku</h3>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left min-w-[300px]">
                                        <thead class="bg-gray-100 text-gray-600 text-sm">
                                            <tr>
                                                <th class="p-3">Nama Genre</th>
                                                <th class="p-3 text-center">Jml Buku</th>
                                                <th class="p-3 text-right">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <?php while ($g = mysqli_fetch_assoc($genres_list)): ?>
                                                <tr class="hover:bg-blue-50">
                                                    <td class="p-3 font-medium text-gray-800"><?= htmlspecialchars($g['name']) ?></td>
                                                    <td class="p-3 text-center">
                                                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full"><?= $g['total_books'] ?></span>
                                                    </td>
                                                    <td class="p-3 text-right space-x-1">
                                                        <a href="?page=genres&edit_genre=<?= $g['id'] ?>" class="text-yellow-600 hover:bg-yellow-100 px-2 py-1 rounded text-sm font-bold">Edit</a>
                                                        <a href="?page=genres&delete_genre=<?= $g['id'] ?>" onclick="return confirm('Hapus genre ini?')" class="text-red-600 hover:bg-red-100 px-2 py-1 rounded text-sm font-bold">Hapus</a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php break; ?>


                <?php
                    // ================= DEFAULT CASE: BOOKS =================
                default:
                    $books = mysqli_query($conn, "SELECT b.id, b.title, b.author, b.year, b.type, b.cover, CASE WHEN file_path IS NOT NULL AND LENGTH(file_path) > 0 THEN 1 ELSE 0 END as file_exists, GROUP_CONCAT(g.name SEPARATOR ', ') as genre_names FROM books b LEFT JOIN book_genres bg ON b.id = bg.book_id LEFT JOIN genres g ON bg.genre_id = g.id GROUP BY b.id ORDER BY b.created_at DESC");
                ?>

                    <!-- Form Upload Buku -->
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 mb-8">
                        <h2 class="text-xl font-bold mb-6 text-gray-800 border-b pb-2 flex items-center gap-2">
                            <?= $edit_book ? '✏️ Edit Data Buku' : '📚 Upload Buku Baru' ?>
                        </h2>

                        <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <input type="hidden" name="mode" value="<?= $edit_book ? 'edit' : 'add' ?>">
                            <?php if ($edit_book): ?> <input type="hidden" name="book_id" value="<?= $edit_book['id'] ?>"> <?php endif; ?>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-1">Judul Buku</label>
                                    <input type="text" name="title" required value="<?= $edit_book['title'] ?? '' ?>" class="w-full border rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 outline-none transition">
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-gray-700 text-sm font-bold mb-1">Penulis</label>
                                        <input type="text" name="author" required value="<?= $edit_book['author'] ?? '' ?>" class="w-full border rounded-lg p-2.5 outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 text-sm font-bold mb-1">Tahun</label>
                                        <input type="number" name="year" required value="<?= $edit_book['year'] ?? '' ?>" class="w-full border rounded-lg p-2.5 outline-none">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-1">Tipe</label>
                                    <select name="type" class="w-full border rounded-lg p-2.5 bg-white outline-none">
                                        <option value="BOOK" <?= ($edit_book['type'] ?? '') == 'BOOK' ? 'selected' : '' ?>>📘 Buku</option>
                                        <option value="JOURNAL" <?= ($edit_book['type'] ?? '') == 'JOURNAL' ? 'selected' : '' ?>>📓 Jurnal</option>
                                        <option value="ARTICLE" <?= ($edit_book['type'] ?? '') == 'ARTICLE' ? 'selected' : '' ?>>📰 Artikel</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2">Pilih Genre</label>
                                    <div class="bg-gray-50 p-3 rounded-lg border h-32 overflow-y-auto grid grid-cols-2 gap-2">
                                        <?php
                                        mysqli_data_seek($genres_all, 0);
                                        while ($g = mysqli_fetch_assoc($genres_all)):
                                            $isChecked = (in_array($g['id'], $edit_book_genres)) ? 'checked' : '';
                                        ?>
                                            <label class="flex items-center space-x-2 text-sm cursor-pointer hover:bg-gray-100 p-1 rounded">
                                                <input type="checkbox" name="genres[]" value="<?= $g['id'] ?>" <?= $isChecked ?> class="rounded text-blue-600 h-4 w-4">
                                                <span class="text-gray-700"><?= $g['name'] ?></span>
                                            </label>
                                        <?php endwhile; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <!-- Input File PDF -->
                                <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                                    <label class="block text-gray-700 text-sm font-bold mb-1">File PDF <span class="text-red-500">*</span></label>
                                    <input type="file" name="file_book" accept="application/pdf" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-100 file:text-blue-700 hover:file:bg-blue-200">
                                    <?php if ($edit_book && $edit_book['has_file']): ?>
                                        <p class="text-xs text-green-600 mt-1">✅ File sudah ada (Upload ulang untuk mengganti)</p>
                                    <?php endif; ?>
                                </div>

                                <!-- Input Cover (Diselaraskan) -->
                                <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                                    <label class="block text-gray-700 text-sm font-bold mb-1">Cover Gambar</label>
                                    <input type="file" name="cover" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-100 file:text-blue-700 hover:file:bg-blue-200">
                                </div>

                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-1">Deskripsi</label>
                                    <textarea name="description" class="w-full border rounded-lg p-2.5 outline-none h-24"><?= $edit_book['description'] ?? '' ?></textarea>
                                </div>
                            </div>

                            <div class="col-span-1 md:col-span-2 flex gap-3 pt-4 border-t">
                                <button type="submit" name="save_book" class="bg-blue-600 text-white px-6 py-2.5 rounded-lg hover:bg-blue-700 font-bold shadow-lg flex-1 transition">
                                    <?= $edit_book ? 'Simpan Perubahan' : 'Upload Sekarang' ?>
                                </button>
                                <?php if ($edit_book): ?>
                                    <a href="?page=books" class="bg-gray-200 text-gray-700 px-6 py-2.5 rounded-lg hover:bg-gray-300 font-bold text-center">Batal Edit</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>

                    <!-- Tabel Buku -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="p-4 bg-gray-50 border-b flex justify-between items-center bg-gray-50 rounded-t-xl">
                            <h3 class="font-bold text-gray-700">Daftar Koleksi Digital</h3>
                            <span class="text-xs bg-white border px-2 py-1 rounded text-gray-500">Total: <?= mysqli_num_rows($books) ?></span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse min-w-[700px]">
                                <thead class="bg-gray-100 text-gray-600 text-sm uppercase">
                                    <tr>
                                        <th class="p-4 w-20">Cover</th>
                                        <th class="p-4">Detail Buku</th>
                                        <th class="p-4 w-48">Genre</th>
                                        <th class="p-4 w-32">Status File</th>
                                        <th class="p-4 text-right w-32">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php while ($b = mysqli_fetch_assoc($books)): ?>
                                        <tr class="hover:bg-blue-50 transition">
                                            <td class="p-4 align-top">
                                                <?php if ($b['cover']): ?>
                                                    <img src="data:image/jpeg;base64,<?= base64_encode($b['cover']) ?>" class="w-12 h-16 object-cover rounded shadow">
                                                <?php else: ?>
                                                    <div class="w-12 h-16 bg-gray-200 rounded flex items-center justify-center text-xs text-gray-400">N/A</div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-4 align-top">
                                                <div class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($b['title']) ?></div>
                                                <div class="text-sm text-gray-500"><?= htmlspecialchars($b['author']) ?></div>
                                                <div class="mt-1 text-xs text-gray-400">
                                                    <?= $b['year'] ?> • <span class="uppercase"><?= $b['type'] ?></span>
                                                </div>
                                            </td>
                                            <td class="p-4 align-top">
                                                <div class="flex flex-wrap gap-1">
                                                    <?php foreach (explode(',', $b['genre_names']) as $gn): if (trim($gn) == '') continue; ?>
                                                        <span class="text-[10px] bg-blue-100 text-blue-700 px-2 py-1 rounded border border-blue-200"><?= trim($gn) ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            </td>
                                            <td class="p-4 align-top">
                                                <?php if ($b['file_exists']): ?>
                                                    <span class="text-green-600 font-bold flex items-center gap-1 bg-green-50 px-2 py-1 rounded w-max text-xs border border-green-200">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        Stored
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-red-500 font-bold bg-red-50 px-2 py-1 rounded text-xs border border-red-200">Empty</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-4 align-top text-right space-y-2">
                                                <a href="?page=books&edit_book=<?= $b['id'] ?>" class="block bg-yellow-400 hover:bg-yellow-500 text-white text-xs font-bold px-2 py-1.5 rounded text-center shadow-sm">
                                                    ✏️ Edit
                                                </a>
                                                <a href="?page=books&delete_book=<?= $b['id'] ?>" onclick="return confirm('Hapus buku ini secara permanen?')" class="block bg-red-100 hover:bg-red-200 text-red-700 text-xs font-bold px-2 py-1.5 rounded text-center border border-red-200">
                                                    🗑️ Hapus
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php break; ?>

            <?php endswitch; ?>

        </main>
    </div>

    <!-- Script JavaScript untuk Toggle Sidebar -->
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobile-overlay');

            if (sidebar.classList.contains('-translate-x-full')) {
                // BUKA SIDEBAR
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
            } else {
                // TUTUP SIDEBAR
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            }
        }
    </script>

</body>

</html>