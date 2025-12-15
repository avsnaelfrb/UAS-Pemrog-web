<?php
require '../Backend/config.php';

// CEK KHUSUS: Hanya Penerbit
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'PENERBIT') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$error_msg = '';

// --- LOGIKA UPLOAD KARYA (VERSI BARU: SYSTEM STORAGE) ---
if (isset($_POST['upload_karya'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $author = mysqli_real_escape_string($conn, $_POST['author']);
    $year = (int)$_POST['year'];
    $type = $_POST['type'];
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $selected_genres = isset($_POST['genres']) ? $_POST['genres'] : [];

    // 1. Tentukan Folder Tujuan (Naik satu level dari Frontend, lalu masuk uploads)
    $dirBooks = "../uploads/books";
    $dirCovers = "../uploads/covers";

    // 2. Proses Upload Cover (Opsional)
    $coverFilename = null;
    if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadFile($_FILES['cover'], $dirCovers);
        if ($uploadResult) {
            $coverFilename = $uploadResult;
        } else {
            $error_msg = "Gagal mengupload cover. Cek permission folder.";
        }
    }

    // 3. Proses Upload PDF (Wajib)
    $pdfFilename = null;
    if (isset($_FILES['file_book']) && $_FILES['file_book']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadFile($_FILES['file_book'], $dirBooks);
        if ($uploadResult) {
            $pdfFilename = $uploadResult;
        } else {
            $error_msg = "Gagal mengupload file PDF. Pastikan folder uploads/books ada dan writable.";
        }
    } else {
        $error_msg = "File PDF wajib dipilih!";
    }

    // 4. Simpan ke Database jika tidak ada error dan PDF berhasil terupload
    if (!$error_msg && $pdfFilename) {
        // Query Insert: Sekarang kolom cover & file_path menyimpan STRING (nama file), bukan BLOB
        $query = "INSERT INTO books (title, author, description, year, type, cover, file_path, status, uploaded_by) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, 'PENDING', ?)";

        $stmt = mysqli_prepare($conn, $query);

        // sssisssi -> string, string, string, int, string, string (nama file), string (nama file), int
        mysqli_stmt_bind_param($stmt, "sssisssi", $title, $author, $description, $year, $type, $coverFilename, $pdfFilename, $user_id);

        if (mysqli_stmt_execute($stmt)) {
            $new_id = mysqli_insert_id($conn);

            // Simpan Genre
            if (!empty($selected_genres)) {
                foreach ($selected_genres as $gid) {
                    $gid = (int)$gid;
                    mysqli_query($conn, "INSERT INTO book_genres VALUES ($new_id, $gid)");
                }
            }

            $message = "Karya berhasil diupload! Silakan tunggu moderasi Admin.";
        } else {
            $error_msg = "Gagal menyimpan data ke database: " . mysqli_error($conn);
            // Opsional: Hapus file yang sudah terlanjur ke-upload jika DB gagal (Clean up)
            if ($coverFilename) unlink($dirCovers . '/' . $coverFilename);
            if ($pdfFilename) unlink($dirBooks . '/' . $pdfFilename);
        }
    }
}

// Ambil Data User & Genre (Tidak Berubah)
$u_res = mysqli_query($conn, "SELECT * FROM users WHERE id=$user_id");
$current_user = mysqli_fetch_assoc($u_res);
$genres_list = mysqli_query($conn, "SELECT * FROM genres ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Karya - Publisher</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .sidebar-transition {
            transition: transform 0.3s ease-in-out;
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

        /* Scrollbar ungu muda */
        .genre-scroll::-webkit-scrollbar-thumb:hover {
            background: #c084fc;
        }
    </style>
</head>

<body class="bg-gray-50 font-sans text-gray-800">

    <!-- Notifikasi -->
    <?php if ($message): ?>
        <div onclick="this.remove()" class="fixed top-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg z-50 cursor-pointer animate-bounce">
            ✅ <?= $message ?>
        </div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div onclick="this.remove()" class="fixed top-4 right-4 bg-red-600 text-white px-6 py-3 rounded-lg shadow-lg z-50 cursor-pointer animate-bounce">
            ❌ <?= $error_msg ?>
        </div>
    <?php endif; ?>

    <!-- OVERLAY MOBILE -->
    <div id="mobile-overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden backdrop-blur-sm"></div>

    <div class="flex min-h-screen">

        <!-- SIDEBAR (Konsisten dengan Dashboard Penerbit) -->
        <aside id="sidebar" class="w-64 bg-white shadow-xl fixed inset-y-0 left-0 z-40 border-r transform -translate-x-full lg:translate-x-0 sidebar-transition h-full overflow-y-auto">
            <div class="p-6 border-b flex flex-col items-center relative">
                <button onclick="toggleSidebar()" class="absolute top-4 right-4 lg:hidden text-gray-500 hover:text-red-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                <div class="w-16 h-16 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center text-2xl mb-3">✒️</div>
                <h1 class="text-xl font-bold text-purple-900">Publisher</h1>
                <p class="text-xs text-gray-500 mt-1">Halo, <?= htmlspecialchars($current_user['name']) ?></p>
            </div>

            <nav class="p-4 space-y-2">
                <a href="dashboard-publisher.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-purple-50 rounded-lg font-medium transition">
                    <span>📚</span> Katalog
                </a>

                <!-- Menu Upload Aktif -->
                <a href="upload.php" class="flex items-center gap-3 px-4 py-3 bg-purple-50 text-purple-700 rounded-lg font-medium border border-purple-100 shadow-sm">
                    <span>📤</span> Upload Karya
                </a>

                <a href="history.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-purple-50 rounded-lg font-medium transition">
                    <span>🕒</span> Riwayat
                </a>
                <a href="profile.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-purple-50 rounded-lg font-medium transition">
                    <span>⚙️</span> Profil
                </a>
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
                    <button onclick="toggleSidebar()" class="text-gray-700 p-2 hover:bg-gray-100 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path>
                        </svg>
                    </button>
                    <h1 class="font-bold text-purple-900 text-lg">Upload Karya</h1>
                </div>
                <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center text-sm border border-purple-200">✒️</div>
            </div>

            <div class="max-w-4xl mx-auto">
                <!-- Info Header -->
                <div class="mb-8">
                    <h2 class="text-3xl font-bold text-gray-800 mb-2">📤 Upload Karya Baru</h2>
                    <p class="text-gray-500">Kontribusi Anda akan dimoderasi oleh Admin sebelum diterbitkan.</p>
                </div>

                <!-- Form Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

                    <!-- Alert Info -->
                    <div class="bg-purple-50 border-b border-purple-100 px-6 py-4 flex items-start gap-3">
                        <span class="text-purple-600 text-xl">ℹ️</span>
                        <p class="text-sm text-purple-800 leading-relaxed">
                            Pastikan karya Anda orisinal dan tidak mengandung unsur SARA atau konten ilegal.
                            Status awal buku adalah <span class="font-bold">PENDING</span>.
                        </p>
                    </div>

                    <form method="POST" enctype="multipart/form-data" class="p-6 lg:p-8 space-y-6">
                        <input type="hidden" name="upload_karya" value="1">

                        <!-- Judul -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Judul Buku <span class="text-red-500">*</span></label>
                            <input type="text" name="title" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-200 focus:border-purple-400 outline-none transition placeholder-gray-400"
                                placeholder="Masukkan judul lengkap karya Anda...">
                        </div>

                        <!-- Grid: Penulis & Tahun -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Penulis <span class="text-red-500">*</span></label>
                                <input type="text" name="author" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-200 focus:border-purple-400 outline-none transition"
                                    placeholder="Nama penulis...">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Tahun Terbit <span class="text-red-500">*</span></label>
                                <input type="number" name="year" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-200 focus:border-purple-400 outline-none transition"
                                    placeholder="YYYY">
                            </div>
                        </div>

                        <!-- Grid: Tipe & Genre -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="md:col-span-1">
                                <label class="block text-sm font-bold text-gray-700 mb-2">Tipe Dokumen</label>
                                <select name="type" class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white focus:ring-2 focus:ring-purple-200 focus:border-purple-400 outline-none transition cursor-pointer">
                                    <option value="BOOK">📘 Buku</option>
                                    <option value="JOURNAL">📓 Jurnal</option>
                                    <option value="ARTICLE">📰 Artikel</option>
                                </select>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-bold text-gray-700 mb-2">Kategori Genre</label>
                                <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 max-h-32 overflow-y-auto genre-scroll pr-2">
                                        <?php mysqli_data_seek($genres_list, 0);
                                        while ($g = mysqli_fetch_assoc($genres_list)): ?>
                                            <label class="inline-flex items-center space-x-2 cursor-pointer group p-1 hover:bg-white rounded transition">
                                                <input type="checkbox" name="genres[]" value="<?= $g['id'] ?>" class="form-checkbox text-purple-600 rounded focus:ring-purple-500 h-4 w-4 border-gray-300">
                                                <span class="text-sm text-gray-600 group-hover:text-purple-700 font-medium"><?= htmlspecialchars($g['name']) ?></span>
                                            </label>
                                        <?php endwhile; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sinopsis -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Sinopsis / Deskripsi</label>
                            <textarea name="description" class="w-full px-4 py-3 border border-gray-300 rounded-xl h-32 focus:ring-2 focus:ring-purple-200 focus:border-purple-400 outline-none transition placeholder-gray-400 resize-none" placeholder="Tuliskan gambaran singkat tentang karya ini..."></textarea>
                        </div>

                        <!-- Upload Area (Tombol Biru & Modern) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                            <!-- PDF Upload -->
                            <div class="relative group">
                                <label class="block text-sm font-bold text-gray-700 mb-2">File PDF <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input type="file" name="file_book" accept="application/pdf" required
                                        class="block w-full text-sm text-slate-500
                                                  file:mr-4 file:py-2.5 file:px-4
                                                  file:rounded-lg file:border-0
                                                  file:text-sm file:font-bold
                                                  file:bg-blue-50 file:text-blue-700
                                                  hover:file:bg-blue-100
                                                  cursor-pointer border border-gray-300 rounded-xl p-1.5 transition bg-white focus:outline-none focus:ring-2 focus:ring-blue-200">
                                </div>
                                <p class="text-xs text-gray-400 mt-1">Format .pdf saja</p>
                            </div>

                            <!-- Cover Upload -->
                            <div class="relative group">
                                <label class="block text-sm font-bold text-gray-700 mb-2">Cover (Opsional)</label>
                                <div class="relative">
                                    <input type="file" name="cover" accept="image/*"
                                        class="block w-full text-sm text-slate-500
                                                  file:mr-4 file:py-2.5 file:px-4
                                                  file:rounded-lg file:border-0
                                                  file:text-sm file:font-bold
                                                  file:bg-blue-50 file:text-blue-700
                                                  hover:file:bg-blue-100
                                                  cursor-pointer border border-gray-300 rounded-xl p-1.5 transition bg-white focus:outline-none focus:ring-2 focus:ring-blue-200">
                                </div>
                                <p class="text-xs text-gray-400 mt-1">Format .jpg, .png, .jpeg</p>
                            </div>
                        </div>

                        <!-- Submit Button (Ungu) -->
                        <div class="pt-6 border-t border-gray-100 flex justify-end gap-3">
                            <a href="dashboard-publisher.php" class="px-6 py-3 rounded-xl font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 transition">
                                Batal
                            </a>
                            <button type="submit" class="px-8 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl font-bold shadow-lg hover:shadow-xl hover:from-purple-700 hover:to-indigo-700 transition transform hover:-translate-y-0.5 flex items-center gap-2">
                                <span>🚀</span> Request Upload
                            </button>
                        </div>
                    </form>
                </div>
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