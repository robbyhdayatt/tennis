<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole(['wasit']);

$conn = getDBConnection();
// Ambil semua match, bukan cuma active
$matches = [];
$stmt = $conn->prepare("SELECT * FROM matches ORDER BY created_at DESC");
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
    <title>Wasit - Tennis Scoreboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <nav class="bg-gradient-to-r from-orange-600 to-red-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <i class="fas fa-whistle text-2xl"></i>
                    <h1 class="text-xl font-bold">Area Wasit</h1>
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
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Daftar Pertandingan</h2>

        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Judul</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Info</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (empty($matches)): ?>
                            <tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">Tidak ada data</td></tr>
                        <?php else: ?>
                            <?php foreach ($matches as $match): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-900"><?= htmlspecialchars($match['match_title']) ?></div>
                                        <div class="text-xs text-gray-500"><?= formatDate($match['match_date']) ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-xs bg-gray-100 px-2 py-1 rounded text-gray-600">
                                            <?= getPlayTypeName($match['play_type']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <?php
                                        $statusClass = [
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'active' => 'bg-green-100 text-green-800 animate-pulse',
                                            'completed' => 'bg-gray-100 text-gray-800',
                                        ];
                                        ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $statusClass[$match['status']] ?? '' ?>">
                                            <?= getMatchStatus($match['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <?php if ($match['status'] === 'active'): ?>
                                            <a href="score-control.php?match_id=<?= $match['id'] ?>" class="inline-flex items-center px-3 py-1 bg-orange-600 hover:bg-orange-700 text-white text-sm font-medium rounded-lg transition-colors">
                                                <i class="fas fa-gamepad mr-2"></i> Kontrol Skor
                                            </a>
                                        <?php elseif ($match['status'] === 'completed'): ?>
                                            <a href="../admin/match-detail.php?id=<?= $match['id'] ?>" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                                <i class="fas fa-eye mr-1"></i> Lihat Hasil
                                            </a>
                                        <?php else: ?>
                                            <span class="text-gray-400 text-xs italic">Menunggu Aktivasi</span>
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