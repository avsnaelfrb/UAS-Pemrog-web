<?php
require_once dirname(__DIR__) . '/Backend/config.php';
// Helper function checkRole jika belum ada di config, kita bypass atau pastikan sesi valid
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'ADMIN') {
    header("Location: login.php");
    exit;
}

$message = '';
$error_msg = '';
$active_page = isset($_GET['page']) ? $_GET['page'] : 'books';
$user_id = $_SESSION['user_id'];

// --- LOGIC HANDLERS ---
if (isset($_POST['update_profile'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $nim = mysqli_real_escape_string($conn, $_POST['nim']);
    $password_baru = $_POST['password'];
    $password_lama = $_POST['old_password'];

    $cek_email = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email' AND id != $user_id");
    if (mysqli_num_rows($cek_email) > 0) {
        $error_msg = "Email sudah digunakan oleh akun lain!";
    } else {
        $password_valid = true;
        $hashed_password_baru = '';

        if (!empty($password_baru)) {
            $q_cek_pass = mysqli_query($conn, "SELECT password FROM users WHERE id = $user_id");
            $data_user_curr = mysqli_fetch_assoc($q_cek_pass);
            if (password_verify($password_lama, $data_user_curr['password'])) {
                $hashed_password_baru = password_hash($password_baru, PASSWORD_DEFAULT);
            } else {
                $password_valid = false;
                $error_msg = "Password lama yang Anda masukkan salah!";
            }
        }

        if ($password_valid) {
            $query = "UPDATE users SET name='$name', email='$email', nim='$nim'";
            if (!empty($hashed_password_baru)) $query .= ", password='$hashed_password_baru'";
            $query .= " WHERE id=$user_id";

            if (mysqli_query($conn, $query)) {
                $_SESSION['name'] = $name;
                $message = "Profil Admin berhasil diperbarui!";
            } else {
                $error_msg = "Gagal memperbarui database.";
            }
        }
    }
}

if (isset($_GET['delete_book'])) {
    $id = (int)$_GET['delete_book'];

    $q_file = mysqli_query($conn, "SELECT cover, file_path FROM books WHERE id=$id");
    if ($row_file = mysqli_fetch_assoc($q_file)) {
        $dirBooks = "../uploads/books/";
        $dirCovers = "../uploads/covers/";

        if (!empty($row_file['file_path']) && file_exists($dirBooks . $row_file['file_path'])) {
            unlink($dirBooks . $row_file['file_path']);
        }
        if (!empty($row_file['cover']) && file_exists($dirCovers . $row_file['cover'])) {
            unlink($dirCovers . $row_file['cover']);
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

$u_admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=$user_id"));
// Helper Function for Time Ago
if (!function_exists('time_elapsed_string')) {
    function time_elapsed_string($datetime, $full = false)
    {
        $tz = new DateTimeZone('Asia/Jakarta');
        $now = new DateTime('now', $tz);
        $ago = new DateTime($datetime, $tz);
        $diff = $now->diff($ago);

        if ($ago > $now) return 'Baru saja';

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

// Helper untuk status badge (Dibungkus function_exists untuk mencegah error redeclare)
if (!function_exists('getStatusBadge')) {
    function getStatusBadge($status)
    {
        switch ($status) {
            case 'APPROVED':
                return '<span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold border border-green-200">Terbit</span>';
            case 'PENDING':
                return '<span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-xs font-bold border border-blue-200">Menunggu</span>';
            case 'REJECTED':
                return '<span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs font-bold border border-red-200">Ditolak</span>';
            default:
                return '<span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-xs font-bold">Draft</span>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - E-Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .active-nav {
            background-color: #eff6ff;
            color: #1e40af;
        }

        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>

<body class="bg-gray-50">

    <!-- Sidebar Desktop -->
    <div class="flex min-h-screen">
        <aside class="w-64 bg-white shadow-xl fixed inset-y-0 left-0 z-40 border-r hidden lg:block">
            <div class="p-6 border-b flex flex-col items-center">
                <div class="mb-2 text-blue-700">
                    <i data-lucide="shield-check" class="w-10 h-10"></i>
                </div>
                <h1 class="font-bold text-gray-800">Admin Panel</h1>
                <p class="text-xs text-gray-500">Moderation Mode</p>
            </div>
            <nav class="p-4 space-y-1">
                <div class="text-xs font-bold text-gray-400 px-4 mt-2">UTAMA</div>
                <a href="?page=books" class="flex justify-between items-center px-4 py-3 rounded-lg hover:bg-gray-50 <?= $active_page == 'books' ? 'active-nav' : '' ?>">
                    <span class="flex items-center">
                        <i data-lucide="library" class="w-4 h-4 mr-2"></i> Kelola Buku
                    </span>
                </a>
                <a href="?page=genres" class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-50 <?= $active_page == 'genres' ? 'active-nav' : '' ?>">
                    <i data-lucide="folder" class="w-4 h-4 mr-2"></i> Kelola Genre
                </a>
                <a href="?page=users" class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-50 <?= $active_page == 'users' ? 'active-nav' : '' ?>">
                    <i data-lucide="users" class="w-4 h-4 mr-2"></i> Kelola User
                </a>
                <a href="?page=history" class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-50 <?= $active_page == 'history' ? 'active-nav' : '' ?>">
                    <i data-lucide="history" class="w-4 h-4 mr-2"></i> Riwayat Baca
                </a>

                <div class="text-xs font-bold text-gray-400 px-4 mt-6">VALIDASI</div>
                <a href="?page=validation_users" class="flex justify-between items-center px-4 py-3 rounded-lg hover:bg-gray-50 <?= $active_page == 'validation_users' ? 'active-nav' : '' ?>">
                    <span class="flex items-center">
                        <i data-lucide="medal" class="w-4 h-4 mr-2"></i> Req. Penerbit
                    </span>
                    <?php if ($notif_req_penerbit > 0): ?><span class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full"><?= $notif_req_penerbit ?></span><?php endif; ?>
                </a>
                <a href="?page=validation_books" class="flex justify-between items-center px-4 py-3 rounded-lg hover:bg-gray-50 <?= $active_page == 'validation_books' ? 'active-nav' : '' ?>">
                    <span class="flex items-center">
                        <i data-lucide="book-open-check" class="w-4 h-4 mr-2"></i> Req. Buku
                    </span>
                    <?php if ($notif_req_buku > 0): ?><span class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full"><?= $notif_req_buku ?></span><?php endif; ?>
                </a>

                <div class="text-xs font-bold text-gray-400 px-4 mt-6">AKUN</div>
                <a href="?page=profile" class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-50 <?= $active_page == 'profile' ? 'active-nav' : '' ?>">
                    <i data-lucide="user-cog" class="w-4 h-4 mr-3"></i> Edit Profil
                </a>
                <a href="logout.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-red-50 text-red-600">
                    <i data-lucide="log-out" class="w-4 h-4 mr-2"></i> Logout
                </a>
            </nav>
        </aside>

        <main class="flex-1 lg:ml-64 p-8">
            <!-- Notifikasi -->
            <?php if ($message): ?>
                <div class="bg-green-100 text-green-700 p-4 rounded mb-6 flex items-center">
                    <i data-lucide="check-circle" class="w-5 h-5 mr-2"></i> <?= $message ?>
                </div>
            <?php endif; ?>
            <?php if ($error_msg): ?>
                <div class="bg-red-100 text-red-700 p-4 rounded mb-6 flex items-center">
                    <i data-lucide="alert-circle" class="w-5 h-5 mr-2"></i> <?= $error_msg ?>
                </div>
            <?php endif; ?>

            <?php switch ($active_page):

                    // === HALAMAN BUKU (UPDATED WITH TRY-CATCH FOR SAFETY) ===
                default:
                case 'books':
                    $query_books = "
                        SELECT b.*, u.name as uploader,
                        (SELECT COUNT(*) FROM book_likes WHERE book_id = b.id) as total_likes
                        FROM books b 
                        LEFT JOIN users u ON b.uploaded_by = u.id 
                        ORDER BY b.created_at DESC
                    ";

                    $books = false;
                    $is_fallback = false;
                    $error_detail = "";

                    try {
                        // Coba eksekusi query normal
                        $books = mysqli_query($conn, $query_books);

                        // Jika return false (error non-exception), lempar exception manual
                        if (!$books) {
                            throw new Exception(mysqli_error($conn));
                        }
                    } catch (Exception $e) {
                        $is_fallback = true;
                        $error_detail = $e->getMessage();

                        // Query Fallback (Tanpa Likes)
                        $query_fallback = "
                            SELECT b.*, u.name as uploader, 
                            0 as total_likes 
                            FROM books b 
                            LEFT JOIN users u ON b.uploaded_by = u.id 
                            ORDER BY b.created_at DESC
                        ";
                        $books = mysqli_query($conn, $query_fallback);
                    }
            ?>
                    <div class="flex justify-between items-end mb-6">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800 flex items-center">
                                <i data-lucide="library" class="w-8 h-8 text-blue-600 mr-2"></i> Semua Koleksi Digital
                            </h2>
                            <p class="text-gray-500 text-sm mt-1">Kelola dan moderasi konten buku dalam sistem.</p>
                        </div>
                        <div class="bg-blue-50 text-blue-700 px-4 py-2 rounded-lg text-sm font-bold border border-blue-100">
                            Total: <?= ($books) ? mysqli_num_rows($books) : 0 ?> Buku
                        </div>
                    </div>

                    <?php if ($is_fallback): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                            <p class="font-bold">Database Warning</p>
                            <p>Fitur "Like" dinonaktifkan sementara karena tabel <code>book_likes</code> belum tersedia.</p>
                            <p class="text-xs mt-2 bg-red-200 p-2 rounded font-mono">Error: <?= htmlspecialchars($error_detail) ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-blue-50 border-b border-blue-100 text-blue-900 uppercase text-xs tracking-wider">
                                <tr>
                                    <th class="p-5 font-bold w-16">Cover</th>
                                    <th class="p-5 font-bold">Informasi Buku</th>
                                    <th class="p-5 font-bold">Penerbit/Uploader</th>
                                    <th class="p-5 font-bold text-center">Interaksi</th> <!-- Kolom Baru -->
                                    <th class="p-5 font-bold">Status</th>
                                    <th class="p-5 font-bold text-right">Moderasi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php if ($books && mysqli_num_rows($books) > 0): ?>
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
                                                    <span class="bg-blue-100 px-2 py-0.5 rounded text-blue-600 font-medium"><?= $b['type'] ?></span>
                                                    <span>•</span>
                                                    <span><?= $b['author'] ?></span>
                                                    <span>•</span>
                                                    <span><?= $b['year'] ?></span>
                                                </div>
                                            </td>
                                            <td class="p-5 align-top">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center text-xs font-bold text-purple-500">
                                                        <?= $b['uploaded_by'] ? strtoupper(substr($b['uploader'], 0, 1)) : '?' ?>
                                                    </div>
                                                    <div class="text-sm text-purple-600">
                                                        <?= $b['uploaded_by'] ? htmlspecialchars($b['uploader']) : '<span class="text-gray-400 italic">System Legacy</span>' ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <!-- KOLOM LIKE BARU -->
                                            <td class="p-5 align-top text-center">
                                                <div class="inline-flex items-center px-3 py-1 bg-red-50 text-red-600 rounded-full border border-red-100 gap-1">
                                                    <i data-lucide="heart" class="w-4 h-4 fill-current"></i>
                                                    <span class="font-bold text-sm"><?= $b['total_likes'] ?></span>
                                                </div>
                                            </td>
                                            <td class="p-5 align-top">
                                                <?= getStatusBadge($b['status']) ?>
                                            </td>
                                            <td class="p-5 align-top text-right">
                                                <a href="?page=books&delete_book=<?= $b['id'] ?>" onclick="return confirm('PERINGATAN: Menghapus buku ini bersifat permanen. Lanjutkan?')" class="inline-flex items-center gap-1 bg-red-50 hover:bg-red-100 text-red-600 text-xs font-bold px-3 py-2 rounded-lg transition border border-red-100">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i> Hapus
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="p-10 text-center text-gray-400 italic">
                                            Belum ada buku dalam database.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php break; ?>

                <?php
                    // === HALAMAN GENRE (TIDAK DIUBAH) ===
                case 'genres':
                    $q_genre = "
                        SELECT g.id, g.name, COUNT(bg.book_id) as total_books
                        FROM genres g
                        LEFT JOIN book_genres bg ON g.id = bg.genre_id
                        GROUP BY g.id, g.name
                        ORDER BY g.name ASC
                    ";
                    $genres = mysqli_query($conn, $q_genre);
                    $total_genres = mysqli_num_rows($genres);
                    mysqli_data_seek($genres, 0);
                ?>
                    <div class="flex justify-between items-end mb-8">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800 flex items-center">
                                <i data-lucide="folder-open" class="w-8 h-8 text-blue-600 mr-2"></i> Kelola Kategori Genre
                            </h2>
                            <p class="text-gray-500 text-sm mt-1">Atur kategori buku agar katalog lebih terstruktur.</p>
                        </div>
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
                        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 sticky top-4">
                            <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2 pb-3 border-b border-gray-100">
                                <span class="bg-blue-100 text-blue-600 w-8 h-8 flex items-center justify-center rounded-lg text-sm">
                                    <i data-lucide="plus" class="w-5 h-5"></i>
                                </span>
                                Tambah Genre Baru
                            </h3>
                            <form method="POST">
                                <div class="mb-4">
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Nama Kategori</label>
                                    <input type="text" name="genre_name" placeholder="Misal: Psikologi, Sejarah..." required
                                        class="w-full border border-gray-300 p-3 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition text-sm bg-gray-50 focus:bg-white">
                                </div>
                                <button type="submit" name="save_genre" class="w-full bg-blue-600 text-white py-3 rounded-lg font-bold hover:bg-blue-700 transition shadow-md shadow-blue-100 flex items-center justify-center gap-2">
                                    <span>Simpan Genre</span>
                                </button>
                            </form>
                        </div>

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
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $g['total_books'] > 0 ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-400' ?>">
                                                        <?= $g['total_books'] ?> Buku
                                                    </span>
                                                </td>
                                                <td class="p-5 text-right">
                                                    <a href="?page=genres&delete_genre=<?= $g['id'] ?>" onclick="return confirm('Yakin hapus genre?')" class="text-gray-400 hover:text-red-600 transition-colors inline-block">
                                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php break; ?>

                <?php
                    // === HALAMAN VALIDASI BUKU (TIDAK DIUBAH) ===
                case 'validation_books':
                    $req_books = mysqli_query($conn, "SELECT b.*, u.name as publisher_name, u.email as publisher_email FROM books b JOIN users u ON b.uploaded_by = u.id WHERE b.status='PENDING' ORDER BY b.created_at DESC");
                ?>
                    <div class="flex justify-between items-end mb-8">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800 flex items-center">
                                <i data-lucide="book-check" class="w-8 h-8 text-blue-600 mr-2"></i> Antrean Validasi Buku
                            </h2>
                            <p class="text-gray-500 text-sm mt-1">Review kiriman buku dari para penerbit sebelum diterbitkan secara luas.</p>
                        </div>
                        <div class="bg-blue-50 text-blue-700 px-4 py-2 rounded-lg text-sm font-bold border border-orange-100 flex items-center gap-2">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                            </span>
                            <?= mysqli_num_rows($req_books) ?> Menunggu Review
                        </div>
                    </div>

                    <?php if (mysqli_num_rows($req_books) == 0): ?>
                        <div class="bg-white p-20 rounded-2xl border border-dashed border-gray-200 flex flex-col items-center justify-center text-center shadow-sm">
                            <div class="mb-4 text-blue-400">
                                <i data-lucide="sparkles" class="w-16 h-16"></i>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800">Semua Beres!</h3>
                            <p class="text-gray-500 max-w-xs mx-auto">Tidak ada buku baru dalam antrean validasi saat ini.</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                            <?php while ($b = mysqli_fetch_assoc($req_books)): ?>
                                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md hover:border-blue-200 transition-all duration-300 group overflow-hidden flex flex-col md:flex-row">
                                    <!-- Cover Section -->
                                    <div class="w-full md:w-44 h-64 md:h-auto bg-gray-100 relative overflow-hidden flex-shrink-0">
                                        <?php
                                        $coverPath = '../uploads/covers/' . $b['cover'];
                                        if (!empty($b['cover']) && file_exists($coverPath)):
                                        ?>
                                            <img src="<?= $coverPath ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                        <?php else: ?>
                                            <div class="w-full h-full flex flex-col items-center justify-center text-gray-400 p-4">
                                                <i data-lucide="image" class="w-8 h-8 mb-2"></i>
                                                <span class="text-[10px] font-bold uppercase tracking-widest">No Cover</span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="absolute top-3 left-3">
                                            <?php
                                            $typeColor = match ($b['type']) {
                                                'BOOK' => 'bg-blue-600',
                                                'JOURNAL' => 'bg-orange-500',
                                                'ARTICLE' => 'bg-green-600',
                                                default => 'bg-gray-600'
                                            };
                                            ?>
                                            <span class="<?= $typeColor ?> text-white text-[10px] font-black px-2 py-1 rounded shadow-lg uppercase"><?= $b['type'] ?></span>
                                        </div>
                                    </div>

                                    <!-- Content Section -->
                                    <div class="p-6 flex flex-col justify-between flex-1">
                                        <div>
                                            <div class="flex justify-between items-start mb-2">
                                                <span class="text-[10px] font-bold text-blue-500 uppercase tracking-widest flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    <?= time_elapsed_string($b['created_at']) ?>
                                                </span>
                                            </div>
                                            <h3 class="font-bold text-xl text-gray-800 mb-1 leading-tight group-hover:text-blue-600 transition-colors line-clamp-2" title="<?= htmlspecialchars($b['title']) ?>">
                                                <?= htmlspecialchars($b['title']) ?>
                                            </h3>
                                            <p class="text-sm text-gray-500 font-medium mb-4 italic">Oleh <?= htmlspecialchars($b['author']) ?> (<?= $b['year'] ?>)</p>

                                            <div class="bg-gray-50 p-3 rounded-xl border border-gray-100 mb-6">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center text-xs font-bold text-blue-600 border border-blue-100 shadow-sm">
                                                        <?= strtoupper(substr($b['publisher_name'], 0, 1)) ?>
                                                    </div>
                                                    <div class="overflow-hidden">
                                                        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-tighter">Pengirim</div>
                                                        <div class="text-xs font-bold text-gray-700 truncate" title="<?= htmlspecialchars($b['publisher_email']) ?>">
                                                            <?= htmlspecialchars($b['publisher_name']) ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Action Buttons -->
                                        <div class="flex flex-wrap gap-2">
                                            <a href="read.php?id=<?= $b['id'] ?>" target="_blank" class="flex-1 inline-flex items-center justify-center gap-2 bg-white border border-gray-200 text-gray-700 px-3 py-2.5 rounded-xl text-xs font-bold hover:bg-gray-50 hover:border-blue-300 transition-all shadow-sm">
                                                <i data-lucide="eye" class="w-4 h-4"></i> Pratinjau
                                            </a>
                                            <a href="?page=validation_books&approve_book=<?= $b['id'] ?>" class="flex-1 inline-flex items-center justify-center gap-2 bg-blue-500 text-white px-3 py-2.5 rounded-xl text-xs font-bold hover:bg-blue-600 hover:scale-[1.02] active:scale-95 transition-all shadow-md">
                                                <i data-lucide="check" class="w-4 h-4"></i> Terbit
                                            </a>
                                            <a href="?page=validation_books&reject_book=<?= $b['id'] ?>" onclick="return confirm('Tolak buku ini?')" class="inline-flex items-center justify-center w-11 h-11 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 hover:text-red-700 transition-all border border-red-100">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php endif;
                    break; ?>

                <?php
                    // === HALAMAN VALIDASI USER (TIDAK DIUBAH) ===
                case 'validation_users':
                    $req_users = mysqli_query($conn, "SELECT * FROM users WHERE request_penerbit='1' ORDER BY created_at ASC");
                ?>
                    <div class="flex justify-between items-end mb-8">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800 flex items-center">
                                <i data-lucide="medal" class="w-8 h-8 text-blue-600 mr-2"></i> Validasi Pengajuan Penerbit
                            </h2>
                            <p class="text-gray-500 text-sm mt-1">Tinjau pengguna yang ingin menjadi kontributor penerbit.</p>
                        </div>
                        <div class="bg-blue-50 text-blue-700 px-4 py-2 rounded-lg text-sm font-bold border border-blue-100 flex items-center gap-2">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                            </span>
                            <?= mysqli_num_rows($req_users) ?> Permintaan
                        </div>
                    </div>

                    <?php if (mysqli_num_rows($req_users) == 0): ?>
                        <div class="bg-white p-20 rounded-2xl border border-dashed border-gray-200 flex flex-col items-center justify-center text-center shadow-sm">
                            <div class="mb-4 text-green-500">
                                <i data-lucide="check-circle-2" class="w-16 h-16"></i>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800">Semua Permintaan Tertangani</h3>
                            <p class="text-gray-500 max-w-xs mx-auto">Saat ini tidak ada user yang mengajukan diri sebagai penerbit.</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                            <?php while ($r = mysqli_fetch_assoc($req_users)):
                                $initial = strtoupper(substr($r['name'], 0, 1));
                            ?>
                                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col items-center text-center hover:shadow-md transition-shadow relative overflow-hidden group">
                                    <div class="absolute top-0 left-0 w-full h-1 bg-blue-400"></div>

                                    <div class="w-20 h-20 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center font-bold text-3xl mb-4 shadow-sm border border-blue-100 group-hover:scale-110 transition-transform">
                                        <?= $initial ?>
                                    </div>

                                    <h3 class="font-bold text-lg text-gray-800 mb-1"><?= htmlspecialchars($r['name']) ?></h3>
                                    <p class="text-gray-500 text-sm mb-4 flex items-center justify-center gap-1">
                                        <i data-lucide="mail" class="w-3 h-3"></i> <?= htmlspecialchars($r['email']) ?>
                                    </p>

                                    <div class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-6 bg-gray-50 px-3 py-1 rounded-full border border-gray-100">
                                        Member Sejak <?= date('M Y', strtotime($r['created_at'])) ?>
                                    </div>

                                    <div class="flex gap-2 w-full mt-auto">
                                        <a href="?page=validation_users&approve_publisher=<?= $r['id'] ?>" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2.5 rounded-lg text-sm font-bold transition flex items-center justify-center gap-2 shadow-sm">
                                            <i data-lucide="check" class="w-4 h-4"></i> Setujui
                                        </a>
                                        <a href="?page=validation_users&reject_publisher=<?= $r['id'] ?>" onclick="return confirm('Tolak pengajuan ini?')" class="flex-1 bg-white border border-red-200 text-red-600 hover:bg-red-50 py-2.5 rounded-lg text-sm font-bold transition flex items-center justify-center gap-2">
                                            <i data-lucide="x" class="w-4 h-4"></i> Tolak
                                        </a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php endif;
                    break; ?>

                <?php
                case 'users':
                    // === HALAMAN USER (TIDAK DIUBAH) ===
                    $all_users = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC");
                ?>
                    <div class="flex justify-between items-end mb-6">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800 flex items-center">
                                <i data-lucide="users" class="w-8 h-8 text-purple-600 mr-2"></i> Daftar Pengguna
                            </h2>
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
                                            default => 'bg-blue-100 text-blue-800 border-blue-200'
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
                                                    <a href="?delete_user=<?= $u['id'] ?>" onclick="return confirm('Yakin hapus user ini?')" class="inline-flex items-center gap-1 text-red-600 hover:text-red-800 text-sm font-bold bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg transition">
                                                        <i data-lucide="trash-2" class="w-4 h-4"></i> Hapus
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php break; ?>

                <?php
                case 'history':
                    // === HALAMAN HISTORY (TIDAK DIUBAH) ===
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
                ?>
                    <div class="flex justify-between items-end mb-6">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800 flex items-center">
                                <i data-lucide="history" class="w-8 h-8 text-indigo-600 mr-2"></i> Log Aktivitas Pembaca
                            </h2>
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
                                        $initial = strtoupper(substr($h['user_name'], 0, 1));
                                        $bg_avatar = ($h['user_role'] == 'PENERBIT') ? 'bg-purple-100 text-purple-600' : 'bg-blue-100 text-blue-600';
                                    ?>
                                        <tr class="hover:bg-gray-50 transition duration-150">
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
                                            <td class="p-5 align-middle">
                                                <div class="flex gap-4 items-start">
                                                    <div class="w-10 h-14 bg-gray-200 rounded shadow-sm overflow-hidden flex-shrink-0">
                                                        <?php
                                                        $coverPath = '../uploads/covers/' . $h['book_cover'];
                                                        if (!empty($h['book_cover']) && file_exists($coverPath)):
                                                        ?>
                                                            <img src="<?= $coverPath ?>" class="w-full h-full object-cover">
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="max-w-xs">
                                                        <div class="font-bold text-gray-800 text-sm line-clamp-1"><?= htmlspecialchars($h['book_title']) ?></div>
                                                        <div class="text-xs text-gray-500 mt-0.5">Karya: <?= htmlspecialchars($h['book_author']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="p-5 align-middle">
                                                <span class="px-2.5 py-1 rounded text-xs font-bold border bg-blue-50 text-blue-700 border-blue-100">
                                                    <?= $h['book_type'] ?>
                                                </span>
                                            </td>
                                            <td class="p-5 align-middle text-right">
                                                <div class="text-sm font-bold text-gray-700"><?= time_elapsed_string($h['read_at']) ?></div>
                                                <div class="text-xs text-gray-400"><?= date('H:i', strtotime($h['read_at'])) ?></div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php break; ?>
                <?php

                case 'profile': ?>
                    <!-- === INTERNAL PAGE: EDIT PROFILE ADMIN === -->
                    <div class="max-w-4xl mx-auto">

                        <div class="flex flex-col md:flex-row items-center gap-8 mb-12 bg-white p-8 rounded-[2.5rem] border border-gray-100 shadow-sm">
                            <div class="w-32 h-32 bg-gradient-to-tr from-blue-700 to-indigo-900 rounded-[2rem] flex items-center justify-center text-white shadow-xl">
                                <i data-lucide="shield-check" class="w-16 h-16"></i>
                            </div>
                            <div class="text-center md:text-left">
                                <span class="px-3 py-1 bg-slate-100 text-blue-700 text-[10px] font-bold uppercase tracking-widest rounded-full border border-slate-200 mb-3 inline-block">System Administrator</span>
                                <h3 class="text-3xl font-black text-gray-900 mb-1"><?= htmlspecialchars($u_admin['name']) ?></h3>
                                <p class="text-gray-500 font-medium"><?= htmlspecialchars($u_admin['email']) ?></p>
                            </div>
                        </div>

                        <form method="POST" class="space-y-8">
                            <div class="bg-white rounded-[2rem] border border-gray-100 p-6 lg:p-10 shadow-sm">
                                <div class="flex items-center gap-4 mb-8">
                                    <div class="w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600">
                                        <i data-lucide="user" class="w-6 h-6"></i>
                                    </div>
                                    <h4 class="text-xl font-black text-gray-900">Informasi Pribadi</h4>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Nama Lengkap</label>
                                        <input type="text" name="name" value="<?= htmlspecialchars($u_admin['name']) ?>" required class="w-full px-4 py-3.5 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-4 focus:ring-slate-100 outline-none transition-all font-medium">
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">ID / NIP Admin</label>
                                        <input type="text" name="nim" value="<?= htmlspecialchars($u_admin['nim']) ?>" required class="w-full px-4 py-3.5 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-4 focus:ring-slate-100 outline-none transition-all font-medium">
                                    </div>
                                    <div class="md:col-span-2 space-y-2">
                                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Email</label>
                                        <input type="email" name="email" value="<?= htmlspecialchars($u_admin['email']) ?>" required class="w-full px-4 py-3.5 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-4 focus:ring-slate-100 outline-none transition-all font-medium">
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white rounded-[2rem] border border-gray-100 p-6 lg:p-10 shadow-sm">
                                <div class="flex items-center gap-4 mb-8">
                                    <div class="w-12 h-12 bg-red-50 rounded-2xl flex items-center justify-center text-red-500">
                                        <i data-lucide="lock" class="w-6 h-6"></i>
                                    </div>
                                    <h4 class="text-xl font-black text-gray-900">Keamanan Akun</h4>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Password Lama</label>
                                        <input type="password" name="old_password" placeholder="••••••••" class="w-full px-4 py-3.5 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-4 focus:ring-red-50 outline-none transition-all font-medium">
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Password Baru</label>
                                        <input type="password" name="password" placeholder="Min. 6 Karakter" class="w-full px-4 py-3.5 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-4 focus:ring-green-50 outline-none transition-all font-medium">
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-end gap-4 pb-10">
                                <button type="submit" name="update_profile" class="px-10 py-4 bg-slate-900 text-white rounded-2xl font-black transition-all transform hover:scale-105 shadow-xl flex items-center gap-3">
                                    <i data-lucide="save" class="w-5 h-5"></i> Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                    <?php break; ?>
            <?php endswitch; ?>
        </main>
    </div>
    <!-- Initialize Lucide Icons -->
    <script>
        lucide.createIcons();
    </script>
</body>

</html>