<?php
require_once dirname(__DIR__) . '/Backend/config.php';
checkRole('ADMIN');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'ADMIN') {
    header("Location: login.php");
    exit;
}

$message = '';
$error_msg = '';
$active_page = isset($_GET['page']) ? $_GET['page'] : 'books';

if (isset($_GET['delete_book'])) {
    $id = (int)$_GET['delete_book'];

    $q_file = mysqli_query($conn, "SELECT cover, file_path FROM books WHERE id=$id");
    if ($row_file = mysqli_fetch_assoc($q_file)) {
        $dirBooks = "../uploads/books/";
        $dirCovers = "../uploads/covers/";

        if (!empty($row_file['file_path'])) {
            deleteFile($dirBooks . $row_file['file_path']);
        }
        if (!empty($row_file['cover'])) {
            deleteFile($dirCovers . $row_file['cover']);
        }
    }

    if (mysqli_query($conn, "DELETE FROM books WHERE id=$id")) {
        header("Location: dashboard-admin.php?page=books&msg=deleted");
        exit;
    } else {
        $error_msg = "Gagal menghapus buku.";
    }
}

if (isset($_GET['approve_publisher'])) {
    $uid = (int)$_GET['approve_publisher'];
    mysqli_query($conn, "UPDATE users SET role='PENERBIT', request_penerbit='0' WHERE id=$uid");
    $message = "User berhasil diupgrade menjadi Penerbit!";
}
if (isset($_GET['reject_publisher'])) {
    $uid = (int)$_GET['reject_publisher'];
    mysqli_query($conn, "UPDATE users SET request_penerbit='0' WHERE id=$uid");
    $message = "Permintaan menjadi penerbit ditolak.";
}

if (isset($_GET['approve_book'])) {
    $bid = (int)$_GET['approve_book'];
    mysqli_query($conn, "UPDATE books SET status='APPROVED' WHERE id=$bid");
    $message = "Buku berhasil diterbitkan!";
}
if (isset($_GET['reject_book'])) {
    $bid = (int)$_GET['reject_book'];
    mysqli_query($conn, "UPDATE books SET status='REJECTED' WHERE id=$bid");
    $message = "Buku ditolak.";
}

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

if (isset($_POST['save_genre'])) {
    $name = mysqli_real_escape_string($conn, $_POST['genre_name']);
    if (!empty($name)) {
        $check = mysqli_query($conn, "SELECT id FROM genres WHERE name = '$name'");
        if (mysqli_num_rows($check) > 0) {
            $error_msg = "Genre sudah ada!";
        } else {
            mysqli_query($conn, "INSERT INTO genres (name) VALUES ('$name')");
            $message = "Genre berhasil ditambahkan.";
        }
    }
}
if (isset($_GET['delete_genre'])) {
    $gid = (int)$_GET['delete_genre'];
    mysqli_query($conn, "DELETE FROM genres WHERE id=$gid");
    header("Location: dashboard-admin.php?page=genres&msg=deleted");
    exit;
}

if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') $message = "Data berhasil dihapus.";

// NOTIFICATIONS
$notif_req_penerbit = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users WHERE request_penerbit='1'"));
$notif_req_buku = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM books WHERE status='PENDING'"));

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - E-Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .active-nav {
            background-color: #eff6ff;
            color: #1e40af;
        }
    </style>
</head>

