<?php
// File ini hanya dijalankan SEKALI untuk memperbaiki struktur database
require 'config.php';

echo "<h1>🛠️ Perbaikan Database Otomatis</h1>";
echo "<div style='background:#f4f4f4; padding:20px; border-radius:10px; font-family:sans-serif;'>";

// 1. Cek & Tambah Kolom 'link'
$check_link = mysqli_query($conn, "SHOW COLUMNS FROM books LIKE 'link'");
if (mysqli_num_rows($check_link) == 0) {
    $sql_add_link = "ALTER TABLE books ADD COLUMN link TEXT DEFAULT NULL AFTER file_path";
    if (mysqli_query($conn, $sql_add_link)) {
        echo "<p style='color:green;'>✅ Berhasil menambahkan kolom <b>'link'</b>.</p>";
    } else {
        echo "<p style='color:red;'>❌ Gagal menambah kolom 'link': " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p style='color:blue;'>ℹ️ Kolom <b>'link'</b> sudah ada (Aman).</p>";
}

// 2. Ubah kolom 'file_path' agar boleh NULL (Supaya Artikel bisa disimpan tanpa PDF)
$sql_modify_path = "ALTER TABLE books MODIFY COLUMN file_path VARCHAR(255) NULL";
if (mysqli_query($conn, $sql_modify_path)) {
    echo "<p style='color:green;'>✅ Berhasil mengubah kolom <b>'file_path'</b> menjadi Nullable.</p>";
} else {
    echo "<p style='color:red;'>❌ Gagal mengubah kolom 'file_path': " . mysqli_error($conn) . "</p>";
}

// 3. Tambah Tabel Saved Books (Jika belum ada, jaga-jaga fitur koleksi)
$sql_saved_books = "CREATE TABLE IF NOT EXISTS saved_books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    UNIQUE KEY unique_saved (user_id, book_id)
)";

if (mysqli_query($conn, $sql_saved_books)) {
    echo "<p style='color:green;'>✅ Tabel <b>'saved_books'</b> siap digunakan.</p>";
} else {
    echo "<p style='color:red;'>❌ Gagal cek tabel 'saved_books': " . mysqli_error($conn) . "</p>";
}

// 4. Update kolom 'type' agar mendukung 'ARTICLE' (FIX PENTING)
$sql_modify_type = "ALTER TABLE books MODIFY COLUMN type ENUM('BOOK', 'JOURNAL', 'ARTICLE') DEFAULT 'BOOK'";
if (mysqli_query($conn, $sql_modify_type)) {
    echo "<p style='color:green;'>✅ Berhasil update kolom <b>'type'</b> (Support ARTICLE).</p>";
} else {
    echo "<p style='color:red;'>❌ Gagal update kolom 'type': " . mysqli_error($conn) . "</p>";
}

echo "<hr>";
echo "<h3>🎉 Perbaikan Selesai!</h3>";
echo "<p>Sekarang silakan coba upload artikel lagi.</p>";
echo "<a href='../Frontend/upload.php' style='background:purple; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>Kembali ke Upload</a>";
echo "</div>";
