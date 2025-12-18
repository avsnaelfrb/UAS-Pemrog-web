<?php
// --- BARIS DEBUGGING (Hapus nanti jika sudah fix) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ----------------------------------------------------

require '../Backend/config.php';

// CEK KHUSUS: Hanya Penerbit
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'PENERBIT') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$error_msg = '';

// --- LOGIKA UPLOAD KARYA ---
if (isset($_POST['upload_karya'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $author = mysqli_real_escape_string($conn, $_POST['author']);
    $year = (int)$_POST['year'];
    $type = $_POST['type']; // BOOK, JOURNAL, ARTICLE
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $selected_genres = isset($_POST['genres']) ? $_POST['genres'] : [];

    // Init Variabel (Penting agar tidak undefined)
    $pdfFilename = null;
    $articleLink = null;
    $coverFilename = null;

    // 1. Validasi Input Berdasarkan Tipe
    if ($type == 'ARTICLE') {
        if (!empty($_POST['link'])) {
            $articleLink = mysqli_real_escape_string($conn, $_POST['link']);
        } else {
            $error_msg = "Link Artikel wajib diisi!";
        }
    } else {
        // Buku/Jurnal wajib PDF
        $dirBooks = "../uploads/books";
        if (isset($_FILES['file_book']) && $_FILES['file_book']['error'] === UPLOAD_ERR_OK) {
            // Cek apakah fungsi uploadFile ada
            if (!function_exists('uploadFile')) {
                die("FATAL ERROR: Fungsi uploadFile() tidak ditemukan. Update file Backend/config.php Anda!");
            }
            $uploadResult = uploadFile($_FILES['file_book'], $dirBooks);
            if ($uploadResult) {
                $pdfFilename = $uploadResult;
            } else {
                $error_msg = "Gagal mengupload file PDF ke server.";
            }
        } else {
            $error_msg = "File PDF wajib dipilih untuk Buku/Jurnal!";
        }
    }

    // 2. Upload Cover (Opsional)
    $dirCovers = "../uploads/covers";
    if (!$error_msg && isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
        if (!function_exists('uploadFile')) {
            die("FATAL ERROR: Fungsi uploadFile() tidak ditemukan. Update file Backend/config.php Anda!");
        }
        $uploadResult = uploadFile($_FILES['cover'], $dirCovers);
        if ($uploadResult) $coverFilename = $uploadResult;
    }

    // 3. Simpan ke Database
    if (!$error_msg) {
        // Debugging Query
        $query = "INSERT INTO books (title, author, description, year, type, cover, file_path, link, status, uploaded_by) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'PENDING', ?)";

        $stmt = mysqli_prepare($conn, $query);

        if (!$stmt) {
            // Tampilkan error MySQL jika prepare gagal
            die("DATABASE ERROR (Prepare): " . mysqli_error($conn));
        }

        // sssissssi -> string, string, string, int, string, string, string, string, int
        mysqli_stmt_bind_param($stmt, "sssissssi", $title, $author, $description, $year, $type, $coverFilename, $pdfFilename, $articleLink, $user_id);

        if (mysqli_stmt_execute($stmt)) {
            $new_id = mysqli_insert_id($conn);

            // Simpan Genre
            if (!empty($selected_genres)) {
                foreach ($selected_genres as $gid) {
                    $gid = (int)$gid;
                    mysqli_query($conn, "INSERT INTO book_genres VALUES ($new_id, $gid)");
                }
            }
            $message = "Karya berhasil diupload! Tunggu moderasi Admin.";
        } else {
            // Tampilkan error jika execute gagal
            $error_msg = "DATABASE ERROR (Execute): " . mysqli_stmt_error($stmt);

            // Hapus file jika gagal DB agar tidak nyampah
            if ($coverFilename) @unlink($dirCovers . '/' . $coverFilename);
            if ($pdfFilename) @unlink($dirBooks . '/' . $pdfFilename);
        }
        mysqli_stmt_close($stmt);
    }
}

// Data User & Genre
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

        .genre-scroll::-webkit-scrollbar-thumb:hover {
            background: #c084fc;
        }
    </style>
</head>

<body class="bg-gray-50 font-sans text-gray-800">

    <?php if ($message): ?>
        <div onclick="this.remove()" class="fixed top-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg z-50 cursor-pointer animate-bounce">
            ✅ <?= $message ?>
        </div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div onclick="this.remove()" class="fixed top-4 right-4 bg-red-600 text-white px-6 py-3 rounded-lg shadow-lg z-50 cursor-pointer">
            ❌ <?= $error_msg ?>
        </div>
    <?php endif; ?>

    <div id="mobile-overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden backdrop-blur-sm"></div>

    <div class="flex min-h-screen">
        <!-- SIDEBAR -->
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
                <a href="dashboard-publisher.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-purple-50 hover:text-purple-700 rounded-lg font-medium transition duration-200"><span>📚</span> Katalog</a>
                <a href="my_publications.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-purple-50 hover:text-purple-700 rounded-lg font-medium transition duration-200"><span>📂</span> Terbitan Saya</a>
                <a href="upload.php" class="flex items-center gap-3 px-4 py-3 bg-purple-50 text-purple-700 rounded-lg font-medium border border-purple-100 shadow-sm"><span>📤</span> Upload Karya</a>
                <a href="history.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-purple-50 hover:text-purple-700 rounded-lg font-medium transition"><span>🕒</span> Riwayat</a>
                <a href="saved_books.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-purple-50 hover:text-purple-700 rounded-lg font-medium transition duration-200"><span>🔖</span> Koleksi</a>
                <a href="profile.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-purple-50 hover:text-purple-700 rounded-lg font-medium transition"><span>⚙️</span> Profile</a>
                <a href="logout.php" class="flex items-center gap-3 px-4 py-3 text-red-600 hover:bg-red-50 rounded-lg mt-auto pt-4 border-t"><span>🚪</span> Keluar</a>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 lg:ml-64 p-4 lg:p-8 transition-all duration-300">
            <div class="lg:hidden flex items-center justify-between bg-white p-4 rounded-xl shadow-sm border mb-6 sticky top-0 z-20">
                <div class="flex items-center gap-3">
                    <button onclick="toggleSidebar()" class="text-gray-700 p-2 hover:bg-gray-100 rounded-lg"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path>
                        </svg></button>
                    <h1 class="font-bold text-purple-900 text-lg">Upload Karya</h1>
                </div>
            </div>

            <div class="max-w-4xl mx-auto">
                <div class="mb-8">
                    <h2 class="text-3xl font-bold text-gray-800 mb-2">📤 Upload Karya Baru</h2>
                    <p class="text-gray-500">Pilih tipe dokumen yang sesuai: Buku, Jurnal, atau Artikel.</p>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="bg-purple-50 border-b border-purple-100 px-6 py-4 flex items-start gap-3">
                        <span class="text-purple-600 text-xl">ℹ️</span>
                        <p class="text-sm text-purple-800 leading-relaxed">
                            <b>Buku & Jurnal:</b> Wajib upload file PDF.<br>
                            <b>Artikel:</b> Cukup masukkan Link URL artikel.
                        </p>
                    </div>

                    <form method="POST" enctype="multipart/form-data" class="p-6 lg:p-8 space-y-6">
                        <input type="hidden" name="upload_karya" value="1">

                        <!-- Judul -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Judul Karya <span class="text-red-500">*</span></label>
                            <input type="text" name="title" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-200 outline-none transition" placeholder="Judul lengkap...">
                        </div>

                        <!-- Penulis & Tahun -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Penulis <span class="text-red-500">*</span></label>
                                <input type="text" name="author" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-200 outline-none" placeholder="Nama penulis...">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Tahun Terbit <span class="text-red-500">*</span></label>
                                <input type="number" name="year" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-200 outline-none" placeholder="YYYY">
                            </div>
                        </div>

                        <!-- Tipe & Genre -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="md:col-span-1">
                                <label class="block text-sm font-bold text-gray-700 mb-2">Tipe Dokumen <span class="text-red-500">*</span></label>
                                <select name="type" id="docType" class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white focus:ring-2 focus:ring-purple-200 outline-none cursor-pointer">
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
                            <textarea name="description" class="w-full px-4 py-3 border border-gray-300 rounded-xl h-32 focus:ring-2 focus:ring-purple-200 outline-none resize-none" placeholder="Deskripsi singkat..."></textarea>
                        </div>

                        <!-- DYNAMIC INPUT SECTION -->
                        <div class="bg-gray-50 p-6 rounded-xl border border-gray-200 transition-all duration-300">
                            <!-- 1. Input File PDF (Default) -->
                            <div id="inputPDF">
                                <label class="block text-sm font-bold text-gray-700 mb-2">Upload File PDF <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input type="file" name="file_book" id="fileBookInput" accept="application/pdf"
                                        class="block w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer border border-gray-300 rounded-xl p-1.5 bg-white focus:outline-none focus:ring-2 focus:ring-blue-200">
                                </div>
                                <p class="text-xs text-gray-400 mt-1">Format .pdf untuk Buku atau Jurnal</p>
                            </div>

                            <!-- 2. Input Link Artikel (Hidden by default) -->
                            <div id="inputLink" class="hidden">
                                <label class="block text-sm font-bold text-gray-700 mb-2">Link Artikel <span class="text-red-500">*</span></label>
                                <input type="url" name="link" id="linkInput"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-200 focus:border-green-400 outline-none transition placeholder-gray-400"
                                    placeholder="https://contoh-website.com/artikel-anda">
                                <p class="text-xs text-gray-400 mt-1">Masukkan URL lengkap ke artikel sumber</p>
                            </div>
                        </div>

                        <!-- Cover Upload -->
                        <div class="relative group mt-4">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Cover (Opsional)</label>
                            <input type="file" name="cover" accept="image/*"
                                class="block w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer border border-gray-300 rounded-xl p-1.5 bg-white focus:outline-none focus:ring-2 focus:ring-blue-200">
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-6 border-t border-gray-100 flex justify-end gap-3">
                            <a href="dashboard-publisher.php" class="px-6 py-3 rounded-xl font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 transition">Batal</a>
                            <button type="submit" class="px-8 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl font-bold shadow-lg hover:shadow-xl hover:from-purple-700 hover:to-indigo-700 transition transform hover:-translate-y-0.5 flex items-center gap-2">
                                <span>🚀</span> Upload Karya
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        const docType = document.getElementById('docType');
        const inputPDF = document.getElementById('inputPDF');
        const inputLink = document.getElementById('inputLink');
        const fileBookInput = document.getElementById('fileBookInput');
        const linkInput = document.getElementById('linkInput');

        function toggleInput() {
            if (docType.value === 'ARTICLE') {
                inputPDF.classList.add('hidden');
                inputLink.classList.remove('hidden');
                fileBookInput.required = false;
                linkInput.required = true;
            } else {
                inputPDF.classList.remove('hidden');
                inputLink.classList.add('hidden');
                fileBookInput.required = true;
                linkInput.required = false;
            }
        }

        docType.addEventListener('change', toggleInput);
        toggleInput();

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