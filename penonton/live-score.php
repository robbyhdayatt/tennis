<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole(['penonton']);

$matchId = intval($_GET['match_id'] ?? 0);
if ($matchId === 0) {
    header('Location: index.php');
    exit();
}

$match = getActiveMatch($matchId);
if (!$match) {
    header('Location: index.php');
    exit();
}

$matchPlayers = getMatchPlayers($matchId);
$allSets = getAllSets($matchId);
$currentSet = getCurrentSet($matchId);
$currentGame = null;
if ($currentSet) {
    $currentGame = getCurrentGame($matchId, $currentSet['id']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Score - <?= htmlspecialchars($match['match_title']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .live-indicator {
            animation: pulse 2s infinite;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-black bg-opacity-50 backdrop-blur-sm text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="hover:text-gray-300"><i class="fas fa-arrow-left mr-2"></i>Kembali</a>
                    <h1 class="text-xl font-bold"><?= htmlspecialchars($match['match_title']) ?></h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm">
                        <i class="fas fa-circle text-red-500 live-indicator mr-2"></i>Live
                    </span>
                    <a href="../scoreboard.php?match_id=<?= $matchId ?>" target="_blank" class="bg-purple-500 hover:bg-purple-600 px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-tv mr-2"></i>Fullscreen
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Match Info -->
        <div class="bg-white bg-opacity-10 backdrop-blur-sm rounded-xl shadow-lg p-6 mb-6 text-white">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
                <div>
                    <p class="text-sm text-gray-300 mb-2">Tim 1</p>
                    <p class="font-bold text-xl text-blue-300">
                        <?php foreach ($matchPlayers['team1'] as $p): ?>
                            <?= htmlspecialchars($p['name']) ?><?= $p !== end($matchPlayers['team1']) ? ' / ' : '' ?>
                        <?php endforeach; ?>
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-300 mb-2">VS</p>
                    <p class="font-bold text-lg text-gray-300"><?= getPlayTypeName($match['play_type']) ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-300 mb-2">Tim 2</p>
                    <p class="font-bold text-xl text-red-300">
                        <?php foreach ($matchPlayers['team2'] as $p): ?>
                            <?= htmlspecialchars($p['name']) ?><?= $p !== end($matchPlayers['team2']) ? ' / ' : '' ?>
                        <?php endforeach; ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Sets Display -->
        <div class="bg-white bg-opacity-10 backdrop-blur-sm rounded-xl shadow-lg p-6 mb-6">
            <h3 class="text-white text-lg font-bold mb-4 text-center">Set</h3>
            <div class="grid grid-cols-<?= $match['number_of_sets'] ?> gap-4">
                <?php for ($i = 1; $i <= $match['number_of_sets']; $i++): 
                    $set = null;
                    foreach ($allSets as $s) {
                        if ($s['set_number'] == $i) {
                            $set = $s;
                            break;
                        }
                    }
                ?>
                    <div class="text-center p-4 rounded-lg <?= $set && $set['status'] === 'in_progress' ? 'bg-green-500 bg-opacity-30 border-2 border-green-400' : ($set ? 'bg-gray-700 bg-opacity-50' : 'bg-gray-800 bg-opacity-50') ?>">
                        <p class="text-sm text-gray-300 mb-2">Set <?= $i ?></p>
                        <?php if ($set): ?>
                            <div class="flex justify-around">
                                <span class="text-3xl font-bold text-blue-300"><?= $set['team1_games'] ?></span>
                                <span class="text-3xl font-bold text-gray-400">-</span>
                                <span class="text-3xl font-bold text-red-300"><?= $set['team2_games'] ?></span>
                            </div>
                        <?php else: ?>
                            <div class="flex justify-around">
                                <span class="text-3xl font-bold text-gray-600">0</span>
                                <span class="text-3xl font-bold text-gray-600">-</span>
                                <span class="text-3xl font-bold text-gray-600">0</span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Current Game Score -->
        <div class="bg-gradient-to-r from-green-600 to-blue-600 rounded-xl shadow-2xl p-8 mb-6">
            <h3 class="text-white text-xl font-bold mb-6 text-center">Game Saat Ini</h3>
            <div class="grid grid-cols-2 gap-8">
                <!-- Team 1 -->
                <div class="text-center">
                    <p class="text-white text-sm mb-4 opacity-80">Tim 1</p>
                    <div class="bg-white rounded-lg p-8 mb-4">
                        <p class="text-7xl font-bold text-blue-600" id="team1_score">
                            <?= $currentGame ? getTennisScore($currentGame['team1_points']) : '0' ?>
                        </p>
                    </div>
                </div>

                <!-- Team 2 -->
                <div class="text-center">
                    <p class="text-white text-sm mb-4 opacity-80">Tim 2</p>
                    <div class="bg-white rounded-lg p-8 mb-4">
                        <p class="text-7xl font-bold text-red-600" id="team2_score">
                            <?= $currentGame ? getTennisScore($currentGame['team2_points']) : '0' ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const matchId = <?= $matchId ?>;
        
        function updateScore() {
            fetch(`../api/get-score.php?match_id=${matchId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update sets
                        const setsContainer = document.querySelector('.grid.grid-cols-<?= $match['number_of_sets'] ?>');
                        if (setsContainer) {
                            setsContainer.innerHTML = '';
                            for (let i = 1; i <= data.match.number_of_sets; i++) {
                                const set = data.sets.find(s => s.set_number === i);
                                const isActive = set && set.status === 'in_progress';
                                const bgClass = isActive ? 'bg-green-500 bg-opacity-30 border-2 border-green-400' : 
                                               (set ? 'bg-gray-700 bg-opacity-50' : 'bg-gray-800 bg-opacity-50');
                                
                                setsContainer.innerHTML += `
                                    <div class="text-center p-4 rounded-lg ${bgClass}">
                                        <p class="text-sm text-gray-300 mb-2">Set ${i}</p>
                                        <div class="flex justify-around">
                                            <span class="text-3xl font-bold text-blue-300">${set ? set.team1_games : 0}</span>
                                            <span class="text-3xl font-bold text-gray-400">-</span>
                                            <span class="text-3xl font-bold text-red-300">${set ? set.team2_games : 0}</span>
                                        </div>
                                    </div>
                                `;
                            }
                        }
                        
                        // Update current game
                        if (data.current_game) {
                            document.getElementById('team1_score').textContent = data.current_game.team1_score;
                            document.getElementById('team2_score').textContent = data.current_game.team2_score;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error updating score:', error);
                });
        }
        
        // Update every 2 seconds
        setInterval(updateScore, 2000);
        
        // Initial load
        updateScore();
    </script>
</body>
</html>

