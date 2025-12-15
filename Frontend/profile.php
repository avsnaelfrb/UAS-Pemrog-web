<?php
require '../Backend/config.php';

// Cek Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = $_SESSION['user_id'];
$message = '';
$error = '';

// Proses Update Data
if (isset($_POST['update'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $nim = mysqli_real_escape_string($conn, $_POST['nim']);

    $password_baru = $_POST['password']; // Password baru (opsional)
    $password_lama = $_POST['old_password']; // Password lama (wajib jika ganti pass)

    // Cek email duplikat (kecuali punya sendiri)
    $cek_email = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email' AND id != $id");

    if (mysqli_num_rows($cek_email) > 0) {
        $error = "Email sudah digunakan oleh pengguna lain!";
    } else {
        // Logika Validasi Password
        $password_valid = true;
        $hashed_password_baru = '';

        // Jika user ingin ganti password (kolom password baru tidak kosong)
        if (!empty($password_baru)) {
            // Ambil password saat ini dari DB
            $q_cek_pass = mysqli_query($conn, "SELECT password FROM users WHERE id = $id");
            $data_user_curr = mysqli_fetch_assoc($q_cek_pass);

            // Verifikasi password lama
            if (password_verify($password_lama, $data_user_curr['password'])) {
                $hashed_password_baru = password_hash($password_baru, PASSWORD_DEFAULT);
            } else {
                $password_valid = false;
                $error = "Password lama salah! Profil gagal diperbarui.";
            }
        }

        // Jika validasi password lolos (atau tidak ganti password)
        if ($password_valid) {
            $query = "UPDATE users SET name='$name', email='$email', nim='$nim'";

            // Tambahkan update password jika ada
            if (!empty($hashed_password_baru)) {
                $query .= ", password='$hashed_password_baru'";
            }

            $query .= " WHERE id=$id";

            if (mysqli_query($conn, $query)) {
                $_SESSION['name'] = $name;
                $message = "Profil berhasil diperbarui!";
            } else {
                $error = "Gagal Update Database: " . mysqli_error($conn);
            }
        }
    }
}

// Ambil Data User Terbaru untuk ditampilkan di Form
$query_user = mysqli_query($conn, "SELECT * FROM users WHERE id=$id");
$user = mysqli_fetch_assoc($query_user);
$role = $user['role'];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil - <?= htmlspecialchars($user['name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 min-h-screen font-sans">

    <?php if ($role == 'ADMIN'): ?>
        <!-- ======================== TAMPILAN ADMIN (NAVBAR MODE) ======================== -->

        <div class="container mx-auto p-6">
            <!-- Header Admin -->
            <div class="flex flex-col md:flex-row justify-between items-center mb-6 bg-white p-4 rounded-lg shadow-sm gap-4">
                <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                    <span>📚</span> Admin Panel
                </h1>
                <div class="flex flex-wrap gap-2 items-center">
                    <a href="dashboard-admin.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded hover:bg-gray-200 text-sm font-medium flex items-center gap-1">
                        <span>⬅️</span> Kembali ke Dashboard
                    </a>
                    <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 text-sm font-medium">Logout</a>
                </div>
            </div>

            <!-- Notifikasi -->
            <?php if ($message): ?> <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">✅ <?= $message ?></div> <?php endif; ?>
            <?php if ($error): ?> <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">❌ <?= $error ?></div> <?php endif; ?>

            <!-- Form Admin -->
            <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
                <h2 class="text-xl font-bold mb-6 text-blue-900 border-b pb-2">⚙️ Edit Data Admin</h2>

                <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Kolom Kiri -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-gray-700 font-medium mb-1">Nama Lengkap</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required class="w-full border rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-gray-700 font-medium mb-1">NIP / ID Admin</label>
                            <input type="text" name="nim" value="<?= htmlspecialchars($user['nim']) ?>" required class="w-full border rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                    </div>

                    <!-- Kolom Kanan -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-gray-700 font-medium mb-1">Email Login</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required class="w-full border rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>

                        <!-- Section Keamanan Admin -->
                        <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200 mt-2">
                            <h3 class="text-sm font-bold text-yellow-800 mb-3 border-b border-yellow-200 pb-1">🔒 Ganti Password (Opsional)</h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-gray-700 text-xs font-bold mb-1">Password Lama</label>
                                    <input type="password" name="old_password" placeholder="Wajib diisi jika ganti password baru" class="w-full border rounded p-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-xs font-bold mb-1">Password Baru</label>
                                    <input type="password" name="password" placeholder="Biarkan kosong jika tidak ganti" class="w-full border rounded p-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Action -->
                    <div class="col-span-1 md:col-span-2 flex gap-3 mt-4 border-t pt-4">
                        <button type="submit" name="update" class="bg-blue-600 text-white px-8 py-2.5 rounded-lg hover:bg-blue-700 font-bold flex-1 transition shadow-lg">Simpan Perubahan Profil</button>
                        <a href="dashboard-admin.php" class="bg-gray-200 text-gray-700 px-6 py-2.5 rounded-lg hover:bg-gray-300 font-bold text-center">Batal</a>
                    </div>
                </form>
            </div>
        </div>

    <?php else: ?>
        <!-- ======================== TAMPILAN USER (SIDEBAR MODE) ======================== -->

        <div class="flex min-h-screen">
            <!-- Sidebar -->
            <aside class="w-64 bg-white shadow-xl fixed inset-y-0 left-0 z-10 hidden lg:block border-r">
                <div class="p-6 border-b flex flex-col items-center">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center text-2xl mb-3">👤</div>
                    <h1 class="text-xl font-bold text-blue-900">E-Library</h1>
                    <p class="text-xs text-gray-500 mt-1">Halo, <?= htmlspecialchars($_SESSION['name']) ?></p>
                </div>
                <nav class="p-4 space-y-2">
                    <a href="dashboard-user.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg font-medium transition">
                        <span>📚</span> Katalog Buku
                    </a>
                    <a href="profile.php" class="flex items-center gap-3 px-4 py-3 bg-blue-50 text-blue-700 rounded-lg font-medium border border-blue-100">
                        <span>⚙️</span> Profil Saya
                    </a>
                    <a href="logout.php" class="flex items-center gap-3 px-4 py-3 text-red-600 hover:bg-red-50 rounded-lg mt-auto pt-4 border-t">
                        <span>🚪</span> Keluar
                    </a>
                </nav>
            </aside>

            <!-- Main Content User -->
            <main class="flex-1 lg:ml-64 p-4 lg:p-8">
                <div class="lg:hidden bg-white p-4 rounded-lg shadow-sm mb-4 flex justify-between items-center">
                    <h1 class="font-bold text-blue-900">E-Library</h1>
                    <a href="dashboard-user.php" class="text-sm text-gray-500">Kembali</a>
                </div>

                <div class="max-w-4xl mx-auto">
                    <!-- Notifikasi -->
                    <?php if ($message): ?> <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">✅ <?= $message ?></div> <?php endif; ?>
                    <?php if ($error): ?> <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">❌ <?= $error ?></div> <?php endif; ?>

                    <div class="bg-white rounded-xl shadow-sm border p-6 lg:p-8">
                        <div class="flex items-center gap-4 border-b pb-6 mb-6">
                            <div class="bg-blue-100 p-3 rounded-full text-blue-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-gray-800">Profil Saya</h2>
                                <p class="text-gray-500 text-sm">Kelola informasi akun Anda</p>
                            </div>
                        </div>

                        <form method="POST" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap</label>
                                    <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nomor Induk (NIM)</label>
                                    <input type="text" name="nim" value="<?= htmlspecialchars($user['nim']) ?>" required class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition">
                                </div>
                            </div>

                            <!-- Section Keamanan User -->
                            <div class="bg-gray-50 p-6 rounded-lg border border-gray-100 mt-6">
                                <h3 class="text-sm font-bold text-gray-900 mb-4 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                    Keamanan (Ganti Password)
                                </h3>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Password Lama</label>
                                        <input type="password" name="old_password" placeholder="Wajib diisi jika ingin mengubah password" class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition bg-white">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Password Baru</label>
                                        <input type="password" name="password" placeholder="Kosongkan jika tidak ingin mengubah password" class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition bg-white">
                                        <p class="text-xs text-gray-500 mt-2">*Minimal 8 karakter disarankan</p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end gap-3 pt-4">
                                <button type="submit" name="update" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-bold shadow-lg shadow-blue-200 transition">
                                    Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>

    <?php endif; ?>

</body>

</html>