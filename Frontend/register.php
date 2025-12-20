<?php
require_once dirname(__DIR__) . '/Backend/config.php';

$error = '';
$success = '';

if (isset($_POST['register'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $nim = mysqli_real_escape_string($conn, $_POST['nim']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Cek email duplikat
    $check = mysqli_query($conn, "SELECT email FROM users WHERE email = '$email'");
    if (mysqli_num_rows($check) > 0) {
        $error = "Email ini sudah terdaftar di sistem kami.";
    } else {
        $query = "INSERT INTO users (name, email, password, nim, role) VALUES ('$name', '$email', '$password', '$nim', 'USER')";
        if (mysqli_query($conn, $query)) {
            $success = "Registrasi berhasil! Sekarang Anda dapat masuk.";
        } else {
            $error = "Terjadi kesalahan sistem: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - E-Library Digital</title>
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
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4 sm:p-6 relative overflow-x-hidden">
    <!-- Dekorasi Latar Belakang Minimalis -->
    <div class="fixed inset-0 subtle-grid opacity-30 md:opacity-40 -z-10"></div>
    <div class="fixed top-[-10%] right-[-10%] w-[60%] md:w-[40%] h-[40%] bg-blue-50 rounded-full blur-[80px] md:blur-[120px] -z-10"></div>
    <div class="fixed bottom-[-10%] left-[-10%] w-[60%] md:w-[40%] h-[40%] bg-indigo-50 rounded-full blur-[80px] md:blur-[120px] -z-10"></div>

    <div class="w-full max-w-[480px] flex flex-col justify-center py-8">

        <div class="space-y-8">
            <!-- Branding Minimalis -->
            <div class="flex items-center gap-3 justify-center md:justify-start transform scale-90 sm:scale-100">
                <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-200">
                    <i data-lucide="book-marked" class="w-6 h-6 text-white"></i>
                </div>
                <span class="text-xl font-extrabold tracking-tight text-slate-900 uppercase">E-Library</span>
            </div>

            <!-- Header Konten -->
            <div class="text-center md:text-left">
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mb-2">Buat Akun Baru</h1>
                <p class="text-slate-500 font-medium text-sm sm:text-base px-4 md:px-0">Bergabunglah untuk mulai menjelajahi ribuan literasi digital.</p>
            </div>

            <!-- Pesan Feedback -->
            <?php if ($error): ?>
                <div class="mx-2 md:mx-0 flex items-center gap-3 p-4 bg-red-50 border border-red-100 rounded-2xl text-red-600 text-sm font-semibold animate-in fade-in slide-in-from-top-4 duration-300">
                    <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0"></i>
                    <span><?= $error ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="mx-2 md:mx-0 flex items-center gap-3 p-4 bg-green-50 border border-green-100 rounded-2xl text-green-600 text-sm font-semibold animate-in fade-in slide-in-from-top-4 duration-300">
                    <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0"></i>
                    <div class="flex flex-col">
                        <span><?= $success ?></span>
                        <a href="login.php" class="text-xs font-black underline mt-1">Masuk ke Dashboard Sekarang</a>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5 px-2 md:px-0">
                <!-- Nama Lengkap -->
                <div class="space-y-2">
                    <label class="text-sm font-bold text-slate-700 ml-1">Nama Lengkap</label>
                    <div class="relative group">
                        <i data-lucide="user" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-blue-600 transition-colors"></i>
                        <input type="text" name="name" required
                            class="w-full pl-12 pr-4 py-4 bg-white border rounded-2xl outline-none clean-input font-medium text-slate-900 placeholder:text-slate-400 text-base sm:text-sm"
                            placeholder="Nama Lengkap Anda">
                    </div>
                </div>

                <!-- NIM / Identitas -->
                <div class="space-y-2">
                    <label class="text-sm font-bold text-slate-700 ml-1">Nomor Induk (NIM)</label>
                    <div class="relative group">
                        <i data-lucide="id-card" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-blue-600 transition-colors"></i>
                        <input type="text" name="nim" required
                            class="w-full pl-12 pr-4 py-4 bg-white border rounded-2xl outline-none clean-input font-medium text-slate-900 placeholder:text-slate-400 text-base sm:text-sm"
                            placeholder="Contoh: 20210801001">
                    </div>
                </div>

                <!-- Email -->
                <div class="space-y-2">
                    <label class="text-sm font-bold text-slate-700 ml-1">Alamat Email</label>
                    <div class="relative group">
                        <i data-lucide="mail" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-blue-600 transition-colors"></i>
                        <input type="email" name="email" required
                            class="w-full pl-12 pr-4 py-4 bg-white border rounded-2xl outline-none clean-input font-medium text-slate-900 placeholder:text-slate-400 text-base sm:text-sm"
                            placeholder="nama@email.com">
                    </div>
                </div>

                <!-- Password -->
                <div class="space-y-2">
                    <label class="text-sm font-bold text-slate-700 ml-1">Buat Kata Sandi</label>
                    <div class="relative group">
                        <i data-lucide="lock" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-blue-600 transition-colors"></i>
                        <input type="password" name="password" id="passwordField" required
                            class="w-full pl-12 pr-12 py-4 bg-white border rounded-2xl outline-none clean-input font-medium text-slate-900 placeholder:text-slate-400 text-base sm:text-sm"
                            placeholder="••••••••">
                        <button type="button" onclick="togglePasswordVisibility()" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 focus:outline-none transition-colors p-1">
                            <i data-lucide="eye" id="eyeIcon" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>

                <!-- Tombol Submit -->
                <div class="pt-4">
                    <button type="submit" name="register"
                        class="w-full py-4 bg-slate-900 text-white rounded-2xl font-bold transition-all hover:bg-slate-800 active:scale-[0.98] shadow-xl shadow-slate-200 flex items-center justify-center gap-3">
                        <span>Daftar Akun Baru</span>
                        <i data-lucide="user-plus" class="w-5 h-5"></i>
                    </button>
                </div>
            </form>

            <div class="text-center pt-2">
                <p class="text-sm text-slate-500 font-medium">
                    Sudah memiliki akun?
                    <a href="login.php" class="text-blue-600 font-bold hover:underline ml-1">Masuk sekarang</a>
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-16 sm:mt-24 border-t border-slate-100 pt-8 px-4 md:px-0 text-center md:text-left">
            <p class="text-[11px] text-slate-400 font-bold uppercase tracking-widest leading-loose">
                E-Library Digital System &bull; &copy; <?= date('Y') ?> <br class="hidden sm:block">
                Akses Pengetahuan Tanpa Batas
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
            lucide.createIcons();
        }
    </script>
</body>

</html>