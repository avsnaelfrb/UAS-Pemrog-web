<?php
require '../Backend/config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'ADMIN') {
    header("Location: login.php");
    exit;
}

$message = '';
$error_msg = '';


if (isset($_POST['save_book'])) {
    $mode = $_POST['mode'];
    $book_id = isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0;

    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $author = mysqli_real_escape_string($conn, $_POST['author']);
    $year = (int)$_POST['year'];
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);

    $selected_genres = isset($_POST['genres']) ? $_POST['genres'] : [];

    if (empty($selected_genres)) {
        $error_msg = "Wajib memilih minimal satu genre!";
    } else {

        $cover_sql_val = "NULL";
        $file_sql_val = "NULL";

        if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
            $imgData = file_get_contents($_FILES['cover']['tmp_name']);
            $imgData = mysqli_real_escape_string($conn, $imgData);
            $cover_sql_val = "'$imgData'";
        } else {
        }

        if (isset($_FILES['file_book']) && $_FILES['file_book']['error'] === UPLOAD_ERR_OK) {
            $pdfData = file_get_contents($_FILES['file_book']['tmp_name']);
            $pdfData = mysqli_real_escape_string($conn, $pdfData);
            $file_sql_val = "'$pdfData'";
        } else {
            if ($mode == 'add' && (!isset($_FILES['file_book']) || $_FILES['file_book']['error'] !== UPLOAD_ERR_OK)) {
                $error_msg .= "File PDF wajib diupload untuk buku baru! ";
            }
        }

        if (empty($error_msg)) {
            if ($mode == 'add') {
                $query = "INSERT INTO books (title, author, description, cover, file_path, year, type) 
                          VALUES ('$title', '$author', '$description', $cover_sql_val, $file_sql_val, $year, '$type')";

                if (mysqli_query($conn, $query)) {
                    $new_book_id = mysqli_insert_id($conn);
                    foreach ($selected_genres as $gid) {
                        $gid = (int)$gid;
                        mysqli_query($conn, "INSERT INTO book_genres (book_id, genre_id) VALUES ($new_book_id, $gid)");
                    }
                    $message = "Buku berhasil ditambahkan ke Database!";
                } else {
                    $error_msg = "Database Error (Mungkin file terlalu besar, cek max_allowed_packet): " . mysqli_error($conn);
                }
            } else {
                $update_parts = [
                    "title='$title'",
                    "author='$author'",
                    "description='$description'",
                    "year=$year",
                    "type='$type'"
                ];

                if ($cover_sql_val !== "NULL") {
                    $update_parts[] = "cover=$cover_sql_val";
                }
                if ($file_sql_val !== "NULL") {
                    $update_parts[] = "file_path=$file_sql_val";
                }

                $query = "UPDATE books SET " . implode(', ', $update_parts) . " WHERE id=$book_id";

                if (mysqli_query($conn, $query)) {
                    mysqli_query($conn, "DELETE FROM book_genres WHERE book_id = $book_id");
                    foreach ($selected_genres as $gid) {
                        $gid = (int)$gid;
                        mysqli_query($conn, "INSERT INTO book_genres (book_id, genre_id) VALUES ($book_id, $gid)");
                    }
                    echo "<script>alert('Buku berhasil diperbarui!'); window.location='dashboard-admin.php';</script>";
                    exit;
                } else {
                    $error_msg = "Database Error: " . mysqli_error($conn);
                }
            }
        }
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM book_genres WHERE book_id=$id");
    mysqli_query($conn, "DELETE FROM books WHERE id=$id");

    header("Location: dashboard-admin.php");
    exit;
}

$edit_data = null;
$edit_genres_ids = [];
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];

    $q = mysqli_query($conn, "SELECT id, title, author, description, year, type, 
                              CASE WHEN cover IS NOT NULL AND LENGTH(cover) > 0 THEN 1 ELSE 0 END as has_cover,
                              CASE WHEN file_path IS NOT NULL AND LENGTH(file_path) > 0 THEN 1 ELSE 0 END as has_file
                              FROM books WHERE id=$id");
    $edit_data = mysqli_fetch_assoc($q);

    if ($edit_data) {
        $qg = mysqli_query($conn, "SELECT genre_id FROM book_genres WHERE book_id=$id");
        while ($row = mysqli_fetch_assoc($qg)) {
            $edit_genres_ids[] = $row['genre_id'];
        }
    }
}

