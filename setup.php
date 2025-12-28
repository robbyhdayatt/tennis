<?php
/**
 * Setup Script untuk Tennis Scoreboard
 * Jalankan file ini sekali setelah instalasi untuk verifikasi setup
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Verification - Tennis Scoreboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-xl shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">
                <i class="fas fa-cog text-green-600 mr-2"></i>Setup Verification
            </h1>

            <div class="space-y-4">
                <?php
                $checks = [];
                $allPassed = true;

                // Check PHP Version
                $phpVersion = phpversion();
                $phpOk = version_compare($phpVersion, '7.4.0', '>=');
                $checks[] = [
                    'name' => 'PHP Version',
                    'status' => $phpOk,
                    'message' => $phpOk ? "PHP $phpVersion (OK)" : "PHP $phpVersion (Minimal 7.4 diperlukan)"
                ];
                if (!$phpOk) $allPassed = false;

                // Check MySQL Extension
                $mysqlOk = extension_loaded('mysqli');
                $checks[] = [
                    'name' => 'MySQL Extension',
                    'status' => $mysqlOk,
                    'message' => $mysqlOk ? 'MySQLi extension tersedia' : 'MySQLi extension tidak ditemukan'
                ];
                if (!$mysqlOk) $allPassed = false;

                // Check Config Files
                $configOk = file_exists('config/config.php') && file_exists('config/database.php');
                $checks[] = [
                    'name' => 'File Konfigurasi',
                    'status' => $configOk,
                    'message' => $configOk ? 'File config/config.php dan config/database.php ditemukan' : 'File konfigurasi tidak ditemukan'
                ];
                if (!$configOk) $allPassed = false;

                // Check Database Connection
                $dbOk = false;
                $dbMessage = '';
                if ($configOk) {
                    try {
                        require_once 'config/config.php';
                        $conn = getDBConnection();
                        $dbOk = true;
                        $dbMessage = 'Koneksi database berhasil';
                    } catch (Exception $e) {
                        $dbMessage = 'Error: ' . $e->getMessage();
                    }
                } else {
                    $dbMessage = 'File konfigurasi tidak ditemukan';
                }
                $checks[] = [
                    'name' => 'Database Connection',
                    'status' => $dbOk,
                    'message' => $dbMessage
                ];
                if (!$dbOk) $allPassed = false;

                // Check Database Tables
                $tablesOk = false;
                $tablesMessage = '';
                if ($dbOk) {
                    try {
                        $tables = ['users', 'players', 'matches', 'match_players', 'sets', 'games', 'score_history'];
                        $missing = [];
                        foreach ($tables as $table) {
                            $result = $conn->query("SHOW TABLES LIKE '$table'");
                            if ($result->num_rows === 0) {
                                $missing[] = $table;
                            }
                        }
                        if (empty($missing)) {
                            $tablesOk = true;
                            $tablesMessage = 'Semua tabel database ditemukan';
                        } else {
                            $tablesMessage = 'Tabel yang hilang: ' . implode(', ', $missing) . '. Import database/schema.sql';
                        }
                    } catch (Exception $e) {
                        $tablesMessage = 'Error: ' . $e->getMessage();
                    }
                } else {
                    $tablesMessage = 'Database tidak terkoneksi';
                }
                $checks[] = [
                    'name' => 'Database Tables',
                    'status' => $tablesOk,
                    'message' => $tablesMessage
                ];
                if (!$tablesOk) $allPassed = false;

                // Check Default Users
                $usersOk = false;
                $usersMessage = '';
                if ($tablesOk) {
                    try {
                        $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE username IN ('admin', 'wasit', 'penonton')");
                        $row = $result->fetch_assoc();
                        if ($row['count'] == 3) {
                            $usersOk = true;
                            $usersMessage = 'Default users ditemukan (admin, wasit, penonton)';
                        } else {
                            $usersMessage = 'Default users tidak lengkap. Import database/schema.sql';
                        }
                    } catch (Exception $e) {
                        $usersMessage = 'Error: ' . $e->getMessage();
                    }
                } else {
                    $usersMessage = 'Tabel users tidak ditemukan';
                }
                $checks[] = [
                    'name' => 'Default Users',
                    'status' => $usersOk,
                    'message' => $usersMessage
                ];
                if (!$usersOk) $allPassed = false;

                // Check Write Permissions
                $writeOk = is_writable('.');
                $checks[] = [
                    'name' => 'Write Permissions',
                    'status' => $writeOk,
                    'message' => $writeOk ? 'Folder memiliki write permission' : 'Folder tidak memiliki write permission (opsional)'
                ];

                // Display Results
                foreach ($checks as $check) {
                    $icon = $check['status'] ? 'fa-check-circle text-green-500' : 'fa-times-circle text-red-500';
                    $bg = $check['status'] ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200';
                    ?>
                    <div class="border rounded-lg p-4 <?= $bg ?>">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <i class="fas <?= $icon ?> text-xl"></i>
                                <span class="font-semibold text-gray-800"><?= htmlspecialchars($check['name']) ?></span>
                            </div>
                            <span class="text-sm text-gray-600"><?= htmlspecialchars($check['message']) ?></span>
                        </div>
                    </div>
                    <?php
                }
                ?>

                <div class="mt-8 p-6 rounded-lg <?= $allPassed ? 'bg-green-100 border-2 border-green-500' : 'bg-yellow-100 border-2 border-yellow-500' ?>">
                    <?php if ($allPassed): ?>
                        <h2 class="text-2xl font-bold text-green-800 mb-2">
                            <i class="fas fa-check-circle mr-2"></i>Setup Berhasil!
                        </h2>
                        <p class="text-green-700 mb-4">
                            Semua komponen sudah siap. Anda bisa mulai menggunakan sistem.
                        </p>
                        <a href="login.php" class="inline-block bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition-colors">
                            <i class="fas fa-sign-in-alt mr-2"></i>Login Sekarang
                        </a>
                    <?php else: ?>
                        <h2 class="text-2xl font-bold text-yellow-800 mb-2">
                            <i class="fas fa-exclamation-triangle mr-2"></i>Setup Belum Lengkap
                        </h2>
                        <p class="text-yellow-700 mb-4">
                            Beberapa komponen belum siap. Silakan perbaiki item yang ditandai merah di atas.
                        </p>
                        <div class="space-y-2 text-sm text-yellow-800">
                            <p><strong>Langkah yang perlu dilakukan:</strong></p>
                            <ol class="list-decimal list-inside space-y-1 ml-4">
                                <li>Pastikan PHP version >= 7.4</li>
                                <li>Pastikan MySQL extension terinstall</li>
                                <li>Edit config/database.php dengan kredensial MySQL yang benar</li>
                                <li>Import database/schema.sql ke MySQL</li>
                                <li>Refresh halaman ini untuk verifikasi ulang</li>
                            </ol>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mt-6 text-center text-sm text-gray-600">
                    <p><strong>Default Login:</strong></p>
                    <p>Username: admin | Password: password</p>
                    <p class="mt-2 text-xs text-red-600">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        Ganti password default setelah login pertama kali!
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

