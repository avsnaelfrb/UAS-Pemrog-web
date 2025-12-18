<?php

/**
 * Jalankan file ini sekali di browser untuk memperbaiki data role di database cloud
 * Contoh: yourdomain.vercel.app/api/Backend/fix_db_data.php
 */
require_once 'config.php';

echo "<h1>🛠️ Perbaikan Data Database Cloud</h1>";

// 1. Normalisasi Role User (Memastikan huruf besar semua)
$roles = ['ADMIN', 'USER', 'PENERBIT'];
foreach ($roles as $role) {
    $query = "UPDATE users SET role = '$role' WHERE UPPER(role) = '$role'";
    mysqli_query($conn, $query);
}

// 2. Perbaikan jika ada role yang mengandung spasi atau lowercase
$update_admin = mysqli_query($conn, "UPDATE users SET role = 'ADMIN' WHERE LOWER(role) LIKE '%admin%'");
$update_penerbit = mysqli_query($conn, "UPDATE users SET role = 'PENERBIT' WHERE LOWER(role) LIKE '%penerbit%'");
$update_user = mysqli_query($conn, "UPDATE users SET role = 'USER' WHERE LOWER(role) LIKE '%user%'");

echo "✅ Data Role telah dinormalisasi ke UPPERCASE.<br>";

// 3. Pastikan kolom request_penerbit memiliki default yang benar
mysqli_query($conn, "ALTER TABLE users MODIFY COLUMN request_penerbit ENUM('0','1') DEFAULT '0'");

echo "✅ Struktur request_penerbit telah diperbaiki.<br>";
echo "<hr><a href='../Frontend/login.php'>Kembali ke Login</a>";