$books_query = "
    SELECT b.id, b.title, b.author, b.year, b.type, b.cover, 
    CASE WHEN file_path IS NOT NULL AND LENGTH(file_path) > 0 THEN 1 ELSE 0 END as file_exists,
    GROUP_CONCAT(g.name SEPARATOR ', ') as genre_names 
    FROM books b 
    LEFT JOIN book_genres bg ON b.id = bg.book_id 
    LEFT JOIN genres g ON bg.genre_id = g.id 
    GROUP BY b.id 
    ORDER BY b.created_at DESC
";
$books = mysqli_query($conn, $books_query);
$genres = mysqli_query($conn, "SELECT * FROM genres");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - E-Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 min-h-screen">

    <div class="container mx-auto p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 bg-white p-4 rounded-lg shadow-sm">
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <span>📚</span> Dashboard Admin (Database Mode)
            </h1>
            <div class="flex gap-2">
                <a href="dashboard-user.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 text-sm font-medium">Lihat Web User</a>
                <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 text-sm font-medium">Logout</a>
            </div>
        </div>

        <!-- Notifikasi -->
        <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">✅ <?= $message ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">❌ <?= $error_msg ?></div>
        <?php endif; ?>

        <!-- Form Add/Edit Buku -->
        <div class="bg-white p-6 rounded-lg shadow-md mb-8 border border-gray-200">
            <h2 class="text-xl font-bold mb-4 text-blue-900 border-b pb-2">
                <?= $edit_data ? '✏️ Edit Data Buku' : '➕ Tambah Buku Baru' ?>
            </h2>

            <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <input type="hidden" name="mode" value="<?= $edit_data ? 'edit' : 'add' ?>">
                <?php if ($edit_data): ?>
                    <input type="hidden" name="book_id" value="<?= $edit_data['id'] ?>">
                <?php endif; ?>

                <!-- Kolom Kiri -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-700 font-medium mb-1">Judul Buku</label>
                        <input type="text" name="title" required value="<?= $edit_data['title'] ?? '' ?>" class="w-full border rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-1">Penulis</label>
                        <input type="text" name="author" required value="<?= $edit_data['author'] ?? '' ?>" class="w-full border rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 font-medium mb-1">Tahun Terbit</label>
                            <input type="number" name="year" required value="<?= $edit_data['year'] ?? '' ?>" class="w-full border rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-gray-700 font-medium mb-1">Tipe Dokumen</label>
                            <select name="type" class="w-full border rounded-lg p-2.5 bg-white outline-none">
                                <option value="BOOK" <?= ($edit_data['type'] ?? '') == 'BOOK' ? 'selected' : '' ?>>Buku</option>
                                <option value="JOURNAL" <?= ($edit_data['type'] ?? '') == 'JOURNAL' ? 'selected' : '' ?>>Jurnal</option>
                                <option value="ARTICLE" <?= ($edit_data['type'] ?? '') == 'ARTICLE' ? 'selected' : '' ?>>Artikel</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Pilih Genre</label>
                        <div class="bg-gray-50 p-3 rounded-lg border h-32 overflow-y-auto grid grid-cols-2 gap-2">
                            <?php
                            mysqli_data_seek($genres, 0);
                            while ($g = mysqli_fetch_assoc($genres)):
                                $isChecked = in_array($g['id'], $edit_genres_ids) ? 'checked' : '';
                            ?>
                                <label class="flex items-center space-x-2 text-sm cursor-pointer hover:bg-gray-100 p-1 rounded">
                                    <input type="checkbox" name="genres[]" value="<?= $g['id'] ?>" <?= $isChecked ?> class="rounded text-blue-600 focus:ring-blue-500 h-4 w-4">
                                    <span class="text-gray-700 select-none"><?= $g['name'] ?></span>
                                </label>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-700 font-medium mb-1">File PDF Buku <span class="text-red-500">*</span></label>
                        <input type="file" name="file_book" accept="application/pdf" class="w-full border rounded-lg p-2 bg-gray-50 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <?php if ($edit_data && $edit_data['has_file']): ?>
                            <div class="mt-1 text-xs text-green-600 flex items-center gap-1">
                                ✅ PDF Tersimpan di Database (Biarkan kosong jika tidak mengubah)
                            </div>
                        <?php else: ?>
                            <small class="text-gray-500">Wajib upload format .pdf untuk buku baru.</small>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="block text-gray-700 font-medium mb-1">Cover Gambar</label>
                        <input type="file" name="cover" accept="image/*" class="w-full border rounded-lg p-2 bg-gray-50 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <?php if ($edit_data && $edit_data['has_cover']): ?>
                            <div class="mt-2 text-xs text-green-600">✅ Cover Tersimpan di Database</div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="block text-gray-700 font-medium mb-1">Deskripsi Singkat</label>
                        <textarea name="description" class="w-full border rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 outline-none h-28" placeholder="Tulis ringkasan buku..."><?= $edit_data['description'] ?? '' ?></textarea>
                    </div>
                </div>

                <div class="col-span-1 md:col-span-2 flex gap-3 mt-4 border-t pt-4">
                    <button type="submit" name="save_book" class="bg-blue-600 text-white px-8 py-2.5 rounded-lg hover:bg-blue-700 font-bold flex-1 transition shadow-lg shadow-blue-200">
                        <?= $edit_data ? 'Simpan Perubahan' : 'Upload Buku Sekarang' ?>
                    </button>
                    <?php if ($edit_data): ?>
                        <a href="dashboard-admin.php" class="bg-gray-200 text-gray-700 px-6 py-2.5 rounded-lg hover:bg-gray-300 font-bold transition">Batal Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Tabel Daftar Buku -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden border border-gray-200">
            <div class="p-4 bg-gray-50 border-b">
                <h3 class="font-bold text-gray-700">Daftar Koleksi Digital (Database Storage)</h3>
            </div>
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-100 border-b text-gray-600 text-sm uppercase tracking-wider">
                    <tr>
                        <th class="p-4 w-24">Cover</th>
                        <th class="p-4">Detail Buku</th>
                        <th class="p-4 w-64">Genre</th>
                        <th class="p-4 w-32">Status File</th>
                        <th class="p-4 text-right w-40">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php while ($row = mysqli_fetch_assoc($books)): ?>
                        <tr class="hover:bg-blue-50 transition duration-150">
                            <td class="p-4 align-top">
                                <?php if ($row['cover']): ?>
                                    <!-- DISPLAY IMAGE DARI BLOB MENGGUNAKAN BASE64 -->
                                    <img src="data:image/jpeg;base64,<?= base64_encode($row['cover']) ?>" class="h-20 w-14 object-cover rounded shadow border bg-white">
                                <?php else: ?>
                                    <div class="h-20 w-14 bg-gray-200 flex items-center justify-center text-xs text-gray-500 rounded border">No img</div>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 align-top">
                                <div class="font-bold text-gray-900 text-lg"><?= htmlspecialchars($row['title']) ?></div>
                                <div class="text-sm text-gray-600 font-medium"><?= htmlspecialchars($row['author']) ?></div>
                                <div class="text-xs text-gray-400 mt-1 bg-gray-100 inline-block px-2 py-1 rounded">
                                    <?= $row['year'] ?> • <?= $row['type'] ?>
                                </div>
                            </td>
                            <td class="p-4 align-top">
                                <div class="flex flex-wrap gap-1">
                                    <?php
                                    $bg_genres = explode(',', $row['genre_names']);
                                    foreach ($bg_genres as $g_name):
                                        if (trim($g_name) == '') continue;
                                    ?>
                                        <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-md border border-blue-200">
                                            <?= trim($g_name) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td class="p-4 align-top text-sm">
                                <?php if ($row['file_exists']): ?>
                                    <span class="text-green-600 font-bold flex items-center gap-1 bg-green-50 px-2 py-1 rounded w-max">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Stored in DB
                                    </span>
                                <?php else: ?>
                                    <span class="text-red-500 font-bold bg-red-50 px-2 py-1 rounded">Empty</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 align-top text-right">
                                <div class="flex flex-col gap-2">
                                    <a href="?edit=<?= $row['id'] ?>" class="text-center bg-yellow-400 text-yellow-900 px-3 py-1.5 rounded hover:bg-yellow-500 font-medium text-sm transition shadow-sm">
                                        ✏️ Edit
                                    </a>
                                    <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Hapus permanen?')" class="text-center bg-red-100 text-red-700 px-3 py-1.5 rounded hover:bg-red-200 font-medium text-sm transition">
                                        🗑️ Hapus
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>