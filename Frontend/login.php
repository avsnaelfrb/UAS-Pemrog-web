<?php
require_once dirname(__DIR__) . '/Backend/config.php';

// Jika sudah ada session, langsung ke dashboard
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    if ($role == 'ADMIN') redirect('dashboard-admin.php');
    else if ($role == 'PENERBIT') redirect('dashboard-publisher.php');
    else redirect('dashboard-user.php');
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
            // Set Session Standar
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = strtoupper($user['role']);

            // Redirect sesuai role
            if ($_SESSION['role'] == 'ADMIN') redirect('dashboard-admin.php');
            else if ($_SESSION['role'] == 'PENERBIT') redirect('dashboard-publisher.php');
            else redirect('dashboard-user.php');
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Email tidak ditemukan!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - E-Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-2xl shadow-lg w-full max-w-md">
        <div class="text-center mb-6">
            <h2 class="text-3xl font-bold text-blue-900">E-Library</h2>
            <p class="text-gray-500">Silakan masuk ke akun Anda</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 text-red-600 p-3 rounded-lg mb-4 text-sm border border-red-200">
                ⚠️ <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" name="password" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <button type="submit" name="login" class="w-full bg-blue-900 text-white py-2 rounded-lg font-bold hover:bg-blue-800 transition">Masuk</button>
        </form>
        <p class="mt-6 text-center text-sm text-gray-600">
            Belum punya akun? <a href="register.php" class="text-blue-700 font-bold">Daftar</a>
        </p>
    </div>
</body>

</html>