<?php
// Debugging Error (PENTING AGAR TIDAK BLANK)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '../Backend/config.php';

// CEK KHUSUS: Hanya Penerbit
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'PENERBIT') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

// --- LOGIKA HAPUS BUKU ---
if (isset($_GET['delete'])) {
    $book_id = (int)$_GET['delete'];

    // Pastikan buku ini milik si penerbit
    $check = mysqli_query($conn, "SELECT id FROM books WHERE id=$book_id AND uploaded_by=$user_id");

    if (!$check) {
        die("Query Error: " . mysqli_error($conn));
    }

    if (mysqli_num_rows($check) > 0) {

        // Hapus File Fisik
        $q_file = mysqli_query($conn, "SELECT cover, file_path FROM books WHERE id=$book_id");
        $row_file = mysqli_fetch_assoc($q_file);

        $dirBooks = "../uploads/books/";
        $dirCovers = "../uploads/covers/";

        // Gunakan fungsi deleteFile jika ada di config, atau unlink manual
        if (!empty($row_file['file_path'])) {
            if (function_exists('deleteFile')) {
                deleteFile($dirBooks . $row_file['file_path']);
            } elseif (file_exists($dirBooks . $row_file['file_path'])) {
                unlink($dirBooks . $row_file['file_path']);
            }
        }

        if (!empty($row_file['cover'])) {
            if (function_exists('deleteFile')) {
                deleteFile($dirCovers . $row_file['cover']);
            } elseif (file_exists($dirCovers . $row_file['cover'])) {
                unlink($dirCovers . $row_file['cover']);
            }
        }

        if (mysqli_query($conn, "DELETE FROM books WHERE id=$book_id")) {
            $message = "Buku berhasil dihapus.";
        } else {
            $message = "Gagal menghapus buku: " . mysqli_error($conn);
        }
    } else {
        $message = "Buku tidak ditemukan atau Anda tidak berhak menghapusnya.";
    }
}

// Query Ambil Buku Milik Penerbit
$sql = "SELECT * FROM books WHERE uploaded_by=$user_id ORDER BY created_at DESC";
$my_books = mysqli_query($conn, $sql);

