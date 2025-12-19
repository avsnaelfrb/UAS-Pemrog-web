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
        $error = "Email sudah terdaftar!";
    } else {
        $query = "INSERT INTO users (name, email, password, nim, role) VALUES ('$name', '$email', '$password', '$nim', 'USER')";
        if (mysqli_query($conn, $query)) {
            $success = "Registrasi berhasil! Silakan login.";
        } else {
            $error = "Terjadi kesalahan: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - E-Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center p-8">
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden max-w-4xl w-full flex flex-col md:flex-row">
        <!-- Form Section -->
        <div class="w-full md:w-1/2 p-8">
            <h2 class="text-3xl font-bold text-gray-900 mb-2">Buat Akun Baru</h2>
            <p class="text-gray-600 mb-6">Daftar untuk memulai membaca</p>

            <?php if ($error): ?>
                <div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
                    <?php echo $success; ?> <a href="login.php" class="underline font-bold">Login disini</a>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                    <input type="text" name="name" required class="mt-1 w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">NIM</label>
                    <input type="text" name="nim" required class="mt-1 w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" name="email" required class="mt-1 w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" name="password" required class="mt-1 w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <button type="submit" name="register" class="w-full bg-blue-900 text-white py-3 rounded-lg font-medium hover:bg-blue-800 transition">Daftar</button>
            </form>
            <p class="mt-6 text-center text-sm text-gray-600">
                Sudah punya akun? <a href="login.php" class="text-blue-900 font-bold">Masuk sekarang</a>
            </p>
        </div>

        <!-- Illustration Section -->
        <div class="hidden md:flex md:w-1/2 bg-blue-900 items-center justify-center p-12 text-white text-center">
            <div>
                <svg class="w-32 h-32 mx-auto mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                <h3 class="text-2xl font-bold mb-2">Perpustakaan Digital</h3>
                <p class="text-blue-100">Akses ribuan buku kapan saja dan di mana saja.</p>
            </div>
        </div>
    </div>
</body>

</html>