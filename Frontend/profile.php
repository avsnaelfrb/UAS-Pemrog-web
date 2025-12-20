<?php
require_once dirname(__DIR__) . '/Backend/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$message = '';
$error = '';

// Pengaturan Tema Dinamis
$theme = ($role == 'PENERBIT') ? 'purple' : 'blue';
$bg_soft = "bg-$theme-50";
$text_main = "text-$theme-700";
$text_dark = "text-$theme-900";
$border_main = "border-$theme-100";
$hover_soft = "hover:bg-$theme-50";
$btn_main = "bg-$theme-600 hover:bg-$theme-700 shadow-$theme-100";
$ring_focus = "focus:ring-$theme-500";

// Handle Request Penerbit
if (isset($_POST['request_publisher'])) {
    mysqli_query($conn, "UPDATE users SET request_penerbit='1' WHERE id=$id");
    $message = "Permintaan dikirim! Tunggu konfirmasi Admin.";
}

// Handle Update Profile
if (isset($_POST['update'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $nim = mysqli_real_escape_string($conn, $_POST['nim']);
    $password_baru = $_POST['password'];
    $password_lama = $_POST['old_password'];

    $cek_email = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email' AND id != $id");
    if (mysqli_num_rows($cek_email) > 0) {
        $error = "Email sudah digunakan oleh akun lain!";
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
                $error = "Password lama yang Anda masukkan salah!";
            }
        }

        if ($password_valid) {
            $query = "UPDATE users SET name='$name', email='$email', nim='$nim'";
            if (!empty($hashed_password_baru)) $query .= ", password='$hashed_password_baru'";
            $query .= " WHERE id=$id";

            if (mysqli_query($conn, $query)) {
                $_SESSION['name'] = $name;
                $message = "Profil Anda berhasil diperbarui!";
            } else {
                $error = "Terjadi kesalahan saat memperbarui database.";
            }
        }
    }
}

$query_user = mysqli_query($conn, "SELECT * FROM users WHERE id=$id");
$user = mysqli_fetch_assoc($query_user);

