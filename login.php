<?php
require_once 'config/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (login($username, $password)) {
        $role = $_SESSION['role'];
        if ($role === 'admin') {
            header('Location: ' . BASE_URL . 'admin/index.php');
        } elseif ($role === 'wasit') {
            header('Location: ' . BASE_URL . 'wasit/index.php');
        } else {
            header('Location: ' . BASE_URL . 'penonton/index.php');
        }
        exit();
    } else {
        $error = 'Username atau password salah!';
    }
}

// Redirect if already logged in
if (isLoggedIn()) {
    $role = $_SESSION['role'];
    if ($role === 'admin') {
        header('Location: ' . BASE_URL . 'admin/index.php');
    } elseif ($role === 'wasit') {
        header('Location: ' . BASE_URL . 'wasit/index.php');
    } else {
        header('Location: ' . BASE_URL . 'penonton/index.php');
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Tennis Scoreboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-green-50 via-blue-50 to-purple-50 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-2xl p-8 backdrop-blur-sm bg-opacity-90">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-green-500 to-blue-600 rounded-full mb-4">
                    <i class="fas fa-table-tennis text-white text-3xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Tennis Scoreboard</h1>
                <p class="text-gray-600">Masuk ke akun Anda</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                    <div class="flex">
                        <i class="fas fa-exclamation-circle mr-2 mt-0.5"></i>
                        <p><?= htmlspecialchars($error) ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-6">
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">
                        <i class="fas fa-user mr-2 text-green-600"></i>Username
                    </label>
                    <input type="text" name="username" required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all outline-none"
                           placeholder="Masukkan username">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">
                        <i class="fas fa-lock mr-2 text-green-600"></i>Password
                    </label>
                    <input type="password" name="password" required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all outline-none"
                           placeholder="Masukkan password">
                </div>

                <button type="submit" 
                        class="w-full bg-gradient-to-r from-green-500 to-blue-600 text-white py-3 rounded-lg font-semibold hover:from-green-600 hover:to-blue-700 transform hover:scale-105 transition-all duration-200 shadow-lg">
                    <i class="fas fa-sign-in-alt mr-2"></i>Masuk
                </button>
            </form>

            <div class="mt-6 pt-6 border-t border-gray-200">
                <p class="text-center text-sm text-gray-600">
                    <strong>Default Login:</strong><br>
                    Admin: admin / password<br>
                    Wasit: wasit / password<br>
                    Penonton: penonton / password
                </p>
            </div>
        </div>
    </div>
</body>
</html>

