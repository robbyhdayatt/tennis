<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

$matchId = intval($_GET['match_id'] ?? 0);
if ($matchId === 0) {
    // Get first active match
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id FROM matches WHERE status = 'active' ORDER BY updated_at DESC LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $matchId = $row['id'];
    } else {
        die('Tidak ada pertandingan aktif');
    }
}

// Get match (can be active or completed)
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT * FROM matches WHERE id = ?");
$stmt->bind_param("i", $matchId);
$stmt->execute();
$result = $stmt->get_result();
$match = $result->fetch_assoc();

if (!$match) {
    die('Pertandingan tidak ditemukan');
}

$matchPlayers = getMatchPlayers($matchId);
$allSets = getAllSets($matchId);
$currentSet = getCurrentSet($matchId);
$currentGame = null;
if ($currentSet) {
    $currentGame = getCurrentGame($matchId, $currentSet['id']);
}

// Check if match is completed
$isCompleted = ($match['status'] === 'completed');
$winner = null;
$finalScore = null;
if ($isCompleted) {
    $winner = getMatchWinner($matchId);
    $finalScore = getFinalScore($matchId);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scoreboard - <?= htmlspecialchars($match['match_title']) ?></title>
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
        body {
            overflow: hidden;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-900 via-gray-800 to-black min-h-screen">
    <!-- Fullscreen Scoreboard -->
    <div class="h-screen flex flex-col">
        <!-- Header -->
        <div class="bg-black bg-opacity-50 backdrop-blur-sm text-white p-6">
            <div class="max-w-7xl mx-auto">
                <div class="flex justify-between items-center">
                    <div class="flex-1">
                        <h1 class="text-4xl font-bold mb-2"><?= htmlspecialchars($match['match_title']) ?></h1>
                        <p class="text-gray-300 text-lg">
                            <?= formatDate($match['match_date']) ?> • <?= getPlayTypeName($match['play_type']) ?>
                        </p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="text-right">
                            <?php if ($isCompleted): ?>
                                <span class="text-2xl font-bold text-green-500">
                                    <i class="fas fa-flag-checkered"></i> SELESAI
                                </span>
                            <?php else: ?>
                                <span class="text-2xl font-bold text-red-500 live-indicator">
                                    <i class="fas fa-circle"></i> LIVE
                                </span>
                            <?php endif; ?>
                        </div>
                        <a href="index.php" class="bg-blue-600 hover:bg-blue-700 px-6 py-3 rounded-lg font-semibold transition-colors">
                            <i class="fas fa-home mr-2"></i>Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Score Display -->
        <div class="flex-1 flex items-center justify-center p-8">
            <div class="w-full max-w-7xl">
                <?php if ($isCompleted && $winner): ?>
                    <!-- Winner Display -->
                    <div class="text-center mb-8">
                        <div class="bg-gradient-to-r from-yellow-400 to-yellow-600 rounded-2xl p-8 mb-6 shadow-2xl">
                            <i class="fas fa-trophy text-6xl text-white mb-4"></i>
                            <h2 class="text-5xl font-bold text-white mb-4">PEMENANG</h2>
                            <div class="bg-white rounded-xl p-6">
                                <p class="text-4xl font-bold <?= $winner === 'team1' ? 'text-blue-600' : 'text-red-600' ?> mb-2">
                                    <?php if ($winner === 'team1'): ?>
                                        <?php foreach ($matchPlayers['team1'] as $p): ?>
                                            <?= htmlspecialchars($p['name']) ?><?= $p !== end($matchPlayers['team1']) ? ' / ' : '' ?>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <?php foreach ($matchPlayers['team2'] as $p): ?>
                                            <?= htmlspecialchars($p['name']) ?><?= $p !== end($matchPlayers['team2']) ? ' / ' : '' ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </p>
                                <p class="text-2xl text-gray-600">
                                    Skor Final: <?= $finalScore['team1_sets'] ?> - <?= $finalScore['team2_sets'] ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Teams -->
                <div class="grid grid-cols-2 gap-8 mb-8">
                    <!-- Team 1 -->
                    <div class="bg-blue-600 bg-opacity-20 backdrop-blur-sm rounded-2xl p-8 border-4 <?= ($isCompleted && $winner === 'team1') ? 'border-yellow-500 ring-4 ring-yellow-400' : 'border-blue-500' ?>">
                        <h2 class="text-3xl font-bold text-blue-300 mb-4 text-center">TIM 1</h2>
                        <?php if ($isCompleted && $winner === 'team1'): ?>
                            <div class="text-center mb-4">
                                <span class="bg-yellow-400 text-yellow-900 px-4 py-2 rounded-full text-lg font-bold">
                                    <i class="fas fa-trophy mr-2"></i>PEMENANG
                                </span>
                            </div>
                        <?php endif; ?>
                        <div class="text-center mb-6">
                            <?php foreach ($matchPlayers['team1'] as $p): ?>
                                <p class="text-2xl font-semibold text-white mb-2">
                                    <?= htmlspecialchars($p['name']) ?>
                                </p>
                            <?php endforeach; ?>
                        </div>
                        <div class="bg-white rounded-xl p-6 text-center">
                            <?php if ($isCompleted): ?>
                                <p class="text-6xl font-bold text-blue-600 mb-2">
                                    <?= $finalScore['team1_sets'] ?>
                                </p>
                                <p class="text-xl text-gray-600">Set</p>
                            <?php else: ?>
                                <p class="text-8xl font-bold text-blue-600" id="team1_score">
                                    <?= $currentGame ? getTennisScore($currentGame['team1_points']) : '0' ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Team 2 -->
                    <div class="bg-red-600 bg-opacity-20 backdrop-blur-sm rounded-2xl p-8 border-4 <?= ($isCompleted && $winner === 'team2') ? 'border-yellow-500 ring-4 ring-yellow-400' : 'border-red-500' ?>">
                        <h2 class="text-3xl font-bold text-red-300 mb-4 text-center">TIM 2</h2>
                        <?php if ($isCompleted && $winner === 'team2'): ?>
                            <div class="text-center mb-4">
                                <span class="bg-yellow-400 text-yellow-900 px-4 py-2 rounded-full text-lg font-bold">
                                    <i class="fas fa-trophy mr-2"></i>PEMENANG
                                </span>
                            </div>
                        <?php endif; ?>
                        <div class="text-center mb-6">
                            <?php foreach ($matchPlayers['team2'] as $p): ?>
                                <p class="text-2xl font-semibold text-white mb-2">
                                    <?= htmlspecialchars($p['name']) ?>
                                </p>
                            <?php endforeach; ?>
                        </div>
                        <div class="bg-white rounded-xl p-6 text-center">
                            <?php if ($isCompleted): ?>
                                <p class="text-6xl font-bold text-red-600 mb-2">
                                    <?= $finalScore['team2_sets'] ?>
                                </p>
                                <p class="text-xl text-gray-600">Set</p>
                            <?php else: ?>
                                <p class="text-8xl font-bold text-red-600" id="team2_score">
                                    <?= $currentGame ? getTennisScore($currentGame['team2_points']) : '0' ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Sets -->
                <div class="bg-black bg-opacity-50 backdrop-blur-sm rounded-2xl p-6">
                    <h3 class="text-2xl font-bold text-white mb-4 text-center">SET</h3>
                    <div class="grid grid-cols-<?= $match['number_of_sets'] ?> gap-4" id="sets_container">
                        <?php for ($i = 1; $i <= $match['number_of_sets']; $i++): 
                            $set = null;
                            foreach ($allSets as $s) {
                                if ($s['set_number'] == $i) {
                                    $set = $s;
                                    break;
                                }
                            }
                        ?>
                            <div class="text-center p-4 rounded-xl <?= $set && $set['status'] === 'in_progress' ? 'bg-green-500 bg-opacity-30 border-2 border-green-400' : ($set ? 'bg-gray-700 bg-opacity-50' : 'bg-gray-800 bg-opacity-50') ?>">
                                <p class="text-lg text-gray-300 mb-2">Set <?= $i ?></p>
                                <?php if ($set): ?>
                                    <div class="flex justify-around items-center">
                                        <span class="text-5xl font-bold text-blue-300"><?= $set['team1_games'] ?></span>
                                        <span class="text-4xl font-bold text-gray-400">-</span>
                                        <span class="text-5xl font-bold text-red-300"><?= $set['team2_games'] ?></span>
                                    </div>
                                <?php else: ?>
                                    <div class="flex justify-around items-center">
                                        <span class="text-5xl font-bold text-gray-600">0</span>
                                        <span class="text-4xl font-bold text-gray-600">-</span>
                                        <span class="text-5xl font-bold text-gray-600">0</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const matchId = <?= $matchId ?>;
        const numberOfSets = <?= $match['number_of_sets'] ?>;
        
        function updateScoreboard() {
            fetch(`api/get-score.php?match_id=${matchId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update sets
                        const setsContainer = document.getElementById('sets_container');
                        if (setsContainer) {
                            setsContainer.innerHTML = '';
                            for (let i = 1; i <= numberOfSets; i++) {
                                const set = data.sets.find(s => s.set_number === i);
                                const isActive = set && set.status === 'in_progress';
                                const bgClass = isActive ? 'bg-green-500 bg-opacity-30 border-2 border-green-400' : 
                                               (set ? 'bg-gray-700 bg-opacity-50' : 'bg-gray-800 bg-opacity-50');
                                
                                setsContainer.innerHTML += `
                                    <div class="text-center p-4 rounded-xl ${bgClass}">
                                        <p class="text-lg text-gray-300 mb-2">Set ${i}</p>
                                        <div class="flex justify-around items-center">
                                            <span class="text-5xl font-bold text-blue-300">${set ? set.team1_games : 0}</span>
                                            <span class="text-4xl font-bold text-gray-400">-</span>
                                            <span class="text-5xl font-bold text-red-300">${set ? set.team2_games : 0}</span>
                                        </div>
                                    </div>
                                `;
                            }
                        }
                        
                        // Update current game
                        if (data.current_game) {
                            const team1El = document.getElementById('team1_score');
                            const team2El = document.getElementById('team2_score');
                            if (team1El) team1El.textContent = data.current_game.team1_score;
                            if (team2El) team2El.textContent = data.current_game.team2_score;
                            
                            // Add animation
                            if (team1El) {
                                team1El.style.transform = 'scale(1.1)';
                                setTimeout(() => team1El.style.transform = 'scale(1)', 300);
                            }
                            if (team2El) {
                                team2El.style.transform = 'scale(1.1)';
                                setTimeout(() => team2El.style.transform = 'scale(1)', 300);
                            }
                        }
                    }
                })
                .catch(error => {
                    console.error('Error updating scoreboard:', error);
                });
        }
        
        // Update every 1 second for real-time feel
        setInterval(updateScoreboard, 1000);
        
        // Initial load
        updateScoreboard();
        
        // Fullscreen on load
        window.addEventListener('load', () => {
            if (document.documentElement.requestFullscreen) {
                document.documentElement.requestFullscreen().catch(err => {
                    console.log('Fullscreen not available');
                });
            }
        });
    </script>
</body>
</html>