// Ambil Statistik Singkat (Opsional untuk Informasi Tambahan)
$count_history = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM history WHERE user_id=$id"))['total'];
$count_saved = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM saved_books WHERE user_id=$id"))['total'];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - E-Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .sidebar-transition {
            transition: transform 0.3s ease-in-out;
        }

        .glass-effect {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen font-sans text-gray-800">

    <!-- OVERLAY MOBILE -->
    <div id="mobile-overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden backdrop-blur-sm"></div>

    <!-- NOTIFIKASI -->
    <?php if ($message): ?>
        <div onclick="this.remove()" class="fixed top-4 right-4 bg-gray-900 text-white px-6 py-4 rounded-2xl shadow-2xl z-50 cursor-pointer animate-bounce flex items-center gap-3 border border-gray-700">
            <div class="bg-green-500 p-1 rounded-full"><i data-lucide="check" class="w-4 h-4"></i></div>
            <span class="text-sm font-bold"><?= $message ?></span>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div onclick="this.remove()" class="fixed top-4 right-4 bg-red-600 text-white px-6 py-4 rounded-2xl shadow-2xl z-50 cursor-pointer animate-pulse flex items-center gap-3">
            <i data-lucide="alert-circle" class="w-5 h-5"></i>
            <span class="text-sm font-bold"><?= $error ?></span>
        </div>
    <?php endif; ?>

    <div class="flex min-h-screen">
        <!-- SIDEBAR -->
        <aside id="sidebar" class="w-64 bg-white shadow-xl fixed inset-y-0 left-0 z-40 border-r transform -translate-x-full lg:translate-x-0 sidebar-transition h-full overflow-y-auto">
            <div class="p-6 border-b flex flex-col items-center relative">
                <button onclick="toggleSidebar()" class="absolute top-4 right-4 lg:hidden text-gray-500 hover:text-red-500">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
                <div class="w-16 h-16 <?= ($role == 'PENERBIT') ? 'bg-purple-100 text-purple-600' : 'bg-blue-100 text-blue-600' ?> rounded-full flex items-center justify-center text-2xl mb-3">
                    <?php if ($role == 'PENERBIT'): ?>
                        <i data-lucide="pen-tool" class="w-8 h-8"></i>
                    <?php else: ?>
                        <i data-lucide="user" class="w-8 h-8"></i>
                    <?php endif; ?>
                </div>
                <h1 class="text-xl font-bold <?= ($role == 'PENERBIT') ? 'text-purple-900' : 'text-blue-900' ?>">
                    <?= ($role == 'PENERBIT') ? 'Publisher' : 'E-Library' ?>
                </h1>
                <p class="text-xs text-gray-500 mt-1 text-center">Halo, <?= htmlspecialchars($user['name']) ?></p>
            </div>

            <nav class="p-4 space-y-2">
                <a href="home.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 <?= $hover_soft ?> hover:text-<?= $theme ?>-700 rounded-lg font-medium transition">
                    <i data-lucide="home" class="w-5 h-5"></i> Home
                </a>
                <?php $dash_link = ($role == 'PENERBIT') ? 'dashboard-publisher.php' : 'dashboard-user.php'; ?>
                <a href="<?= $dash_link ?>" class="flex items-center gap-3 px-4 py-3 text-gray-600 <?= $hover_soft ?> hover:text-<?= $theme ?>-700 rounded-lg font-medium transition">
                    <i data-lucide="library" class="w-5 h-5"></i> Katalog
                </a>

                <?php if ($role == 'PENERBIT'): ?>
                    <a href="my_publications.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 <?= $hover_soft ?> hover:text-<?= $theme ?>-700 rounded-lg font-medium transition">
                        <i data-lucide="folder" class="w-5 h-5"></i> Terbitan Saya
                    </a>
                    <a href="upload.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 <?= $hover_soft ?> hover:text-<?= $theme ?>-700 rounded-lg font-medium transition">
                        <i data-lucide="upload" class="w-5 h-5"></i> Upload Karya
                    </a>
                <?php endif; ?>

                <a href="history.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 <?= $hover_soft ?> hover:text-<?= $theme ?>-700 rounded-lg font-medium">
                    <i data-lucide="history" class="w-5 h-5"></i> Riwayat
                </a>

                <a href="saved_books.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 <?= $hover_soft ?> hover:text-<?= $theme ?>-700 rounded-lg font-medium transition">
                    <i data-lucide="bookmark" class="w-5 h-5"></i> Koleksi
                </a>

                <a href="profile.php" class="flex items-center gap-3 px-4 py-3 <?= $bg_soft ?> <?= $text_main ?> rounded-lg font-medium border <?= $border_main ?> shadow-sm transition">
                    <i data-lucide="settings" class="w-5 h-5"></i> Profile
                </a>

                <?php if ($role == 'USER'): ?>
                    <div class="pt-4 mt-4 border-t border-gray-200">
                        <?php if ($user['request_penerbit'] == '0'): ?>
                            <form method="POST">
                                <button type="submit" name="request_publisher" onclick="return confirm('Ingin mengajukan diri sebagai Penerbit?')" class="w-full text-left flex items-center gap-3 px-4 py-3 bg-purple-50 text-purple-700 hover:bg-purple-100 rounded-lg font-medium transition duration-200">
                                    <i data-lucide="pen-tool" class="w-5 h-5"></i> Jadi Penerbit
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="px-4 py-3 bg-gray-100 text-gray-500 rounded-lg text-xs italic border text-center flex items-center justify-center gap-2">
                                <i data-lucide="hourglass" class="w-4 h-4"></i> Menunggu Konfirmasi
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <a href="logout.php" class="flex items-center gap-3 px-4 py-3 text-red-600 hover:bg-red-50 rounded-lg mt-auto pt-4 border-t">
                    <i data-lucide="log-out" class="w-5 h-5"></i> Keluar
                </a>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 lg:ml-64 p-4 lg:p-10 transition-all duration-300">

            <!-- HEADER MOBILE -->
            <div class="lg:hidden flex items-center justify-between bg-white p-4 rounded-2xl shadow-sm border mb-8 sticky top-0 z-20">
                <button onclick="toggleSidebar()" class="text-gray-700 p-2 hover:bg-gray-100 rounded-xl transition">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
                <h1 class="font-black text-<?= $theme ?>-900">Pengaturan Profil</h1>
                <div class="w-9 h-9 <?= $bg_soft ?> rounded-full border border-<?= $theme ?>-200"></div>
            </div>

            <div class="max-w-4xl mx-auto">

                <!-- INFORMASI USER HEADER (REPLACES BANNER) -->
                <div class="flex flex-col md:flex-row items-center gap-8 mb-12">
                    <div class="relative group">
                        <div class="w-32 h-32 md:w-40 md:h-40 bg-gradient-to-tr from-<?= $theme ?>-600 to-indigo-600 rounded-[2.5rem] flex items-center justify-center text-white shadow-2xl shadow-<?= $theme ?>-200 transform group-hover:rotate-6 transition-transform duration-500">
                            <i data-lucide="<?= ($role == 'PENERBIT') ? 'pen-tool' : 'user' ?>" class="w-16 h-16 md:w-20 md:h-20"></i>
                        </div>
                    </div>

                    <div class="text-center md:text-left flex-1">
                        <div class="inline-flex items-center ml-4 gap-2 px-3 py-1 rounded-full bg-<?= $theme ?>-100 text-<?= $theme ?>-700 text-[10px] font-bold uppercase tracking-widest mb-3">
                            <i data-lucide="shield" class="w-3 h-3"></i> <?= $role ?> Akun
                        </div>
                        <h2 class="text-3xl md:text-4xl font-bold ml-5 text-gray-900 leading-tight mb-2"><?= htmlspecialchars($user['name']) ?></h2>
                        <p class="text-gray-500 font-medium flex items-center ml-5 justify-center md:justify-start gap-2">
                            <i data-lucide="mail" class="w-4 h-4"></i> <?= htmlspecialchars($user['email']) ?>
                        </p>

                        <!-- Quick Stats -->
                        <div class="flex flex-wrap justify-center md:justify-start gap-6 ml-5 mt-6">
                            <div class="flex flex-col">
                                <span class="text-2xl font-bold text-gray-900 leading-none"><?= $count_history ?></span>
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter">Buku Dibaca</span>
                            </div>
                            <div class="w-px h-8 bg-gray-200"></div>
                            <div class="flex flex-col">
                                <span class="text-2xl font-bold text-gray-900 leading-none"><?= $count_saved ?></span>
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter">Dalam Koleksi</span>
                            </div>
                            <?php if ($role == 'PENERBIT'): ?>
                                <div class="w-px h-8 bg-gray-200"></div>
                                <div class="flex flex-col">
                                    <span class="text-2xl font-bold text-gray-900 leading-none">
                                        <?= mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM books WHERE uploaded_by=$id"))['t'] ?>
                                    </span>
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter">Karya Terbit</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- FORM PENGATURAN -->
                <div class="grid grid-cols-1 gap-8">

                    <form method="POST" class="space-y-8">

                        <!-- Section: Data Pribadi -->
                        <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6 lg:p-10 transition-all hover:shadow-md">
                            <div class="flex items-center gap-4 mb-8">
                                <div class="w-12 h-12 bg-<?= $theme ?>-50 rounded-2xl flex items-center justify-center text-<?= $theme ?>-600">
                                    <i data-lucide="user-round" class="w-6 h-6"></i>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-gray-900">Informasi Pribadi</h3>
                                    <p class="text-xs text-gray-400 font-medium">Perbarui data identitas diri Anda di sini.</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label class="text-xs font-bold text-gray-400 uppercase tracking-wider ml-1">Nama Lengkap</label>
                                    <div class="relative">
                                        <i data-lucide="user" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-300"></i>
                                        <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-1 <?= $ring_focus ?> focus:bg-white outline-none transition-all font-medium">
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-xs font-bold text-gray-400 uppercase tracking-wider ml-1">Nomor Induk (NIM/NIP)</label>
                                    <div class="relative">
                                        <i data-lucide="credit-card" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-300"></i>
                                        <input type="text" name="nim" value="<?= htmlspecialchars($user['nim']) ?>" required class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-1 <?= $ring_focus ?> focus:bg-white outline-none transition-all font-medium">
                                    </div>
                                </div>
                                <div class="md:col-span-2 space-y-2">
                                    <label class="text-xs font-bold text-gray-400 uppercase tracking-wider ml-1">Alamat Email</label>
                                    <div class="relative">
                                        <i data-lucide="mail" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-300"></i>
                                        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-1 <?= $ring_focus ?> focus:bg-white outline-none transition-all font-medium">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section: Keamanan Akun -->
                        <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6 lg:p-10 transition-all hover:shadow-md">
                            <div class="flex items-center gap-4 mb-8">
                                <div class="w-12 h-12 bg-red-50 rounded-2xl flex items-center justify-center text-red-500">
                                    <i data-lucide="lock" class="w-6 h-6"></i>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-gray-900">Keamanan & Password</h3>
                                    <p class="text-xs text-gray-400 font-medium">Kosongkan kolom password jika tidak ingin menggantinya.</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label class="text-xs font-bold text-gray-400 uppercase tracking-wider ml-1">Password Saat Ini</label>
                                    <div class="relative">
                                        <i data-lucide="key" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-300"></i>
                                        <input type="password" name="old_password" placeholder="••••••••" class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-1 focus:ring-red-100 focus:bg-white outline-none transition-all font-medium">
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-xs font-bold text-gray-400 uppercase tracking-wider ml-1">Password Baru</label>
                                    <div class="relative">
                                        <i data-lucide="shield-plus" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-300"></i>
                                        <input type="password" name="password" placeholder="Minimal 6 karakter" class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-1 focus:ring-green-100 focus:bg-white outline-none transition-all font-medium">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex items-center justify-end gap-4 pb-10">
                            <a href="<?= $dash_link ?>" class="px-8 py-4 text-gray-400 hover:text-gray-600 font-bold transition-colors">Batal</a>
                            <button type="submit" name="update" class="px-10 py-4 <?= $btn_main ?> text-white rounded-2xl font-black transition-all transform hover:scale-105 flex items-center gap-3 shadow-xl">
                                <i data-lucide="save" class="w-5 h-5"></i> Simpan Perubahan
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();

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
    </script>
</body>

</html>