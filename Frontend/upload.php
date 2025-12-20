<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once dirname(__DIR__) . '/Backend/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'PENERBIT') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$error_msg = '';

if (isset($_POST['upload_karya'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $author = mysqli_real_escape_string($conn, $_POST['author']);
    $year = (int)$_POST['year'];
    $type = $_POST['type'];
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $selected_genres = isset($_POST['genres']) ? $_POST['genres'] : [];

    $pdfFilename = null;
    $articleLink = null;
    $coverFilename = null;

    if ($type == 'ARTICLE') {
        if (!empty($_POST['link'])) {
            $articleLink = mysqli_real_escape_string($conn, $_POST['link']);
        } else {
            $error_msg = "Link Artikel wajib diisi!";
        }
    } else {
        $dirBooks = "../uploads/books";
        if (isset($_FILES['file_book']) && $_FILES['file_book']['error'] === UPLOAD_ERR_OK) {
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

    $dirCovers = "../uploads/covers";
    if (!$error_msg && isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
        if (!function_exists('uploadFile')) {
            die("FATAL ERROR: Fungsi uploadFile() tidak ditemukan. Update file Backend/config.php Anda!");
        }
        $uploadResult = uploadFile($_FILES['cover'], $dirCovers);
        if ($uploadResult) $coverFilename = $uploadResult;
    }

    if (!$error_msg) {
        $query = "INSERT INTO books (title, author, description, year, type, cover, file_path, link, status, uploaded_by) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'PENDING', ?)";

        $stmt = mysqli_prepare($conn, $query);

        if (!$stmt) {
            die("DATABASE ERROR (Prepare): " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, "sssissssi", $title, $author, $description, $year, $type, $coverFilename, $pdfFilename, $articleLink, $user_id);

        if (mysqli_stmt_execute($stmt)) {
            $new_id = mysqli_insert_id($conn);

            if (!empty($selected_genres)) {
                foreach ($selected_genres as $gid) {
                    $gid = (int)$gid;
                    mysqli_query($conn, "INSERT INTO book_genres VALUES ($new_id, $gid)");
                }
            }
            $message = "Karya berhasil diupload! Tunggu moderasi Admin.";
        } else {
            $error_msg = "DATABASE ERROR (Execute): " . mysqli_stmt_error($stmt);

            if ($coverFilename) @unlink($dirCovers . '/' . $coverFilename);
            if ($pdfFilename) @unlink($dirBooks . '/' . $pdfFilename);
        }
        mysqli_stmt_close($stmt);
    }
}

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
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
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

<body class="bg-gray-50">

    <!-- NOTIFIKASI -->
    <?php if ($message): ?>
        <div onclick="this.remove()" class="fixed top-4 right-4 bg-gray-900 text-white px-6 py-4 rounded-2xl shadow-2xl z-50 cursor-pointer animate-bounce flex items-center gap-3 border border-gray-700">
            <div class="bg-green-500 p-1 rounded-full"><i data-lucide="check" class="w-4 h-4 text-white"></i></div>
            <span class="text-sm font-bold"><?= $message ?></span>
        </div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div onclick="this.remove()" class="fixed top-4 right-4 bg-red-600 text-white px-6 py-4 rounded-2xl shadow-2xl z-50 cursor-pointer flex items-center gap-3">
            <i data-lucide="alert-circle" class="w-5 h-5"></i>
            <span class="text-sm font-bold"><?= $error_msg ?></span>
        </div>
    <?php endif; ?>

    <div id="mobile-overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden backdrop-blur-sm"></div>

    <div class="flex min-h-screen">
        <!-- SIDEBAR -->
        <aside id="sidebar" class="w-64 bg-white shadow-xl fixed inset-y-0 left-0 z-40 border-r transform -translate-x-full lg:translate-x-0 sidebar-transition h-full overflow-y-auto">
            <div class="p-6 border-b flex flex-col items-center relative">
                <button onclick="toggleSidebar()" class="absolute top-4 right-4 lg:hidden text-gray-500 hover:text-red-500">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
                <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center text-2xl mb-3">
                    <i data-lucide="pen-tool" class="w-8 h-8 text-purple-600"></i>
                </div>
                <h1 class="text-xl font-bold text-purple-900">Publisher</h1>
                <p class="text-xs text-gray-500 mt-1">Halo, <?= htmlspecialchars($current_user['name']) ?></p>
            </div>
            <nav class="p-4 space-y-2">
                <a href="home.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-purple-50 hover:text-purple-700 rounded-lg font-medium transition">
                    <i data-lucide="home" class="w-5 h-5"></i> Home
                </a>
                <a href="dashboard-publisher.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-purple-50 hover:text-purple-700 rounded-lg font-medium transition duration-200">
                    <i data-lucide="library" class="w-5 h-5"></i> Katalog
                </a>
                <a href="my_publications.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-purple-50 hover:text-purple-700 rounded-lg font-medium transition duration-200">
                    <i data-lucide="folder" class="w-5 h-5"></i> Terbitan Saya
                </a>
                <a href="upload.php" class="flex items-center gap-3 px-4 py-3 bg-purple-50 text-purple-700 rounded-lg font-medium border border-purple-100 shadow-sm">
                    <i data-lucide="upload" class="w-5 h-5"></i> Upload Karya
                </a>
                <a href="history.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-purple-50 hover:text-purple-700 rounded-lg font-medium transition">
                    <i data-lucide="history" class="w-5 h-5"></i> Riwayat
                </a>
                <a href="saved_books.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-purple-50 hover:text-purple-700 rounded-lg font-medium transition duration-200">
                    <i data-lucide="bookmark" class="w-5 h-5"></i> Koleksi
                </a>
                <a href="profile.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-purple-50 hover:text-purple-700 rounded-lg font-medium transition">
                    <i data-lucide="settings" class="w-5 h-5"></i> Profile
                </a>
                <a href="logout.php" class="flex items-center gap-3 px-4 py-3 text-red-600 hover:bg-red-50 rounded-lg mt-auto pt-4 border-t">
                    <i data-lucide="log-out" class="w-5 h-5"></i> Keluar
                </a>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 lg:ml-64 p-4 lg:p-10 transition-all duration-300">
            <!-- HEADER MOBILE -->
            <div class="lg:hidden flex items-center justify-between bg-white p-4 rounded-2xl shadow-sm border mb-8 sticky top-0 z-20">
                <button onclick="toggleSidebar()" class="text-gray-700 p-2 hover:bg-gray-100 rounded-xl">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
                <h1 class="font-black text-purple-900">Upload Karya</h1>
                <div class="w-9 h-9 bg-purple-100 rounded-full border border-purple-200"></div>
            </div>

            <div class="max-w-4xl mx-auto">
                <div class="mb-10">
                    <h2 class="text-3xl font-bold text-gray-900 mb-2 leading-tight">Publikasi Karya Baru</h2>
                    <p class="text-gray-500 font-medium">Lengkapi detail di bawah untuk membagikan karya Anda kepada pembaca.</p>
                </div>

                <form method="POST" enctype="multipart/form-data" class="space-y-8">
                    <input type="hidden" name="upload_karya" value="1">

                    <!-- Kartu Detail Utama -->
                    <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6 lg:p-10 transition-all hover:shadow-md">
                        <div class="flex items-center gap-4 mb-8">
                            <div class="w-12 h-12 bg-purple-50 rounded-2xl flex items-center justify-center text-purple-600">
                                <i data-lucide="file-text" class="w-6 h-6"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900">Informasi Dasar</h3>
                                <p class="text-xs text-gray-400 font-medium">Masukkan judul, penulis, dan kategori karya Anda.</p>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div>
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1 mb-2 block">Judul Karya <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <i data-lucide="type" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-300"></i>
                                    <input type="text" name="title" required class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-4 focus:ring-purple-100 focus:bg-white outline-none transition-all font-medium" placeholder="Contoh: Analisis Algoritma Rekursif">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1 mb-2 block">Penulis <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <i data-lucide="user-edit" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-300"></i>
                                        <input type="text" name="author" required class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-4 focus:ring-purple-100 focus:bg-white outline-none transition-all font-medium" placeholder="Nama lengkap penulis">
                                    </div>
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1 mb-2 block">Tahun Terbit <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <i data-lucide="calendar" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-300"></i>
                                        <input type="number" name="year" required class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-4 focus:ring-purple-100 focus:bg-white outline-none transition-all font-medium" placeholder="YYYY">
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="md:col-span-1">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1 mb-2 block">Tipe Dokumen <span class="text-red-500">*</span></label>
                                    <select name="type" id="docType" class="w-full px-4 py-3.5 bg-purple-50 border border-purple-100 rounded-2xl focus:ring-4 focus:ring-purple-100 focus:bg-white outline-none transition-all font-bold text-purple-700 cursor-pointer">
                                        <option value="BOOK">Buku</option>
                                        <option value="JOURNAL">Jurnal</option>
                                        <option value="ARTICLE">Artikel</option>
                                    </select>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1 mb-2 block">Kategori Genre</label>
                                    <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100">
                                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-32 overflow-y-auto genre-scroll pr-2">
                                            <?php mysqli_data_seek($genres_list, 0);
                                            while ($g = mysqli_fetch_assoc($genres_list)): ?>
                                                <label class="inline-flex items-center space-x-2 cursor-pointer group p-1.5 hover:bg-white rounded-xl transition-all">
                                                    <input type="checkbox" name="genres[]" value="<?= $g['id'] ?>" class="form-checkbox text-purple-600 rounded-md focus:ring-purple-500 h-4 w-4 border-gray-300">
                                                    <span class="text-xs text-gray-600 group-hover:text-purple-700 font-bold"><?= htmlspecialchars($g['name']) ?></span>
                                                </label>
                                            <?php endwhile; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Kartu Konten & Media -->
                    <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6 lg:p-10 transition-all hover:shadow-md">
                        <div class="flex items-center gap-4 mb-8">
                            <div class="w-12 h-12 bg-indigo-50 rounded-2xl flex items-center justify-center text-indigo-600">
                                <i data-lucide="image" class="w-6 h-6"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900">Konten & Media</h3>
                                <p class="text-xs text-gray-400 font-medium">Unggah berkas atau masukkan link untuk publikasi Anda.</p>
                            </div>
                        </div>

                        <div class="space-y-8">
                            <div>
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1 mb-2 block">Sinopsis / Deskripsi Singkat</label>
                                <textarea name="description" class="w-full px-5 py-4 bg-gray-50 border border-gray-100 rounded-2xl h-32 focus:ring-4 focus:ring-purple-100 focus:bg-white outline-none resize-none transition-all font-medium text-sm leading-relaxed" placeholder="Gambarkan secara singkat isi karya Anda..."></textarea>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <!-- Upload Cover -->
                                <div class="space-y-3">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1 block">Sampul Buku (Cover)</label>
                                    <div class="group relative bg-gray-50 border-2 border-dashed border-gray-200 rounded-3xl p-6 transition-all hover:border-purple-300 hover:bg-purple-50/30 flex flex-col items-center text-center cursor-pointer">
                                        <i data-lucide="upload-cloud" class="w-10 h-10 text-gray-300 mb-3 group-hover:text-purple-400 transition-colors"></i>
                                        <input type="file" name="cover" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer">
                                        <p class="text-xs font-bold text-gray-500 group-hover:text-purple-600 transition-colors">Pilih gambar sampul</p>
                                        <p class="text-[10px] text-gray-400 mt-1 italic">JPG, PNG, atau JPEG (Maks. 2MB)</p>
                                    </div>
                                </div>

                                <!-- Dynamic Upload Section (PDF or Link) -->
                                <div class="space-y-3">
                                    <div id="labelPDF" class="flex flex-col">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1 mb-3 block">Berkas Dokumen (PDF) <span class="text-red-500">*</span></label>
                                        <div class="group relative bg-indigo-50/50 border-2 border-dashed border-indigo-100 rounded-3xl p-6 transition-all hover:border-indigo-300 hover:bg-indigo-50 flex flex-col items-center text-center cursor-pointer">
                                            <i data-lucide="file-up" class="w-10 h-10 text-indigo-300 mb-3 group-hover:text-indigo-400 transition-colors"></i>
                                            <input type="file" name="file_book" id="fileBookInput" accept="application/pdf" class="absolute inset-0 opacity-0 cursor-pointer">
                                            <p class="text-xs font-bold text-indigo-500 group-hover:text-indigo-600 transition-colors">Pilih file PDF</p>
                                            <p class="text-[10px] text-indigo-400 mt-1 italic">Wajib untuk Buku & Jurnal</p>
                                        </div>
                                    </div>

                                    <div id="labelLink" class="hidden flex flex-col">
                                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1 mb-3 block">Link URL Artikel <span class="text-red-500">*</span></label>
                                        <div class="relative">
                                            <i data-lucide="link" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-300"></i>
                                            <input type="url" name="link" id="linkInput" class="w-full pl-12 pr-4 py-4 bg-green-50 border border-green-100 rounded-2xl focus:ring-4 focus:ring-green-100 focus:bg-white outline-none transition-all font-medium text-sm" placeholder="https://domain.com/judul-artikel">
                                        </div>
                                        <p class="text-[10px] text-green-600 mt-2 font-bold px-2 italic flex items-center gap-1">
                                            <i data-lucide="info" class="w-3 h-3"></i> Tipe artikel hanya memerlukan tautan web.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Action -->
                    <div class="flex items-center justify-end gap-4 pb-10">
                        <a href="dashboard-publisher.php" class="px-8 py-4 text-gray-400 hover:text-gray-600 font-bold transition-colors">Batal</a>
                        <button type="submit" class="px-10 py-4 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-[1.5rem] font-black transition-all transform hover:scale-105 flex items-center gap-3 shadow-xl shadow-purple-100">
                            <i data-lucide="rocket" class="w-5 h-5"></i> Publikasikan Sekarang
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        const docType = document.getElementById('docType');
        const labelPDF = document.getElementById('labelPDF');
        const labelLink = document.getElementById('labelLink');
        const fileBookInput = document.getElementById('fileBookInput');
        const linkInput = document.getElementById('linkInput');

        function toggleInput() {
            if (docType.value === 'ARTICLE') {
                labelPDF.classList.add('hidden');
                labelLink.classList.remove('hidden');
                fileBookInput.required = false;
                linkInput.required = true;
            } else {
                labelPDF.classList.remove('hidden');
                labelLink.classList.add('hidden');
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
                document.body.style.overflow = 'hidden';
            } else {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
                document.body.style.overflow = 'auto';
            }
        }

        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('mobile-overlay');
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
                document.body.style.overflow = 'auto';
            }
        });

        lucide.createIcons();
    </script>
</body>

</html>