<body class="bg-gray-50 font-sans">

    <!-- Sidebar Desktop -->
    <div class="flex min-h-screen">
        <aside class="w-64 bg-white shadow-xl fixed inset-y-0 left-0 z-40 border-r hidden lg:block">
            <div class="p-6 border-b flex flex-col items-center">
                <div class="text-3xl mb-2">🛡️</div>
                <h1 class="font-bold text-gray-800">Admin Panel</h1>
                <p class="text-xs text-gray-500">Moderation Mode</p>
            </div>
            <nav class="p-4 space-y-1">
                <div class="text-xs font-bold text-gray-400 px-4 mt-2">UTAMA</div>
                <a href="?page=books" class="flex justify-between items-center px-4 py-3 rounded-lg hover:bg-gray-50 <?= $active_page == 'books' ? 'active-nav' : '' ?>">
                    <span>📚 Kelola Buku</span>
                </a>
                <a href="?page=genres" class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-50 <?= $active_page == 'genres' ? 'active-nav' : '' ?>">📂 Kelola Genre</a>
                <a href="?page=users" class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-50 <?= $active_page == 'users' ? 'active-nav' : '' ?>">👥 Kelola User</a>
                <a href="?page=history" class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-50 <?= $active_page == 'history' ? 'active-nav' : '' ?>">⏳ Riwayat Baca</a>

                <div class="text-xs font-bold text-gray-400 px-4 mt-6">VALIDASI</div>
                <a href="?page=validation_users" class="flex justify-between items-center px-4 py-3 rounded-lg hover:bg-gray-50 <?= $active_page == 'validation_users' ? 'active-nav' : '' ?>">
                    <span>🥇 Req. Penerbit</span>
                    <?php if ($notif_req_penerbit > 0): ?><span class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full"><?= $notif_req_penerbit ?></span><?php endif; ?>
                </a>
                <a href="?page=validation_books" class="flex justify-between items-center px-4 py-3 rounded-lg hover:bg-gray-50 <?= $active_page == 'validation_books' ? 'active-nav' : '' ?>">
                    <span>📖 Req. Buku</span>
                    <?php if ($notif_req_buku > 0): ?><span class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full"><?= $notif_req_buku ?></span><?php endif; ?>
                </a>

                <div class="text-xs font-bold text-gray-400 px-4 mt-6">AKUN</div>
                <a href="profile.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-50">⚙️ Edit Profil</a>
                <a href="logout.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-red-50 text-red-600">🚪 Logout</a>
            </nav>
        </aside>

        <main class="flex-1 lg:ml-64 p-8">
            <!-- Notifikasi -->
            <?php if ($message): ?><div class="bg-green-100 text-green-700 p-4 rounded mb-6">✅ <?= $message ?></div><?php endif; ?>
            <?php if ($error_msg): ?><div class="bg-red-100 text-red-700 p-4 rounded mb-6">❌ <?= $error_msg ?></div><?php endif; ?>

            <?php switch ($active_page):

                    // === HALAMAN BUKU (Hanya Tabel & Hapus) ===
                default:
                case 'books':
                    $books = mysqli_query($conn, "SELECT b.*, u.name as uploader FROM books b LEFT JOIN users u ON b.uploaded_by = u.id ORDER BY b.created_at DESC");
            ?>
                    <div class="flex justify-between items-end mb-6">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800">📚 Semua Koleksi Digital</h2>
                            <p class="text-gray-500 text-sm mt-1">Kelola dan moderasi konten buku dalam sistem.</p>
                        </div>
                        <div class="bg-blue-50 text-blue-700 px-4 py-2 rounded-lg text-sm font-bold border border-blue-100">
                            Total: <?= mysqli_num_rows($books) ?> Buku
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-blue-50 border-b border-blue-100 text-blue-900 uppercase text-xs tracking-wider">
                                <tr>
                                    <th class="p-5 font-bold w-16">Cover</th>
                                    <th class="p-5 font-bold">Informasi Buku</th>
                                    <th class="p-5 font-bold">Penerbit/Uploader</th>
                                    <th class="p-5 font-bold">Status</th>
                                    <th class="p-5 font-bold text-right">Moderasi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php if (mysqli_num_rows($books) > 0): ?>
                                    <?php while ($b = mysqli_fetch_assoc($books)): ?>
                                        <tr class="hover:bg-gray-50 transition duration-150">
                                            <td class="p-5 align-top">
                                                <div class="w-12 h-16 bg-gray-200 rounded overflow-hidden shadow-sm border border-gray-100">
                                                    <?php
                                                    $coverPath = '../uploads/covers/' . $b['cover'];
                                                    if (!empty($b['cover']) && file_exists($coverPath)):
                                                    ?>
                                                        <img src="<?= $coverPath ?>" class="w-full h-full object-cover">
                                                    <?php else: ?>
                                                        <div class="w-full h-full flex items-center justify-center text-[10px] text-gray-400">No img</div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="p-5 align-top">
                                                <div class="font-bold text-gray-800 text-base mb-1"><?= htmlspecialchars($b['title']) ?></div>
                                                <div class="flex items-center gap-2 text-xs text-gray-500">
                                                    <span class="bg-gray-100 px-2 py-0.5 rounded text-gray-600 font-medium"><?= $b['type'] ?></span>
                                                    <span>•</span>
                                                    <span><?= $b['author'] ?></span>
                                                    <span>•</span>
                                                    <span><?= $b['year'] ?></span>
                                                </div>
                                            </td>
                                            <td class="p-5 align-top">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center text-xs font-bold text-gray-500">
                                                        <?= $b['uploaded_by'] ? strtoupper(substr($b['uploader'], 0, 1)) : '?' ?>
                                                    </div>
                                                    <div class="text-sm">
                                                        <?= $b['uploaded_by'] ? htmlspecialchars($b['uploader']) : '<span class="text-gray-400 italic">System Legacy</span>' ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="p-5 align-top">
                                                <?= getStatusBadge($b['status']) ?>
                                            </td>
                                            <td class="p-5 align-top text-right">
                                                <a href="?page=books&delete_book=<?= $b['id'] ?>" onclick="return confirm('PERINGATAN: Menghapus buku ini bersifat permanen. Lanjutkan?')" class="inline-flex items-center gap-1 bg-red-50 hover:bg-red-100 text-red-600 text-xs font-bold px-3 py-2 rounded-lg transition border border-red-100">
                                                    <span>🗑️</span> Hapus
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="p-10 text-center text-gray-400 italic">
                                            Belum ada buku dalam database.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php break; ?>

                <?php
                    // === HALAMAN GENRE (IMPROVED) ===
                case 'genres':
                    // Query untuk mengambil Genre + Jumlah Bukunya
                    // Menggunakan LEFT JOIN agar genre yang belum ada bukunya tetap muncul (count = 0)
                    $q_genre = "
                        SELECT g.id, g.name, COUNT(bg.book_id) as total_books
                        FROM genres g
                        LEFT JOIN book_genres bg ON g.id = bg.genre_id
                        GROUP BY g.id, g.name
                        ORDER BY g.name ASC
                    ";
                    $genres = mysqli_query($conn, $q_genre);

                    // Hitung total genre
                    $total_genres = mysqli_num_rows($genres);

                    // Reset pointer agar bisa diloop ulang di tabel
                    mysqli_data_seek($genres, 0);
                ?>
                    <div class="flex justify-between items-end mb-8">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800">📂 Kelola Kategori Genre</h2>
                            <p class="text-gray-500 text-sm mt-1">Atur kategori buku agar katalog lebih terstruktur.</p>
                        </div>

                        <!-- Statistik Ringkas -->
                        <div class="flex gap-4">
                            <div class="bg-white px-4 py-2 rounded-lg shadow-sm border border-gray-100 flex items-center gap-3">
                                <div class="bg-blue-50 text-blue-600 p-2 rounded-md">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500 uppercase font-bold tracking-wider">Total Genre</div>
                                    <div class="text-lg font-bold text-gray-800"><?= $total_genres ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
                        <!-- Form Tambah Genre -->
                        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 sticky top-4">
                            <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2 pb-3 border-b border-gray-100">
                                <span class="bg-green-100 text-green-600 w-8 h-8 flex items-center justify-center rounded-lg text-sm">➕</span>
                                Tambah Genre Baru
                            </h3>
                            <form method="POST">
                                <div class="mb-4">
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Nama Kategori</label>
                                    <input type="text" name="genre_name" placeholder="Misal: Psikologi, Sejarah..." required
                                        class="w-full border border-gray-300 p-3 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition text-sm bg-gray-50 focus:bg-white">
                                </div>
                                <button type="submit" name="save_genre" class="w-full bg-green-600 text-white py-3 rounded-lg font-bold hover:bg-green-700 transition shadow-md shadow-green-100 flex items-center justify-center gap-2">
                                    <span>Simpan Genre</span>
                                </button>
                            </form>
                            <p class="text-xs text-gray-400 mt-4 leading-relaxed text-center">
                                Tips: Gunakan nama kategori yang umum agar mudah dicari oleh pengguna.
                            </p>
                        </div>

                        <!-- Tabel Daftar Genre -->
                        <div class="lg:col-span-2 bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                            <div class="p-5 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                                <h4 class="font-bold text-gray-700">Daftar Genre Tersedia</h4>
                            </div>
                            <table class="w-full text-left border-collapse">
                                <thead class="bg-white border-b border-gray-100 text-gray-400 uppercase text-xs tracking-wider">
                                    <tr>
                                        <th class="p-5 font-bold">Nama Genre</th>
                                        <th class="p-5 font-bold text-center w-32">Jumlah Buku</th>
                                        <th class="p-5 font-bold text-right w-24">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php if ($total_genres > 0): ?>
                                        <?php while ($g = mysqli_fetch_assoc($genres)): ?>
                                            <tr class="hover:bg-gray-50 transition duration-150 group">
                                                <td class="p-5 font-medium text-gray-700">
                                                    <div class="flex items-center gap-3">
                                                        <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                                                        <?= htmlspecialchars($g['name']) ?>
                                                    </div>
                                                </td>
                                                <td class="p-5 text-center">
                                                    <?php if ($g['total_books'] > 0): ?>
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                            <?= $g['total_books'] ?> Buku
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-400">
                                                            Kosong
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="p-5 text-right">
                                                    <a href="?page=genres&delete_genre=<?= $g['id'] ?>" onclick="return confirm('Yakin hapus genre \'<?= $g['name'] ?>\'? \nPERINGATAN: Pastikan tidak ada buku penting yang hanya memiliki genre ini.')"
                                                        class="text-gray-400 hover:text-red-600 p-2 rounded-lg hover:bg-red-50 transition-colors inline-block" title="Hapus Genre">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="p-10 text-center text-gray-400 italic">Belum ada genre yang ditambahkan.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php break; ?>

                <?php
                    // === HALAMAN VALIDASI BUKU ===
                case 'validation_books':
                    $req_books = mysqli_query($conn, "SELECT b.*, u.name as publisher_name FROM books b JOIN users u ON b.uploaded_by = u.id WHERE b.status='PENDING'");
                ?>
                    <h2 class="text-2xl font-bold mb-6">Validasi Buku Masuk (Pending)</h2>
                    <?php if (mysqli_num_rows($req_books) == 0): ?><p class="text-gray-500">Tidak ada buku menunggu review.</p><?php else: ?>
                        <div class="grid gap-4">
                            <?php while ($b = mysqli_fetch_assoc($req_books)): ?>
                                <div class="bg-white p-4 rounded-xl shadow border flex gap-4 items-start">
                                    <?php
                                                                                                                                    $coverPath = '../uploads/covers/' . $b['cover'];
                                                                                                                                    if (!empty($b['cover']) && file_exists($coverPath)):
                                    ?>
                                        <img src="<?= $coverPath ?>" class="w-20 h-28 object-cover rounded">
                                    <?php endif; ?>

                                    <div class="flex-1">
                                        <h3 class="font-bold text-lg"><?= htmlspecialchars($b['title']) ?></h3>
                                        <p class="text-sm text-gray-600">Oleh Penerbit: <b><?= htmlspecialchars($b['publisher_name']) ?></b></p>
                                        <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($b['author']) ?> • <?= $b['year'] ?> • <?= $b['type'] ?></p>

                                        <div class="mt-4 flex gap-2">
                                            <a href="read.php?id=<?= $b['id'] ?>" target="_blank" class="bg-gray-100 text-gray-700 px-3 py-1.5 rounded text-sm hover:bg-gray-200">Preview PDF</a>
                                            <a href="?page=validation_books&approve_book=<?= $b['id'] ?>" class="bg-green-600 text-white px-3 py-1.5 rounded text-sm hover:bg-green-700">Terbitkan</a>
                                            <a href="?page=validation_books&reject_book=<?= $b['id'] ?>" class="bg-red-100 text-red-700 px-3 py-1.5 rounded text-sm hover:bg-red-200">Tolak</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php endif;
                                                                                                                            break; ?>

                <?php
                    // === HALAMAN VALIDASI USER ===
                case 'validation_users':
                    $req_users = mysqli_query($conn, "SELECT * FROM users WHERE request_penerbit='1'");
                ?>
                    <h2 class="text-2xl font-bold mb-6">Validasi Pengajuan Penerbit</h2>
                    <?php if (mysqli_num_rows($req_users) == 0): ?><p class="text-gray-500">Tidak ada permintaan baru.</p><?php else: ?>
                        <div class="bg-white rounded-xl shadow border overflow-hidden">
                            <table class="w-full text-left">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="p-4">Nama</th>
                                        <th class="p-4">Email</th>
                                        <th class="p-4 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($r = mysqli_fetch_assoc($req_users)): ?>
                                        <tr class="border-b">
                                            <td class="p-4"><?= htmlspecialchars($r['name']) ?></td>
                                            <td class="p-4"><?= htmlspecialchars($r['email']) ?></td>
                                            <td class="p-4 text-right space-x-2">
                                                <a href="?page=validation_users&approve_publisher=<?= $r['id'] ?>" class="bg-green-100 text-green-700 px-3 py-1 rounded hover:bg-green-200">Setujui</a>
                                                <a href="?page=validation_users&reject_publisher=<?= $r['id'] ?>" class="bg-red-100 text-red-700 px-3 py-1 rounded hover:bg-red-200">Tolak</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif;
                                                                                                                        break; ?>

                <?php
                case 'users':
                    // --- HALAMAN USER LIST ---
                    $all_users = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC");
                ?>
                    <div class="flex justify-between items-end mb-6">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800">👥 Daftar Pengguna</h2>
                            <p class="text-gray-500 text-sm mt-1">Kelola akun pengguna, penerbit, dan admin.</p>
                        </div>
                        <div class="bg-purple-50 text-purple-700 px-4 py-2 rounded-lg text-sm font-bold border border-purple-100">
                            Total: <?= mysqli_num_rows($all_users) ?> Akun
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-purple-50 border-b border-purple-100 text-purple-900 uppercase text-xs tracking-wider">
                                <tr>
                                    <th class="p-5 font-bold">Identitas User</th>
                                    <th class="p-5 font-bold">Role & Status</th>
                                    <th class="p-5 font-bold">Tanggal Bergabung</th>
                                    <th class="p-5 font-bold text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php if (mysqli_num_rows($all_users) > 0): ?>
                                    <?php while ($u = mysqli_fetch_assoc($all_users)):
                                        $initial = strtoupper(substr($u['name'], 0, 1));
                                        $roleColor = match ($u['role']) {
                                            'ADMIN' => 'bg-red-100 text-red-800 border-red-200',
                                            'PENERBIT' => 'bg-purple-100 text-purple-800 border-purple-200',
                                            default => 'bg-gray-100 text-gray-800 border-gray-200'
                                        };
                                        $avatarColor = match ($u['role']) {
                                            'ADMIN' => 'bg-red-50 text-red-600',
                                            'PENERBIT' => 'bg-purple-50 text-purple-600',
                                            default => 'bg-blue-50 text-blue-600'
                                        };
                                    ?>
                                        <tr class="hover:bg-gray-50 transition duration-150">
                                            <td class="p-5 align-middle">
                                                <div class="flex items-center gap-4">
                                                    <div class="w-10 h-10 <?= $avatarColor ?> rounded-full flex items-center justify-center font-bold text-lg shadow-sm border border-white ring-2 ring-gray-50">
                                                        <?= $initial ?>
                                                    </div>
                                                    <div>
                                                        <div class="font-bold text-gray-800 text-sm"><?= htmlspecialchars($u['name']) ?></div>
                                                        <div class="text-xs text-gray-500"><?= htmlspecialchars($u['email']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="p-5 align-middle">
                                                <span class="px-3 py-1 rounded-full text-xs font-bold border <?= $roleColor ?>">
                                                    <?= $u['role'] ?>
                                                </span>
                                            </td>
                                            <td class="p-5 align-middle text-sm text-gray-600">
                                                <?= date('d M Y', strtotime($u['created_at'])) ?>
                                            </td>
                                            <td class="p-5 align-middle text-right">
                                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                                    <a href="?delete_user=<?= $u['id'] ?>" onclick="return confirm('Yakin hapus user ini? Tindakan ini tidak dapat dibatalkan.')" class="inline-flex items-center gap-1 text-red-600 hover:text-red-800 text-sm font-bold bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg transition">
                                                        <span>🗑️</span> Hapus
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-gray-400 text-xs italic bg-gray-50 px-3 py-1.5 rounded-lg border border-gray-100">Akun Anda</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="p-10 text-center text-gray-400 italic">Tidak ada user ditemukan.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php break; ?>

                <?php
                case 'history':
                    // --- IMPROVISASI QUERY ---
                    // Mengambil data cover, author, type buku, dan email user
                    $query_history = "
                        SELECT h.*, 
                               u.name as user_name, u.email as user_email, u.role as user_role,
                               b.title as book_title, b.cover as book_cover, b.author as book_author, b.type as book_type
                        FROM history h 
                        JOIN users u ON h.user_id = u.id 
                        JOIN books b ON h.book_id = b.id 
                        ORDER BY h.read_at DESC
                    ";
                    $histories = mysqli_query($conn, $query_history);

                    // Helper function sederhana untuk 'Waktu yang lalu' (Time Ago)
                    // Cek if function exists untuk menghindari error redeclare
                    if (!function_exists('time_elapsed_string')) {
                        function time_elapsed_string($datetime, $full = false)
                        {
                            $now = new DateTime;
                            $ago = new DateTime($datetime);
                            $diff = $now->diff($ago);

                            // FIX: Menggunakan variabel lokal untuk minggu agar tidak error di editor
                            $weeks = floor($diff->d / 7);
                            $days = $diff->d - ($weeks * 7);

                            $string = array(
                                'y' => 'tahun',
                                'm' => 'bulan',
                                'w' => 'minggu',
                                'd' => 'hari',
                                'h' => 'jam',
                                'i' => 'menit',
                                's' => 'detik',
                            );

                            // Mapping manual value agar lebih bersih
                            $values = [
                                'y' => $diff->y,
                                'm' => $diff->m,
                                'w' => $weeks,
                                'd' => $days,
                                'h' => $diff->h,
                                'i' => $diff->i,
                                's' => $diff->s,
                            ];

                            foreach ($string as $k => &$v) {
                                if ($values[$k]) {
                                    $v = $values[$k] . ' ' . $v;
                                } else {
                                    unset($string[$k]);
                                }
                            }

                            if (!$full) $string = array_slice($string, 0, 1);
                            return $string ? implode(', ', $string) . ' yang lalu' : 'Baru saja';
                        }
                    }
                ?>
                    <div class="flex justify-between items-end mb-6">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800">⏳ Log Aktivitas Pembaca</h2>
                            <p class="text-gray-500 text-sm mt-1">Pantau aktivitas membaca pengguna secara real-time.</p>
                        </div>
                        <div class="bg-indigo-50 text-indigo-700 px-4 py-2 rounded-lg text-sm font-bold border border-indigo-100">
                            Total: <?= mysqli_num_rows($histories) ?> Aktivitas
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-indigo-50 border-b border-indigo-100 text-indigo-900 uppercase text-xs tracking-wider">
                                <tr>
                                    <th class="p-5 font-bold">Pengguna</th>
                                    <th class="p-5 font-bold">Buku yang Dibaca</th>
                                    <th class="p-5 font-bold">Kategori</th>
                                    <th class="p-5 font-bold text-right">Waktu Akses</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php if (mysqli_num_rows($histories) > 0): ?>
                                    <?php while ($h = mysqli_fetch_assoc($histories)):
                                        // Initials untuk avatar
                                        $initial = strtoupper(substr($h['user_name'], 0, 1));
                                        $bg_avatar = ($h['user_role'] == 'PENERBIT') ? 'bg-purple-100 text-purple-600' : 'bg-blue-100 text-blue-600';
                                    ?>
                                        <tr class="hover:bg-gray-50 transition duration-150">
                                            <!-- KOLOM USER -->
                                            <td class="p-5 align-middle">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 <?= $bg_avatar ?> rounded-full flex items-center justify-center font-bold text-lg shadow-sm border border-white ring-2 ring-gray-50">
                                                        <?= $initial ?>
                                                    </div>
                                                    <div>
                                                        <div class="font-bold text-gray-800 text-sm"><?= htmlspecialchars($h['user_name']) ?></div>
                                                        <div class="text-xs text-gray-400"><?= htmlspecialchars($h['user_email']) ?></div>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- KOLOM BUKU -->
                                            <td class="p-5 align-middle">
                                                <div class="flex gap-4 items-start">
                                                    <!-- Cover Mini -->
                                                    <div class="w-10 h-14 bg-gray-200 rounded shadow-sm overflow-hidden flex-shrink-0">
                                                        <?php
                                                        $coverPath = '../uploads/covers/' . $h['book_cover'];
                                                        if (!empty($h['book_cover']) && file_exists($coverPath)):
                                                        ?>
                                                            <img src="<?= $coverPath ?>" class="w-full h-full object-cover">
                                                        <?php else: ?>
                                                            <div class="w-full h-full flex items-center justify-center text-[10px] text-gray-400 text-center leading-none p-1">No Cover</div>
                                                        <?php endif; ?>
                                                    </div>

                                                    <!-- Info Buku -->
                                                    <div class="max-w-xs">
                                                        <div class="font-bold text-gray-800 text-sm line-clamp-1" title="<?= htmlspecialchars($h['book_title']) ?>">
                                                            <?= htmlspecialchars($h['book_title']) ?>
                                                        </div>
                                                        <div class="text-xs text-gray-500 mt-0.5">
                                                            Karya: <span class="text-gray-700"><?= htmlspecialchars($h['book_author']) ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- KOLOM TIPE -->
                                            <td class="p-5 align-middle">
                                                <?php
                                                $badgeColor = match ($h['book_type']) {
                                                    'BOOK' => 'bg-blue-50 text-blue-700 border-blue-100',
                                                    'JOURNAL' => 'bg-orange-50 text-orange-700 border-orange-100',
                                                    'ARTICLE' => 'bg-green-50 text-green-700 border-green-100',
                                                    default => 'bg-gray-50 text-gray-700'
                                                };
                                                ?>
                                                <span class="px-2.5 py-1 rounded text-xs font-bold border <?= $badgeColor ?>">
                                                    <?= $h['book_type'] ?>
                                                </span>
                                            </td>

                                            <!-- KOLOM WAKTU -->
                                            <td class="p-5 align-middle text-right">
                                                <div class="text-sm font-bold text-gray-700" title="<?= $h['read_at'] ?>">
                                                    <?= time_elapsed_string($h['read_at']) ?>
                                                </div>
                                                <div class="text-xs text-gray-400 mt-1">
                                                    <?= date('d M Y, H:i', strtotime($h['read_at'])) ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="p-10 text-center text-gray-400 italic">
                                            Belum ada aktivitas membaca.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php break; ?>

            <?php endswitch; ?>
        </main>
    </div>
</body>

</html>