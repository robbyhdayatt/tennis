<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole(['admin']);

$conn = getDBConnection();
$matches = [];
$stmt = $conn->prepare("SELECT m.*, u.full_name as created_by_name FROM matches m LEFT JOIN users u ON m.created_by = u.id ORDER BY m.created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $matches[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Tennis Scoreboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-gradient-to-r from-green-600 to-blue-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <i class="fas fa-table-tennis text-2xl"></i>
                    <h1 class="text-xl font-bold">Tennis Scoreboard - Admin</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm"><i class="fas fa-user mr-2"></i><?= htmlspecialchars($_SESSION['full_name']) ?></span>
                    <a href="../logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-800 mb-2">Dashboard Admin</h2>
            <p class="text-gray-600">Kelola pertandingan dan pemain</p>
        </div>

        <!-- Action Buttons -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <a href="match-settings.php" class="bg-gradient-to-r from-green-500 to-green-600 text-white p-6 rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all">
                <i class="fas fa-cog text-3xl mb-3"></i>
                <h3 class="text-xl font-bold mb-2">Pengaturan Match</h3>
                <p class="text-green-100">Buat dan atur pertandingan baru</p>
            </a>
            <a href="players.php" class="bg-gradient-to-r from-blue-500 to-blue-600 text-white p-6 rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all">
                <i class="fas fa-users text-3xl mb-3"></i>
                <h3 class="text-xl font-bold mb-2">Kelola Pemain</h3>
                <p class="text-blue-100">Tambah dan kelola data pemain</p>
            </a>
            <a href="../scoreboard.php" target="_blank" class="bg-gradient-to-r from-purple-500 to-purple-600 text-white p-6 rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all">
                <i class="fas fa-tv text-3xl mb-3"></i>
                <h3 class="text-xl font-bold mb-2">Scoreboard</h3>
                <p class="text-purple-100">Lihat tampilan scoreboard</p>
            </a>
        </div>

        <!-- Matches List -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-gray-800 to-gray-900 text-white">
                <h3 class="text-xl font-bold"><i class="fas fa-list mr-2"></i>Daftar Pertandingan</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Judul</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Tanggal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Tipe</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Set</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($matches)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-3 block"></i>
                                    Belum ada pertandingan. <a href="match-settings.php" class="text-green-600 hover:underline">Buat pertandingan baru</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($matches as $match): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($match['match_title']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-600"><?= formatDate($match['match_date']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $match['play_type'] === 'Single' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' ?>">
                                            <?= getPlayTypeName($match['play_type']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-600"><?= $match['number_of_sets'] ?> Set</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $statusColors = [
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'active' => 'bg-green-100 text-green-800',
                                            'completed' => 'bg-gray-100 text-gray-800',
                                            'cancelled' => 'bg-red-100 text-red-800'
                                        ];
                                        $color = $statusColors[$match['status']] ?? 'bg-gray-100 text-gray-800';
                                        ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $color ?>">
                                            <?= getMatchStatus($match['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="match-edit.php?id=<?= $match['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <?php if ($match['status'] === 'pending'): ?>
                                            <a href="match-activate.php?id=<?= $match['id'] ?>" class="text-green-600 hover:text-green-900" onclick="return confirm('Aktifkan pertandingan ini?')">
                                                <i class="fas fa-play"></i> Aktifkan
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>

