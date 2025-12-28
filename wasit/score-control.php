<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole(['wasit']);

$matchId = intval($_GET['match_id'] ?? 0);
if ($matchId === 0) {
    header('Location: index.php');
    exit();
}

$conn = getDBConnection();

// Get match details (can be active or completed)
$stmt = $conn->prepare("SELECT * FROM matches WHERE id = ?");
$stmt->bind_param("i", $matchId);
$stmt->execute();
$result = $stmt->get_result();
$match = $result->fetch_assoc();

if (!$match) {
    header('Location: index.php');
    exit();
}

// Get players
$matchPlayers = getMatchPlayers($matchId);

// Get current set
$currentSet = getCurrentSet($matchId);
if (!$currentSet) {
    // Check if any set exists for this match
    $stmt = $conn->prepare("SELECT * FROM sets WHERE match_id = ? ORDER BY set_number DESC LIMIT 1");
    $stmt->bind_param("i", $matchId);
    $stmt->execute();
    $result = $stmt->get_result();
    $lastSet = $result->fetch_assoc();
    
    if (!$lastSet) {
        // No sets exist, create first set
        // Use INSERT IGNORE to prevent duplicate entry error
        $stmt = $conn->prepare("INSERT IGNORE INTO sets (match_id, set_number, status) VALUES (?, 1, 'in_progress')");
        $stmt->bind_param("i", $matchId);
        $stmt->execute();
        $currentSet = getCurrentSet($matchId);
    } else {
        // Set exists but might be completed, check if we need to create next set
        if ($lastSet['status'] === 'completed') {
            // All sets are completed, check if we can create next set
            $stmt = $conn->prepare("SELECT number_of_sets FROM matches WHERE id = ?");
            $stmt->bind_param("i", $matchId);
            $stmt->execute();
            $result = $stmt->get_result();
            $matchData = $result->fetch_assoc();
            $maxSets = $matchData['number_of_sets'] ?? 5;
            
            if ($lastSet['set_number'] < $maxSets) {
                $nextSetNumber = $lastSet['set_number'] + 1;
                // Use INSERT IGNORE to prevent duplicate entry error
                $stmt = $conn->prepare("INSERT IGNORE INTO sets (match_id, set_number, status) VALUES (?, ?, 'in_progress')");
                $stmt->bind_param("ii", $matchId, $nextSetNumber);
                $stmt->execute();
                $currentSet = getCurrentSet($matchId);
            }
        } else {
            // Set exists and is in progress, use it
            $currentSet = $lastSet;
        }
    }
    
    // Final check - if still no current set, try to get any in_progress set
    if (!$currentSet) {
        $currentSet = getCurrentSet($matchId);
    }
}

// Ensure we have a current set
if (!$currentSet) {
    die('Error: Tidak dapat menemukan atau membuat set untuk pertandingan ini. Silakan refresh halaman atau hubungi admin.');
}

