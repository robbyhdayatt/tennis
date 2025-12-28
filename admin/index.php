<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole(['admin']);

$conn = getDBConnection();
$matches = [];
// Mengambil data match beserta nama pembuatnya
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
    <nav class="bg-gradient-to-r from-green-600 to-blue-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <i class="fas fa-table-tennis text-2xl"></i>
                    <h1 class="text-xl font-bold">Tennis Admin</h1>
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

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8 flex flex-col md:flex-row justify-between items-end gap-4">
            <div>
                <h2 class="text-3xl font-bold text-gray-800">Dashboard</h2>
                <p class="text-gray-600">Kelola pertandingan tenis Anda</p>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 w-full md:w-auto">
                <a href="match-settings.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg shadow text-center transition-colors text-sm font-semibold flex items-center justify-center">
                    <i class="fas fa-plus mr-2"></i>Match
                </a>
                <a href="players.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow text-center transition-colors text-sm font-semibold flex items-center justify-center">
                    <i class="fas fa-users mr-2"></i>Pemain
                </a>
                <a href="sponsors.php" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg shadow text-center transition-colors text-sm font-semibold flex items-center justify-center">
                    <i class="fas fa-ad mr-2"></i>Sponsor
                </a>
                <a href="../scoreboard.php" target="_blank" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg shadow text-center transition-colors text-sm font-semibold flex items-center justify-center">
                    <i class="fas fa-tv mr-2"></i>Board
                </a>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Pertandingan</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Info</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (empty($matches)): ?>
                            <tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">Belum ada pertandingan</td></tr>
                        <?php else: ?>
                            <?php foreach ($matches as $match): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-900"><?= htmlspecialchars($match['match_title']) ?></div>
                                        <div class="text-xs text-gray-500"><?= formatDate($match['match_date']) ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <?= getPlayTypeName($match['play_type']) ?>
                                        </span>
                                        <span class="text-xs text-gray-500 ml-2 block mt-1">
                                            <?= $match['number_of_sets'] ?> Set 
                                            (<?= $match['game_per_set_type'] == 'Normal' ? 'To ' . $match['game_per_set_value'] : 'BestOf ' . $match['game_per_set_value'] ?>)
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <?php
                                        $statusClass = [
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'active' => 'bg-green-100 text-green-800 animate-pulse',
                                            'completed' => 'bg-gray-100 text-gray-800',
                                            'cancelled' => 'bg-red-100 text-red-800'
                                        ];
                                        ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $statusClass[$match['status']] ?? '' ?>">
                                            <?= getMatchStatus($match['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right whitespace-nowrap space-x-2">
                                        <a href="match-detail.php?id=<?= $match['id'] ?>" class="text-blue-600 hover:text-blue-900 font-medium text-sm" title="Lihat Detail">
                                            <i class="fas fa-info-circle"></i>
                                        </a>

                                        <?php if ($match['status'] === 'pending'): ?>
                                            <a href="match-edit.php?id=<?= $match['id'] ?>" class="text-gray-600 hover:text-gray-900 font-medium text-sm" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="match-activate.php?id=<?= $match['id'] ?>" class="text-green-600 hover:text-green-900 font-medium text-sm" onclick="return confirm('Aktifkan pertandingan ini?')" title="Mulai Pertandingan">
                                                <i class="fas fa-play"></i>
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($match['status'] === 'active'): ?>
                                            <a href="../scoreboard.php?match_id=<?= $match['id'] ?>" target="_blank" class="text-purple-600 hover:text-purple-900 font-medium text-sm" title="Buka Scoreboard">
                                                <i class="fas fa-tv"></i>
                                            </a>
                                        <?php endif; ?>

                                        <a href="match-delete.php?id=<?= $match['id'] ?>" 
                                           class="text-red-500 hover:text-red-700 font-medium text-sm ml-2" 
                                           onclick="return confirm('PERINGATAN: Menghapus pertandingan ini akan menghapus semua riwayat skor, set, dan game secara permanen. Lanjutkan?')" 
                                           title="Hapus Pertandingan">
                                            <i class="fas fa-trash"></i>
                                        </a>
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