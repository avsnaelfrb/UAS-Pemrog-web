<?php
require_once dirname(__DIR__) . '/Backend/config.php';

// Jika sudah ada session, langsung arahkan ke halaman yang sesuai
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    if ($role == 'ADMIN') {
        header("Location: dashboard-admin.php");
    } else {
        header("Location: home.php");
    }
    exit;
}

$error = '';

if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        if (password_verify($password, $user['password'])) {
            // Set Session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = strtoupper($user['role']);

            // Redirect sesuai role
            if ($_SESSION['role'] == 'ADMIN') {
                header("Location: dashboard-admin.php");
            } else {
                header("Location: home.php");
            }
            exit;
        } else {
            $error = "Kata sandi salah. Silakan coba lagi.";
        }
    } else {
        $error = "Email tidak ditemukan.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - E-Library Digital</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #ffffff;
        }

        .clean-input {
            transition: all 0.2s ease-in-out;
            border-color: #e5e7eb;
        }

        .clean-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.05);
        }

        .subtle-grid {
            background-image: radial-gradient(#e5e7eb 1px, transparent 1px);
            background-size: 40px 40px;
        }

        /* Animasi halus untuk transisi password */
        #passwordField {
            transition: all 0.3s ease;
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4 sm:p-6 relative overflow-x-hidden">
    <!-- Dekorasi Latar Belakang Minimalis yang Responsif -->
    <div class="fixed inset-0 subtle-grid opacity-30 md:opacity-40 -z-10"></div>
    <div class="fixed top-[-10%] right-[-10%] w-[60%] md:w-[40%] h-[40%] bg-blue-50 rounded-full blur-[80px] md:blur-[120px] -z-10"></div>
    <div class="fixed bottom-[-10%] left-[-10%] w-[60%] md:w-[40%] h-[40%] bg-indigo-50 rounded-full blur-[80px] md:blur-[120px] -z-10"></div>

    <div class="w-full max-w-[440px] flex flex-col min-h-[80vh] sm:min-h-0 justify-between py-8">

        <div class="space-y-10">
            <!-- Branding Minimalis - Terpusat di Mobile -->
            <div class="flex items-center gap-3 justify-center md:justify-start transform scale-90 sm:scale-100">
                <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-200">
                    <i data-lucide="book-marked" class="w-6 h-6 text-white"></i>
                </div>
                <span class="text-xl font-extrabold tracking-tight text-slate-900 uppercase">E-Library</span>
            </div>

            <!-- Header Konten -->
            <div class="text-center md:text-left">
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mb-2">Selamat Datang</h1>
                <p class="text-slate-500 font-medium text-sm sm:text-base px-4 md:px-0">Masuk untuk melanjutkan ke perpustakaan digital Anda.</p>
            </div>

            <!-- Pesan Error yang Elegan -->
            <?php if ($error): ?>
                <div class="mx-2 md:mx-0 flex items-center gap-3 p-4 bg-red-50 border border-red-100 rounded-2xl text-red-600 text-sm font-semibold animate-in fade-in slide-in-from-top-4 duration-300">
                    <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0"></i>
                    <span><?= $error ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5 px-2 md:px-0">
                <!-- Field Email -->
                <div class="space-y-2">
                    <label class="text-sm font-bold text-slate-700 ml-1">Email</label>
                    <div class="relative group">
                        <i data-lucide="mail" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-blue-600 transition-colors"></i>
                        <input type="email" name="email" required
                            class="w-full pl-12 pr-4 py-4 bg-white border rounded-2xl outline-none clean-input font-medium text-slate-900 placeholder:text-slate-400 text-base sm:text-sm"
                            placeholder="nama@email.com">
                    </div>
                </div>

                <!-- Field Password -->
                <div class="space-y-2">
                    <div class="flex justify-between items-center ml-1">
                        <label class="text-sm font-bold text-slate-700">Kata Sandi</label>
                        <a href="#" class="text-xs font-bold text-blue-600 hover:text-blue-700 transition-colors">Lupa sandi?</a>
                    </div>
                    <div class="relative group">
                        <i data-lucide="lock" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-blue-600 transition-colors"></i>
                        <input type="password" name="password" id="passwordField" required
                            class="w-full pl-12 pr-12 py-4 bg-white border rounded-2xl outline-none clean-input font-medium text-slate-900 placeholder:text-slate-400 text-base sm:text-sm"
                            placeholder="••••••••">
                        <!-- Tombol Toggle Password -->
                        <button type="button" onclick="togglePasswordVisibility()" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 focus:outline-none transition-colors p-1">
                            <i data-lucide="eye" id="eyeIcon" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>

                <!-- Tombol Submit - Lebih Besar di Mobile untuk memudahkan Tap -->
                <div class="pt-4">
                    <button type="submit" name="login"
                        class="w-full py-4 bg-slate-900 text-white rounded-2xl font-bold transition-all hover:bg-slate-800 active:scale-[0.98] shadow-xl shadow-slate-200 flex items-center justify-center gap-3">
                        <span>Masuk ke Akun</span>
                        <i data-lucide="arrow-right" class="w-5 h-5"></i>
                    </button>
                </div>
            </form>

            <div class="text-center pt-2">
                <p class="text-sm text-slate-500 font-medium">
                    Belum punya akun?
                    <a href="register.php" class="text-blue-600 font-bold hover:underline ml-1">Daftar sekarang</a>
                </p>
            </div>
        </div>

        <!-- Footer yang Menyesuaikan Layout -->
        <div class="mt-auto sm:mt-20 border-t border-slate-100 pt-8 px-4 md:px-0 text-center md:text-left">
            <p class="text-[11px] text-slate-400 font-bold uppercase tracking-widest leading-loose">
                E-Library Digital System &bull; &copy; <?= date('Y') ?> <br class="hidden sm:block">
                Literasi Digital untuk Semua
            </p>
        </div>
    </div>

    <script>
        // Inisialisasi Ikon Lucide
        lucide.createIcons();

        function togglePasswordVisibility() {
            const passwordField = document.getElementById('passwordField');
            const eyeIcon = document.getElementById('eyeIcon');

            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                eyeIcon.setAttribute('data-lucide', 'eye-off');
            } else {
                passwordField.type = 'password';
                eyeIcon.setAttribute('data-lucide', 'eye');
            }
            // Render ulang hanya ikon yang berubah
            lucide.createIcons();
        }
    </script>
</body>

</html>