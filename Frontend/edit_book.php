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
$id_buku = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error_msg = '';

$query_check = "SELECT * FROM books WHERE id = $id_buku AND uploaded_by = $user_id";
$result_check = mysqli_query($conn, $query_check);

if (!$result_check || mysqli_num_rows($result_check) == 0) {
    echo "<script>alert('Buku tidak ditemukan atau Anda tidak memiliki akses!'); window.location='my_publications.php';</script>";
    exit;
}

$book = mysqli_fetch_assoc($result_check);

$current_genres = [];
$q_genre = mysqli_query($conn, "SELECT genre_id FROM book_genres WHERE book_id = $id_buku");
while ($row = mysqli_fetch_assoc($q_genre)) {
    $current_genres[] = $row['genre_id'];
}

if (isset($_POST['update_karya'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $author = mysqli_real_escape_string($conn, $_POST['author']);
    $year = (int)$_POST['year'];
    $type = $_POST['type'];
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $selected_genres = isset($_POST['genres']) ? $_POST['genres'] : [];

    $pdfFilename = $book['file_path'] ?? null;
    $articleLink = $book['link'] ?? null;
    $coverFilename = $book['cover'] ?? null;

    if ($type == 'ARTICLE') {
        if (!empty($_POST['link'])) {
            $articleLink = mysqli_real_escape_string($conn, $_POST['link']);
            $pdfFilename = NULL;
        } else {
            $error_msg = "Link Artikel wajib diisi!";
        }
    } else {
        // Jika Buku, Cek Upload PDF Baru
        if (isset($_FILES['file_book']) && $_FILES['file_book']['error'] === UPLOAD_ERR_OK) {
            $dirBooks = "../uploads/books";
            if (function_exists('uploadFile')) {
                $uploadResult = uploadFile($_FILES['file_book'], $dirBooks);
                if ($uploadResult) {
                    if ($pdfFilename && file_exists($dirBooks . '/' . $pdfFilename)) {
                        @unlink($dirBooks . '/' . $pdfFilename);
                    }
                    $pdfFilename = $uploadResult;
                } else {
                    $error_msg = "Gagal mengupload file PDF.";
                }
            } else {
                $error_msg = "Fungsi uploadFile tidak ditemukan di config.";
            }
        }
        $articleLink = NULL;
    }

    if (!$error_msg && isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
        $dirCovers = "../uploads/covers";
        if (function_exists('uploadFile')) {
            $uploadResult = uploadFile($_FILES['cover'], $dirCovers);
            if ($uploadResult) {
                if ($coverFilename && file_exists($dirCovers . '/' . $coverFilename)) {
                    @unlink($dirCovers . '/' . $coverFilename);
                }
                $coverFilename = $uploadResult;
            }
        }
    }

    if (!$error_msg) {
        $query = "UPDATE books SET 
                  title=?, author=?, description=?, year=?, type=?, 
                  cover=?, file_path=?, link=?, status='PENDING' 
                  WHERE id=?";

        $stmt = mysqli_prepare($conn, $query);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sssissssi", $title, $author, $description, $year, $type, $coverFilename, $pdfFilename, $articleLink, $id_buku);

            if (mysqli_stmt_execute($stmt)) {
                mysqli_query($conn, "DELETE FROM book_genres WHERE book_id = $id_buku");
                if (!empty($selected_genres)) {
                    foreach ($selected_genres as $gid) {
                        $gid = (int)$gid;
                        mysqli_query($conn, "INSERT INTO book_genres VALUES ($id_buku, $gid)");
                    }
                }
                echo "<script>alert('Berhasil diperbarui! Menunggu persetujuan Admin.'); window.location='my_publications.php';</script>";
                exit;
            } else {
                $error_msg = "Gagal Update: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        } else {
            $error_msg = "Gagal Prepare: " . mysqli_error($conn);
        }
    }
}

$genres_list = mysqli_query($conn, "SELECT * FROM genres ORDER BY name ASC");
$u_res = mysqli_query($conn, "SELECT * FROM users WHERE id=$user_id");
$current_user = mysqli_fetch_assoc($u_res);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Karya - Publisher</title>
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

    <?php if ($error_msg): ?>
        <div onclick="this.remove()" class="fixed top-4 right-4 bg-red-600 text-white px-6 py-3 rounded-lg shadow-lg z-50 cursor-pointer animate-bounce">
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
                <a href="my_publications.php" class="flex items-center gap-3 px-4 py-3 bg-purple-50 text-purple-700 rounded-lg font-medium border border-purple-100 shadow-sm"><span>📂</span> Terbitan Saya</a>
                <a href="upload.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-purple-50 hover:text-purple-700 rounded-lg font-medium transition duration-200"><span>📤</span> Upload Karya</a>
                <a href="history.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-purple-50 hover:text-purple-700 rounded-lg font-medium transition"><span>🕒</span> Riwayat</a>
                <a href="saved_books.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-purple-50 hover:text-purple-700 rounded-lg font-medium transition duration-200"><span>🔖</span> Koleksi</a>
                <a href="profile.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-purple-50 hover:text-purple-700 rounded-lg font-medium transition"><span>⚙️</span> Profil</a>
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
                    <h1 class="font-bold text-purple-900 text-lg">Edit Karya</h1>
                </div>
            </div>

            <div class="max-w-4xl mx-auto">
                <div class="mb-8 flex items-center justify-between">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-800 mb-2">✏️ Edit Publikasi</h2>
                        <p class="text-gray-500">Perbarui informasi karya Anda.</p>
                    </div>
                    <a href="my_publications.php" class="text-gray-500 hover:text-purple-600 font-medium">← Kembali</a>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="bg-yellow-50 border-b border-yellow-100 px-6 py-4 flex items-start gap-3">
                        <span class="text-yellow-600 text-xl">⚠️</span>
                        <p class="text-sm text-yellow-800 leading-relaxed">
                            <strong>Perhatian:</strong> Mengedit karya akan mengubah statusnya menjadi <span class="font-bold">PENDING</span> untuk ditinjau ulang oleh Admin.
                        </p>
                    </div>

                    <form method="POST" enctype="multipart/form-data" class="p-6 lg:p-8 space-y-6">
                        <input type="hidden" name="update_karya" value="1">

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Judul Karya <span class="text-red-500">*</span></label>
                            <input type="text" name="title" value="<?= htmlspecialchars($book['title']) ?>" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-200 outline-none transition">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Penulis <span class="text-red-500">*</span></label>
                                <input type="text" name="author" value="<?= htmlspecialchars($book['author']) ?>" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-200 outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Tahun Terbit <span class="text-red-500">*</span></label>
                                <input type="number" name="year" value="<?= htmlspecialchars($book['year']) ?>" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-200 outline-none">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="md:col-span-1">
                                <label class="block text-sm font-bold text-gray-700 mb-2">Tipe Dokumen <span class="text-red-500">*</span></label>
                                <select name="type" id="docType" class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white focus:ring-2 focus:ring-purple-200 outline-none cursor-pointer">
                                    <option value="BOOK" <?= $book['type'] == 'BOOK' ? 'selected' : '' ?>>📘 Buku</option>
                                    <option value="JOURNAL" <?= $book['type'] == 'JOURNAL' ? 'selected' : '' ?>>📓 Jurnal</option>
                                    <option value="ARTICLE" <?= $book['type'] == 'ARTICLE' ? 'selected' : '' ?>>📰 Artikel</option>
                                </select>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-bold text-gray-700 mb-2">Kategori Genre</label>
                                <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 max-h-32 overflow-y-auto genre-scroll pr-2">
                                        <?php mysqli_data_seek($genres_list, 0);
                                        while ($g = mysqli_fetch_assoc($genres_list)):
                                            $isChecked = in_array($g['id'], $current_genres) ? 'checked' : '';
                                        ?>
                                            <label class="inline-flex items-center space-x-2 cursor-pointer group p-1 hover:bg-white rounded transition">
                                                <input type="checkbox" name="genres[]" value="<?= $g['id'] ?>" <?= $isChecked ?> class="form-checkbox text-purple-600 rounded focus:ring-purple-500 h-4 w-4 border-gray-300">
                                                <span class="text-sm text-gray-600 group-hover:text-purple-700 font-medium"><?= htmlspecialchars($g['name']) ?></span>
                                            </label>
                                        <?php endwhile; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Sinopsis / Deskripsi</label>
                            <textarea name="description" class="w-full px-4 py-3 border border-gray-300 rounded-xl h-32 focus:ring-2 focus:ring-purple-200 outline-none resize-none"><?= htmlspecialchars($book['description']) ?></textarea>
                        </div>

                        <!-- DYNAMIC INPUT -->
                        <div class="bg-gray-50 p-6 rounded-xl border border-gray-200 transition-all duration-300">

                            <!-- Input File PDF -->
                            <div id="inputPDF" class="<?= $book['type'] == 'ARTICLE' ? 'hidden' : '' ?>">
                                <label class="block text-sm font-bold text-gray-700 mb-2">File PDF</label>
                                <div class="flex items-center gap-4 mb-2">
                                    <?php if (!empty($book['file_path'] ?? '')): ?>
                                        <a href="read.php?id=<?= $book['id'] ?>" target="_blank" class="text-sm text-blue-600 underline hover:text-blue-800">📄 Lihat File Saat Ini</a>
                                    <?php else: ?>
                                        <span class="text-sm text-gray-400 italic">Tidak ada file.</span>
                                    <?php endif; ?>
                                </div>
                                <div class="relative">
                                    <input type="file" name="file_book" id="fileBookInput" accept="application/pdf"
                                        class="block w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer border border-gray-300 rounded-xl p-1.5 bg-white focus:outline-none focus:ring-2 focus:ring-blue-200">
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Upload file baru jika ingin mengganti.</p>
                            </div>

                            <!-- Input Link Artikel -->
                            <div id="inputLink" class="<?= $book['type'] == 'ARTICLE' ? '' : 'hidden' ?>">
                                <label class="block text-sm font-bold text-gray-700 mb-2">Link Artikel <span class="text-red-500">*</span></label>
                                <input type="url" name="link" id="linkInput" value="<?= htmlspecialchars($book['link'] ?? '') ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-200 focus:border-green-400 outline-none transition placeholder-gray-400"
                                    placeholder="https://...">
                            </div>
                        </div>

                        <!-- Cover Upload -->
                        <div class="relative group mt-4 flex gap-6 items-start">
                            <div class="flex-1">
                                <label class="block text-sm font-bold text-gray-700 mb-2">Cover Buku</label>
                                <input type="file" name="cover" accept="image/*"
                                    class="block w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer border border-gray-300 rounded-xl p-1.5 bg-white focus:outline-none focus:ring-2 focus:ring-blue-200">
                                <p class="text-xs text-gray-500 mt-1">Upload cover baru jika ingin mengganti.</p>
                            </div>
                            <?php if (!empty($book['cover'] ?? '')): ?>
                                <div class="w-20 h-28 bg-gray-200 rounded overflow-hidden shadow-sm border border-gray-300">
                                    <img src="../uploads/covers/<?= htmlspecialchars($book['cover']) ?>" class="w-full h-full object-cover">
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="pt-6 border-t border-gray-100 flex justify-end gap-3">
                            <a href="my_publications.php" class="px-6 py-3 rounded-xl font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 transition">Batal</a>
                            <button type="submit" class="px-8 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl font-bold shadow-lg hover:shadow-xl hover:from-purple-700 hover:to-indigo-700 transition transform hover:-translate-y-0.5 flex items-center gap-2">
                                <span>💾</span> Simpan & Ajukan
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
                fileBookInput.required = false; // Saat edit, tidak wajib upload ulang PDF
                linkInput.required = false;
            }
        }

        docType.addEventListener('change', toggleInput);

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