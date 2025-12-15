<?php
require '../Backend/config.php';

// Cek Sesi & Role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'ADMIN') {
    header("Location: login.php");
    exit;
}

$message = '';
$error_msg = '';
$active_page = isset($_GET['page']) ? $_GET['page'] : 'books';

// --- LOGIKA MODERASI BUKU (Hapus Konten Tidak Pantas) ---
// UPDATED: Menghapus file fisik juga agar storage tidak penuh
if (isset($_GET['delete_book'])) {
    $id = (int)$_GET['delete_book'];

    // 1. Ambil nama file sebelum dihapus datanya
    $q_file = mysqli_query($conn, "SELECT cover, file_path FROM books WHERE id=$id");
    if ($row_file = mysqli_fetch_assoc($q_file)) {
        // 2. Hapus File Fisik
        $dirBooks = "../uploads/books/";
        $dirCovers = "../uploads/covers/";

        if (!empty($row_file['file_path'])) {
            deleteFile($dirBooks . $row_file['file_path']);
        }
        if (!empty($row_file['cover'])) {
            deleteFile($dirCovers . $row_file['cover']);
        }
    }

    // 3. Hapus Data dari Database
    if (mysqli_query($conn, "DELETE FROM books WHERE id=$id")) {
        header("Location: dashboard-admin.php?page=books&msg=deleted");
        exit;
    } else {
        $error_msg = "Gagal menghapus buku.";
    }
}

// --- LOGIKA APPROVAL PENERBIT ---
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

// --- LOGIKA APPROVAL BUKU (Pending -> Approved) ---
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

// --- LOGIKA HAPUS USER ---
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

