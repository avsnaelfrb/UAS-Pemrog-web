<?php
require '../Backend/config.php';

// Cek Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$message = '';
$error = '';

// --- CONFIG TEMA BERDASARKAN ROLE ---
$theme = ($role == 'PENERBIT') ? 'purple' : 'blue';
// Variable warna dinamis untuk class Tailwind
$bg_soft = "bg-$theme-50";
$text_main = "text-$theme-700";
$border_main = "border-$theme-100";
$hover_soft = "hover:bg-$theme-50";
$btn_main = "bg-$theme-600 hover:bg-$theme-700";
$ring_focus = "focus:ring-$theme-500";

// --- LOGIKA REQUEST PENERBIT (User Only) ---
if (isset($_POST['request_publisher'])) {
    mysqli_query($conn, "UPDATE users SET request_penerbit='1' WHERE id=$id");
    $message = "Permintaan dikirim! Tunggu konfirmasi Admin.";
}

// Proses Update Data Profil
if (isset($_POST['update'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $nim = mysqli_real_escape_string($conn, $_POST['nim']);
    $password_baru = $_POST['password'];
    $password_lama = $_POST['old_password'];

    $cek_email = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email' AND id != $id");
    if (mysqli_num_rows($cek_email) > 0) {
        $error = "Email sudah digunakan!";
    } else {
        $password_valid = true;
        $hashed_password_baru = '';

        if (!empty($password_baru)) {
            $q_cek_pass = mysqli_query($conn, "SELECT password FROM users WHERE id = $id");
            $data_user_curr = mysqli_fetch_assoc($q_cek_pass);
            if (password_verify($password_lama, $data_user_curr['password'])) {
                $hashed_password_baru = password_hash($password_baru, PASSWORD_DEFAULT);
            } else {
                $password_valid = false;
                $error = "Password lama salah!";
            }
        }

        if ($password_valid) {
            $query = "UPDATE users SET name='$name', email='$email', nim='$nim'";
            if (!empty($hashed_password_baru)) $query .= ", password='$hashed_password_baru'";
            $query .= " WHERE id=$id";

            if (mysqli_query($conn, $query)) {
                $_SESSION['name'] = $name;
                $message = "Profil berhasil diperbarui!";
            } else {
                $error = "Gagal Update Database.";
            }
        }
    }
}

$query_user = mysqli_query($conn, "SELECT * FROM users WHERE id=$id");
$user = mysqli_fetch_assoc($query_user);
$genres_list = mysqli_query($conn, "SELECT * FROM genres ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil - <?= htmlspecialchars($user['name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .sidebar-transition {
            transition: transform 0.3s ease-in-out;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 50;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
        }

        .modal-active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .genre-scroll::-webkit-scrollbar {
            width: 6px;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen font-sans">

    <?php if ($role == 'ADMIN'): ?>
        <!-- Layout Admin Tetap (Sama seperti sebelumnya, tidak diubah tone warnanya) -->
        <!-- ... (Kode Admin Sama) ... -->
        <div class="container mx-auto p-6">
            <div class="flex flex-col md:flex-row justify-between items-center mb-6 bg-white p-4 rounded-lg shadow-sm gap-4">
                <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2"><span>📚</span> Admin Panel</h1>
                <div class="flex flex-wrap gap-2 items-center">
                    <a href="dashboard-admin.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded hover:bg-gray-200 text-sm font-medium flex items-center gap-1"><span>⬅️</span> Dashboard</a>
                    <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 text-sm font-medium">Logout</a>
                </div>
            </div>
            <?php if ($message): ?> <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">✅ <?= $message ?></div> <?php endif; ?>
            <?php if ($error): ?> <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">❌ <?= $error ?></div> <?php endif; ?>
            <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
                <h2 class="text-xl font-bold mb-6 text-blue-900 border-b pb-2">⚙️ Edit Data Admin</h2>
                <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Form Fields Admin -->
                    <div class="space-y-4">
                        <div><label class="block text-gray-700 font-medium mb-1">Nama Lengkap</label><input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required class="w-full border rounded-lg p-2.5 outline-none"></div>
                        <div><label class="block text-gray-700 font-medium mb-1">NIP / ID</label><input type="text" name="nim" value="<?= htmlspecialchars($user['nim']) ?>" required class="w-full border rounded-lg p-2.5 outline-none"></div>
                    </div>
                    <div class="space-y-4">
                        <div><label class="block text-gray-700 font-medium mb-1">Email</label><input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required class="w-full border rounded-lg p-2.5 outline-none"></div>
                        <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200 mt-2">
                            <h3 class="text-sm font-bold text-yellow-800 mb-3 border-b border-yellow-200 pb-1">🔒 Ganti Password</h3>
                            <div class="space-y-3">
                                <div><label class="block text-gray-700 text-xs font-bold mb-1">Password Lama</label><input type="password" name="old_password" class="w-full border rounded p-2 text-sm bg-white"></div>
                                <div><label class="block text-gray-700 text-xs font-bold mb-1">Password Baru</label><input type="password" name="password" class="w-full border rounded p-2 text-sm bg-white"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-span-1 md:col-span-2 flex gap-3 mt-4 border-t pt-4">
                        <button type="submit" name="update" class="bg-blue-600 text-white px-8 py-2.5 rounded-lg hover:bg-blue-700 font-bold flex-1">Simpan</button>
                        <a href="dashboard-admin.php" class="bg-gray-200 text-gray-700 px-6 py-2.5 rounded-lg hover:bg-gray-300 font-bold">Batal</a>
                    </div>
                </form>
            </div>
        </div>

    <?php else: ?>
        <!-- ======================== TAMPILAN USER / PENERBIT ======================== -->
        <div id="mobile-overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden bg-blur"></div>

        <div class="flex min-h-screen">
            <!-- Sidebar Dinamis -->
            <aside id="sidebar" class="w-64 bg-white shadow-xl fixed inset-y-0 left-0 z-40 border-r transform -translate-x-full lg:translate-x-0 sidebar-transition h-full overflow-y-auto">
                <div class="p-6 border-b flex flex-col items-center relative">
                    <button onclick="toggleSidebar()" class="absolute top-4 right-4 lg:hidden text-gray-500 hover:text-red-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                    <div class="w-16 h-16 <?= ($role == 'PENERBIT') ? 'bg-purple-100 text-purple-600' : 'bg-blue-100 text-blue-600' ?> rounded-full flex items-center justify-center text-2xl mb-3">
                        <?= ($role == 'PENERBIT') ? '✒️' : '👤' ?>
                    </div>
                    <h1 class="text-xl font-bold <?= ($role == 'PENERBIT') ? 'text-purple-900' : 'text-blue-900' ?>">
                        <?= ($role == 'PENERBIT') ? 'Publisher' : 'E-Library' ?>
                    </h1>
                    <p class="text-xs text-gray-500 mt-1">Halo, <?= htmlspecialchars($user['name']) ?></p>
                </div>

                <nav class="p-4 space-y-2">
                    <?php $dash_link = ($role == 'PENERBIT') ? 'dashboard-publisher.php' : 'dashboard-user.php'; ?>
                    <a href="<?= $dash_link ?>" class="flex items-center gap-3 px-4 py-3 text-gray-600 <?= $hover_soft ?> rounded-lg font-medium transition">
                        <span>📚</span> Katalog
                    </a>

                    <!-- MENU KHUSUS PENERBIT (Updated) -->
                    <?php if ($role == 'PENERBIT'): ?>
                        <a href="my_publications.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 <?= $hover_soft ?> rounded-lg font-medium transition">
                            <span>📂</span> Terbitan Saya
                        </a>

                        <a href="upload.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 <?= $hover_soft ?> rounded-lg font-medium transition">
                            <span>📤</span> Upload Karya
                        </a>
                    <?php endif; ?>

                    <a href="history.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 <?= $hover_soft ?> rounded-lg font-medium transition">
                        <span>🕒</span> Riwayat
                    </a>

                    <!-- Menu Koleksi Baru -->
                    <a href="saved_books.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 <?= $hover_soft ?> rounded-lg font-medium transition">
                        <span>🔖</span> Koleksi
                    </a>

                    <!-- Menu Profil Aktif -->
                    <a href="profile.php" class="flex items-center gap-3 px-4 py-3 <?= $bg_soft ?> <?= $text_main ?> rounded-lg font-medium border <?= $border_main ?>">
                        <span>⚙️</span> Profile
                    </a>

                    <?php if ($role == 'USER'): ?>
                        <div class="pt-4 mt-4 border-t border-gray-200">
                            <?php if ($user['request_penerbit'] == '0'): ?>
                                <form method="POST">
                                    <button type="submit" name="request_publisher" onclick="return confirm('Ingin mengajukan diri sebagai Penerbit?')" class="w-full text-left flex items-center gap-3 px-4 py-3 bg-purple-50 text-purple-700 hover:bg-purple-50 hover:text-purple-700 rounded-lg font-medium transition duration-200"><span>✒️</span> Jadi Penerbit</button>
                                </form>
                            <?php else: ?>
                                <div class="px-4 py-3 bg-gray-100 text-gray-500 rounded-lg text-xs italic border text-center">⏳ Menunggu Konfirmasi</div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <a href="logout.php" class="flex items-center gap-3 px-4 py-3 text-red-600 hover:bg-red-50 rounded-lg mt-auto pt-4 border-t">
                        <span>🚪</span> Keluar
                    </a>
                </nav>
            </aside>

            <!-- Main Content User -->
            <main class="flex-1 lg:ml-64 p-4 lg:p-8 transition-all duration-300">
                <!-- Header Mobile -->
                <div class="lg:hidden flex items-center justify-between bg-white p-4 rounded-xl shadow-sm border mb-6 sticky top-0 z-20">
                    <div class="flex items-center gap-3">
                        <button onclick="toggleSidebar()" class="text-gray-700 p-2 hover:bg-gray-100 rounded-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path>
                            </svg>
                        </button>
                        <h1 class="font-bold <?= $text_main ?> text-lg">Edit Profil</h1>
                    </div>
                </div>

                <div class="max-w-4xl mx-auto">
                    <?php if ($message): ?> <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">✅ <?= $message ?></div> <?php endif; ?>
                    <?php if ($error): ?> <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">❌ <?= $error ?></div> <?php endif; ?>

                    <div class="bg-white rounded-xl shadow-sm border p-6 lg:p-8">
                        <div class="flex items-center gap-4 border-b pb-6 mb-6">
                            <div class="<?= $bg_soft ?> p-3 rounded-full <?= $text_main ?>">
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
                                <div><label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap</label><input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 <?= $ring_focus ?> outline-none transition"></div>
                                <div><label class="block text-sm font-medium text-gray-700 mb-2">Nomor Induk</label><input type="text" name="nim" value="<?= htmlspecialchars($user['nim']) ?>" required class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 <?= $ring_focus ?> outline-none transition"></div>
                                <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-2">Email</label><input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 <?= $ring_focus ?> outline-none transition"></div>
                            </div>

                            <div class="bg-gray-50 p-6 rounded-lg border border-gray-100 mt-6">
                                <h3 class="text-sm font-bold text-gray-900 mb-4 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                    Keamanan
                                </h3>
                                <div class="space-y-4">
                                    <div><label class="block text-sm font-medium text-gray-700 mb-2">Password Lama</label><input type="password" name="old_password" class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 <?= $ring_focus ?> outline-none bg-white"></div>
                                    <div><label class="block text-sm font-medium text-gray-700 mb-2">Password Baru</label><input type="password" name="password" class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 <?= $ring_focus ?> outline-none bg-white"></div>
                                </div>
                            </div>

                            <div class="flex justify-end gap-3 pt-4">
                                <button type="submit" name="update" class="px-6 py-3 <?= $btn_main ?> text-white rounded-lg font-bold shadow-lg transition">Simpan Perubahan</button>
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

    <?php endif; ?>

</body>

</html>