if (!$my_books) {
    die("Gagal mengambil data buku: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terbitan Saya - Publisher</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .sidebar-transition {
            transition: transform 0.3s ease-in-out;
        }
    </style>
</head>

<body class="bg-gray-50 font-sans text-gray-800">

    <?php if ($message): ?>
        <div onclick="this.remove()" class="fixed top-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg z-50 cursor-pointer animate-bounce">
            ✅ <?= $message ?>
        </div>
    <?php endif; ?>

    <div class="flex min-h-screen">
        <!-- Sidebar Penerbit -->
        <aside id="sidebar" class="w-64 bg-white shadow-xl fixed inset-y-0 left-0 z-40 border-r transform -translate-x-full lg:translate-x-0 transition-transform duration-300">
            <div class="p-6 border-b flex flex-col items-center relative">
                <button onclick="toggleSidebar()" class="absolute top-4 right-4 lg:hidden text-gray-500 hover:text-red-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center text-2xl mb-3">✒️</div>
                <h1 class="text-xl font-bold text-purple-900">Publisher</h1>
                <p class="text-xs text-gray-500 mt-1">Halo, <?= htmlspecialchars($_SESSION['name'] ?? 'User') ?></p>
            </div>

            <nav class="p-4 space-y-2">
                <a href="dashboard-publisher.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-purple-50 hover:text-purple-700 rounded-lg font-medium transition duration-200"><span>📚</span> Katalog</a>
                <a href="my_publications.php" class="flex items-center gap-3 px-4 py-3 bg-purple-50 text-purple-700 rounded-lg font-medium border border-purple-100 shadow-sm"><span>📂</span> Terbitan Saya</a>
                <a href="upload.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-purple-50 hover:text-purple-700 rounded-lg font-medium transition duration-200"><span>📤</span> Upload Karya</a>
                <a href="history.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-purple-50 hover:text-purple-700 rounded-lg font-medium transition duration-200"><span>🕒</span> Riwayat</a>
                <a href="saved_books.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-purple-50 hover:text-purple-700 rounded-lg font-medium transition duration-200"><span>🔖</span> Koleksi</a>
                <a href="profile.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-purple-50 hover:text-purple-700 rounded-lg font-medium transition duration-200"><span>⚙️</span> Profile</a>
                <a href="logout.php" class="flex items-center gap-3 px-4 py-3 text-red-600 hover:bg-red-50 rounded-lg mt-auto pt-4 border-t"><span>🚪</span> Keluar</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 lg:ml-64 p-8 transition-all duration-300">
            <!-- Header Mobile -->
            <div class="lg:hidden flex items-center justify-between bg-white p-4 rounded-xl shadow-sm border mb-6 sticky top-0 z-20">
                <button onclick="toggleSidebar()" class="text-gray-700 p-2 hover:bg-gray-100 rounded-lg"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path>
                    </svg></button>
                <h1 class="font-bold text-purple-900 text-lg">Terbitan Saya</h1>
            </div>

            <div class="max-w-6xl mx-auto">
                <div class="bg-white p-6 rounded-xl shadow-sm border mb-8 flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800 mb-1">📂 Manajemen Publikasi</h2>
                        <p class="text-gray-500 text-sm">Kelola status dan data buku yang telah Anda unggah.</p>
                    </div>
                    <a href="upload.php" class="bg-purple-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-purple-700 transition shadow">
                        + Upload Baru
                    </a>
                </div>

                <?php if (mysqli_num_rows($my_books) > 0): ?>
                    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-gray-50 border-b border-gray-100">
                                    <tr>
                                        <th class="p-4 font-bold text-gray-600 text-sm">Info Buku</th>
                                        <th class="p-4 font-bold text-gray-600 text-sm">Tanggal Upload</th>
                                        <th class="p-4 font-bold text-gray-600 text-sm">Tipe</th>
                                        <th class="p-4 font-bold text-gray-600 text-sm">Status Moderasi</th>
                                        <th class="p-4 font-bold text-gray-600 text-sm text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php while ($mb = mysqli_fetch_assoc($my_books)): ?>
                                        <tr class="hover:bg-purple-50 transition">
                                            <td class="p-4 align-top">
                                                <div class="flex gap-4">
                                                    <div class="w-12 h-16 bg-gray-200 rounded overflow-hidden flex-shrink-0 border border-gray-200">
                                                        <?php
                                                        $coverPathMb = '../uploads/covers/' . ($mb['cover'] ?? '');
                                                        if (!empty($mb['cover']) && file_exists($coverPathMb)):
                                                        ?>
                                                            <img src="<?= $coverPathMb ?>" class="w-full h-full object-cover">
                                                        <?php else: ?>
                                                            <div class="flex items-center justify-center h-full text-[10px] text-gray-400">No Img</div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div>
                                                        <h4 class="font-bold text-gray-800 text-sm line-clamp-2 w-48"><?= htmlspecialchars($mb['title']) ?></h4>
                                                        <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($mb['author']) ?> • <?= $mb['year'] ?></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="p-4 text-sm text-gray-600 align-top">
                                                <?= date('d M Y', strtotime($mb['created_at'])) ?>
                                                <div class="text-xs text-gray-400"><?= date('H:i', strtotime($mb['created_at'])) ?></div>
                                            </td>
                                            <td class="p-4 align-top">
                                                <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs font-bold rounded border uppercase">
                                                    <?= $mb['type'] ?>
                                                </span>
                                            </td>
                                            <td class="p-4 align-top">
                                                <?= getStatusBadge($mb['status']) ?>
                                                <?php if ($mb['status'] == 'REJECTED'): ?>
                                                    <p class="text-xs text-red-500 mt-1 italic">Ditolak.</p>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-4 align-top text-right space-y-2">
                                                <!-- TOMBOL LIHAT -->
                                                <a href="detail.php?id=<?= $mb['id'] ?>" class="text-purple-600 font-bold text-xs hover:underline mr-3">Lihat</a>

                                                <!-- TOMBOL EDIT (Ke Halaman Baru) -->
                                                <a href="edit_book.php?id=<?= $mb['id'] ?>" class="text-blue-600 font-bold text-xs hover:underline mr-3">Edit</a>

                                                <!-- TOMBOL HAPUS -->
                                                <a href="?delete=<?= $mb['id'] ?>" onclick="return confirm('Yakin ingin menghapus publikasi ini secara permanen?')" class="text-red-500 font-bold text-xs hover:underline">Hapus</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center py-20 bg-white rounded-xl border border-dashed border-gray-300 text-center">
                        <div class="text-4xl mb-4">📂</div>
                        <h3 class="text-lg font-medium text-gray-900">Belum ada publikasi</h3>
                        <p class="text-gray-500 text-sm mb-4">Mulai kontribusi Anda dengan mengunggah karya tulis.</p>
                        <a href="upload.php" class="px-6 py-2 bg-purple-600 text-white rounded-lg font-bold hover:bg-purple-700 transition">
                            Upload Sekarang
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

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
    </script>
</body>

</html>