// --- LOGIKA CRUD GENRE ---
if (isset($_POST['save_genre'])) {
    $name = mysqli_real_escape_string($conn, $_POST['genre_name']);
    if (!empty($name)) {
        mysqli_query($conn, "INSERT INTO genres (name) VALUES ('$name')");
        $message = "Genre ditambahkan.";
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
                    <div class="mb-6">
                        <h2 class="font-bold text-2xl text-gray-800">📚 Semua Koleksi Digital</h2>
                        <p class="text-gray-500 text-sm">Anda hanya dapat menghapus buku (moderasi), tidak dapat mengupload atau mengedit.</p>
                    </div>

                    <div class="bg-white rounded-xl shadow overflow-hidden">
                        <table class="w-full text-left">
                            <thead class="bg-gray-100 border-b">
                                <tr>
                                    <th class="p-4 w-16">Cover</th>
                                    <th class="p-4">Info Buku</th>
                                    <th class="p-4">Penerbit/Uploader</th>
                                    <th class="p-4">Status</th>
                                    <th class="p-4 text-right">Moderasi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php while ($b = mysqli_fetch_assoc($books)): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="p-4 align-top">
                                            <div class="w-12 h-16 bg-gray-200 rounded overflow-hidden shadow-sm">
                                                <?php
                                                $coverPath = '../uploads/covers/' . $b['cover'];
                                                if (!empty($b['cover']) && file_exists($coverPath)):
                                                ?>
                                                    <img src="<?= $coverPath ?>" class="w-full h-full object-cover">
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="p-4 align-top">
                                            <div class="font-bold text-gray-800"><?= htmlspecialchars($b['title']) ?></div>
                                            <div class="text-xs text-gray-500"><?= $b['author'] ?> • <?= $b['year'] ?></div>
                                        </td>
                                        <td class="p-4 align-top text-sm">
                                            <?= $b['uploaded_by'] ? htmlspecialchars($b['uploader']) : '<span class="text-gray-400 italic">System Legacy</span>' ?>
                                        </td>
                                        <td class="p-4 align-top">
                                            <?= getStatusBadge($b['status']) ?>
                                        </td>
                                        <td class="p-4 align-top text-right">
                                            <a href="?page=books&delete_book=<?= $b['id'] ?>" onclick="return confirm('PERINGATAN: Menghapus buku ini bersifat permanen. Lanjutkan?')" class="bg-red-100 hover:bg-red-200 text-red-700 text-xs font-bold px-3 py-1.5 rounded transition">
                                                🗑️ Hapus Konten
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php break; ?>

                <?php
                    // === HALAMAN GENRE ===
                case 'genres':
                    $genres = mysqli_query($conn, "SELECT * FROM genres ORDER BY name");
                ?>
                    <h2 class="text-2xl font-bold mb-4">📂 Kelola Genre</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-white p-6 rounded-xl shadow h-fit">
                            <form method="POST">
                                <label class="block text-sm font-bold mb-2">Tambah Genre Baru</label>
                                <input type="text" name="genre_name" required class="w-full border p-2 rounded mb-3">
                                <button type="submit" name="save_genre" class="w-full bg-blue-600 text-white py-2 rounded font-bold hover:bg-blue-700">Simpan</button>
                            </form>
                        </div>
                        <div class="md:col-span-2 bg-white rounded-xl shadow overflow-hidden">
                            <table class="w-full text-left">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="p-4">Nama Genre</th>
                                        <th class="p-4 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($g = mysqli_fetch_assoc($genres)): ?>
                                        <tr class="border-b">
                                            <td class="p-4"><?= htmlspecialchars($g['name']) ?></td>
                                            <td class="p-4 text-right">
                                                <a href="?page=genres&delete_genre=<?= $g['id'] ?>" onclick="return confirm('Hapus?')" class="text-red-600 text-sm font-bold">Hapus</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
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
                    // --- HALAMAN USER LIST (Ditambahkan manual karena snippet tidak ada) ---
                    $all_users = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC");
                ?>
                    <h2 class="text-2xl font-bold mb-4">👥 Daftar Semua User</h2>
                    <div class="bg-white rounded-xl shadow overflow-hidden">
                        <table class="w-full text-left">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="p-4">Nama</th>
                                    <th class="p-4">Email</th>
                                    <th class="p-4">Role</th>
                                    <th class="p-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($u = mysqli_fetch_assoc($all_users)): ?>
                                    <tr class="border-b">
                                        <td class="p-4"><?= htmlspecialchars($u['name']) ?></td>
                                        <td class="p-4"><?= htmlspecialchars($u['email']) ?></td>
                                        <td class="p-4">
                                            <span class="px-2 py-1 rounded text-xs font-bold 
                                                <?= $u['role'] == 'ADMIN' ? 'bg-red-100 text-red-800' : ($u['role'] == 'PENERBIT' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800') ?>">
                                                <?= $u['role'] ?>
                                            </span>
                                        </td>
                                        <td class="p-4 text-right">
                                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                                <a href="?delete_user=<?= $u['id'] ?>" onclick="return confirm('Yakin hapus user ini?')" class="text-red-600 font-bold hover:underline">Hapus</a>
                                            <?php else: ?>
                                                <span class="text-gray-400 italic">Akun Sendiri</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php break; ?>

                <?php
                case 'history':
                    $histories = mysqli_query($conn, "SELECT h.*, u.name, b.title FROM history h JOIN users u ON h.user_id=u.id JOIN books b ON h.book_id=b.id ORDER BY h.read_at DESC");
                ?>
                    <h2 class="text-2xl font-bold mb-4">⏳ Riwayat Baca User</h2>
                    <div class="bg-white rounded-xl shadow overflow-hidden">
                        <table class="w-full text-left">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="p-4">User</th>
                                    <th class="p-4">Buku</th>
                                    <th class="p-4">Waktu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($h = mysqli_fetch_assoc($histories)): ?>
                                    <tr class="border-b">
                                        <td class="p-4"><?= htmlspecialchars($h['name']) ?></td>
                                        <td class="p-4"><?= htmlspecialchars($h['title']) ?></td>
                                        <td class="p-4 text-gray-500 text-sm"><?= $h['read_at'] ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php break; ?>

            <?php endswitch; ?>
        </main>
    </div>
</body>

</html>