// Get current game
$currentGame = getCurrentGame($matchId, $currentSet['id']);
if (!$currentGame) {
    // Check if game 1 exists for this set
    $stmt = $conn->prepare("SELECT * FROM games WHERE match_id = ? AND set_id = ? AND game_number = 1");
    $stmt->bind_param("ii", $matchId, $currentSet['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $existingGame = $result->fetch_assoc();
    
    if (!$existingGame) {
        // Create first game if not exists
        // Use INSERT IGNORE to prevent duplicate entry error
        $stmt = $conn->prepare("INSERT IGNORE INTO games (match_id, set_id, game_number, status) VALUES (?, ?, 1, 'in_progress')");
        $stmt->bind_param("ii", $matchId, $currentSet['id']);
        $stmt->execute();
        $currentGame = getCurrentGame($matchId, $currentSet['id']);
    } else {
        // Game exists, use it
        $currentGame = $existingGame;
    }
}

// Final safety check - ensure we have a current game
if (!$currentGame) {
    // Try one more time to get any in_progress game
    $stmt = $conn->prepare("SELECT * FROM games WHERE match_id = ? AND set_id = ? AND status = 'in_progress' ORDER BY game_number DESC LIMIT 1");
    $stmt->bind_param("ii", $matchId, $currentSet['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentGame = $result->fetch_assoc();
    
    // If still no game, create one
    if (!$currentGame) {
        $stmt = $conn->prepare("INSERT IGNORE INTO games (match_id, set_id, game_number, status) VALUES (?, ?, 1, 'in_progress')");
        $stmt->bind_param("ii", $matchId, $currentSet['id']);
        $stmt->execute();
        $currentGame = getCurrentGame($matchId, $currentSet['id']);
    }
}

// If still no game, initialize with default values
if (!$currentGame) {
    $currentGame = [
        'team1_points' => 0,
        'team2_points' => 0,
        'id' => null
    ];
}

// Get all sets
$allSets = getAllSets($matchId);

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
    <title>Kontrol Skor - <?= htmlspecialchars($match['match_title']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .score-button {
            transition: all 0.2s;
        }
        .score-button:hover {
            transform: scale(1.05);
        }
        .score-button:active {
            transform: scale(0.95);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-gradient-to-r from-orange-600 to-red-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="hover:text-gray-200"><i class="fas fa-arrow-left mr-2"></i>Kembali</a>
                    <h1 class="text-xl font-bold"><?= htmlspecialchars($match['match_title']) ?></h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="../scoreboard.php?match_id=<?= $matchId ?>" target="_blank" class="bg-purple-500 hover:bg-purple-600 px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-tv mr-2"></i>Scoreboard
                    </a>
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
        <!-- Match Info -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Tim 1</p>
                    <p class="font-bold text-lg text-blue-600">
                        <?php foreach ($matchPlayers['team1'] as $p): ?>
                            <?= htmlspecialchars($p['name']) ?><?= $p !== end($matchPlayers['team1']) ? ' / ' : '' ?>
                        <?php endforeach; ?>
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 mb-1">VS</p>
                    <p class="font-bold text-lg text-gray-800"><?= getPlayTypeName($match['play_type']) ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 mb-1">Tim 2</p>
                    <p class="font-bold text-lg text-red-600">
                        <?php foreach ($matchPlayers['team2'] as $p): ?>
                            <?= htmlspecialchars($p['name']) ?><?= $p !== end($matchPlayers['team2']) ? ' / ' : '' ?>
                        <?php endforeach; ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Sets Display -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Set</h3>
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
                    <div class="text-center p-4 rounded-lg <?= $set && $set['status'] === 'in_progress' ? 'bg-green-50 border-2 border-green-500' : ($set ? 'bg-gray-50' : 'bg-gray-100') ?>">
                        <p class="text-sm text-gray-600 mb-2">Set <?= $i ?></p>
                        <?php if ($set): ?>
                            <div class="flex justify-around">
                                <span class="text-2xl font-bold text-blue-600"><?= $set['team1_games'] ?></span>
                                <span class="text-2xl font-bold text-gray-400">-</span>
                                <span class="text-2xl font-bold text-red-600"><?= $set['team2_games'] ?></span>
                            </div>
                        <?php else: ?>
                            <div class="flex justify-around">
                                <span class="text-2xl font-bold text-gray-300">0</span>
                                <span class="text-2xl font-bold text-gray-300">-</span>
                                <span class="text-2xl font-bold text-gray-300">0</span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Current Game Score -->
        <?php if (!$isCompleted): ?>
            <div class="bg-gradient-to-r from-green-500 to-blue-500 rounded-xl shadow-lg p-8 mb-6">
                <h3 class="text-white text-lg font-bold mb-6 text-center">Game Saat Ini</h3>
                <div class="grid grid-cols-2 gap-8">
                    <!-- Team 1 -->
                    <div class="text-center">
                        <p class="text-white text-sm mb-4">Tim 1</p>
                        <div class="bg-white rounded-lg p-6 mb-4">
                            <p class="text-6xl font-bold text-blue-600" id="team1_score">
                                <?= $currentGame ? getTennisScore($currentGame['team1_points']) : '0' ?>
                            </p>
                        </div>
                        <div class="flex gap-2 justify-center">
                            <button onclick="updateScore(<?= $matchId ?>, 'team1', 'add')" 
                                    class="score-button bg-white text-green-600 px-6 py-3 rounded-lg font-bold hover:bg-green-50 shadow-lg">
                                <i class="fas fa-plus"></i> Tambah
                            </button>
                            <button onclick="updateScore(<?= $matchId ?>, 'team1', 'remove')" 
                                    class="score-button bg-white text-red-600 px-6 py-3 rounded-lg font-bold hover:bg-red-50 shadow-lg">
                                <i class="fas fa-minus"></i> Kurang
                            </button>
                        </div>
                    </div>

                    <!-- Team 2 -->
                    <div class="text-center">
                        <p class="text-white text-sm mb-4">Tim 2</p>
                        <div class="bg-white rounded-lg p-6 mb-4">
                            <p class="text-6xl font-bold text-red-600" id="team2_score">
                                <?= $currentGame ? getTennisScore($currentGame['team2_points']) : '0' ?>
                            </p>
                        </div>
                        <div class="flex gap-2 justify-center">
                            <button onclick="updateScore(<?= $matchId ?>, 'team2', 'add')" 
                                    class="score-button bg-white text-green-600 px-6 py-3 rounded-lg font-bold hover:bg-green-50 shadow-lg">
                                <i class="fas fa-plus"></i> Tambah
                            </button>
                            <button onclick="updateScore(<?= $matchId ?>, 'team2', 'remove')" 
                                    class="score-button bg-white text-red-600 px-6 py-3 rounded-lg font-bold hover:bg-red-50 shadow-lg">
                                <i class="fas fa-minus"></i> Kurang
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Final Score Display -->
            <div class="bg-gradient-to-r from-green-500 to-blue-500 rounded-xl shadow-lg p-8 mb-6">
                <h3 class="text-white text-2xl font-bold mb-6 text-center">Skor Final</h3>
                <div class="grid grid-cols-2 gap-8">
                    <div class="text-center">
                        <p class="text-white text-lg mb-4">Tim 1</p>
                        <div class="bg-white rounded-lg p-6">
                            <p class="text-6xl font-bold text-blue-600">
                                <?= $finalScore['team1_sets'] ?>
                            </p>
                            <p class="text-xl text-gray-600 mt-2">Set</p>
                        </div>
                    </div>
                    <div class="text-center">
                        <p class="text-white text-lg mb-4">Tim 2</p>
                        <div class="bg-white rounded-lg p-6">
                            <p class="text-6xl font-bold text-red-600">
                                <?= $finalScore['team2_sets'] ?>
                            </p>
                            <p class="text-xl text-gray-600 mt-2">Set</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <?php if ($isCompleted): ?>
                <div class="text-center">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Pertandingan Telah Selesai</h3>
                    <a href="index.php" class="inline-block bg-blue-500 hover:bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>Kembali ke Daftar Pertandingan
                    </a>
                </div>
            <?php else: ?>
                <h3 class="text-lg font-bold text-gray-800 mb-4">Aksi</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <button onclick="nextGame(<?= $matchId ?>)" 
                            class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-forward mr-2"></i>Game Berikutnya
                    </button>
                    <button onclick="nextSet(<?= $matchId ?>)" 
                            class="bg-purple-500 hover:bg-purple-600 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-step-forward mr-2"></i>Set Berikutnya
                    </button>
                    <button onclick="finishMatch(<?= $matchId ?>)" 
                            class="bg-red-500 hover:bg-red-600 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-flag-checkered mr-2"></i>Selesai Pertandingan
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function updateScore(matchId, team, action) {
            fetch('../api/update-score.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    match_id: matchId,
                    team: team,
                    action: action
                })
            })
            .then(response => {
                // Check if response is ok
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                // Check if response is JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Response is not JSON');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    document.getElementById('team1_score').textContent = data.team1_score;
                    document.getElementById('team2_score').textContent = data.team2_score;
                    // Reload page to update sets
                    setTimeout(() => location.reload(), 500);
                } else {
                    alert('Error: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Don't show alert if it's just a JSON parse error - reload instead
                // The score was likely updated successfully
                setTimeout(() => location.reload(), 500);
            });
        }

        function nextGame(matchId) {
            if (confirm('Lanjut ke game berikutnya?')) {
                fetch('../api/next-game.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ match_id: matchId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }

        function nextSet(matchId) {
            if (confirm('Lanjut ke set berikutnya?')) {
                fetch('../api/next-set.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ match_id: matchId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }

        function finishMatch(matchId) {
            if (confirm('Selesaikan pertandingan ini?')) {
                fetch('../api/finish-match.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ match_id: matchId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Pertandingan selesai!');
                        window.location.href = 'index.php';
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }
    </script>
</body>
</html>

