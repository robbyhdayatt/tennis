<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole(['penonton']);

$conn = getDBConnection();

// Get active matches
$activeMatches = [];
$stmt = $conn->prepare("SELECT * FROM matches WHERE status = 'active' ORDER BY updated_at DESC");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $activeMatches[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penonton - Tennis Scoreboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-gradient-to-r from-blue-600 to-purple-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <i class="fas fa-eye text-2xl"></i>
                    <h1 class="text-xl font-bold">Penonton - Live Score</h1>
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
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-800 mb-2">Pertandingan Aktif</h2>
            <p class="text-gray-600">Pilih pertandingan untuk melihat skor live</p>
        </div>

        <?php if (empty($activeMatches)): ?>
            <div class="bg-white rounded-xl shadow-lg p-12 text-center">
                <i class="fas fa-inbox text-6xl text-gray-400 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">Tidak Ada Pertandingan Aktif</h3>
                <p class="text-gray-500">Tunggu admin mengaktifkan pertandingan.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($activeMatches as $match): ?>
                    <a href="live-score.php?match_id=<?= $match['id'] ?>" 
                       class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transform hover:scale-105 transition-all border-l-4 border-blue-500">
                        <div class="flex items-start justify-between mb-4">
                            <h3 class="text-lg font-bold text-gray-800"><?= htmlspecialchars($match['match_title']) ?></h3>
                            <span class="bg-green-100 text-green-800 text-xs font-semibold px-2 py-1 rounded-full">
                                <i class="fas fa-circle text-xs animate-pulse"></i> Live
                            </span>
                        </div>
                        <div class="space-y-2 text-sm text-gray-600">
                            <p><i class="fas fa-calendar mr-2"></i><?= formatDate($match['match_date']) ?></p>
                            <p><i class="fas fa-trophy mr-2"></i><?= getPlayTypeName($match['play_type']) ?> - <?= $match['number_of_sets'] ?> Set</p>
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <span class="text-blue-600 font-semibold">
                                Lihat Skor <i class="fas fa-arrow-right ml-2"></i>
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

