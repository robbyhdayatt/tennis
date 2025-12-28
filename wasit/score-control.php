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
$stmt = $conn->prepare("SELECT * FROM matches WHERE id = ?");
$stmt->bind_param("i", $matchId);
$stmt->execute();
$match = $stmt->get_result()->fetch_assoc();

if (!$match) {
    header('Location: index.php');
    exit();
}

$isCompleted = ($match['status'] === 'completed');
$matchPlayers = getMatchPlayers($matchId);
$allSets = getAllSets($matchId);
$currentSet = null;
$currentGame = null;
$winner = null;
$finalScore = null;
$isTieBreak = false;

if ($isCompleted) {
    $winner = getMatchWinner($matchId);
    $finalScore = getFinalScore($matchId);
} else {
    // Logic mendapatkan game aktif (seperti kode sebelumnya)
    $currentSet = getCurrentSet($matchId);
    if (!$currentSet) {
        $lastSetNum = 0;
        if (!empty($allSets)) {
            $lastSet = end($allSets);
            $lastSetNum = $lastSet['set_number'];
        }
        if ($lastSetNum < $match['number_of_sets']) {
            $stmt = $conn->prepare("INSERT IGNORE INTO sets (match_id, set_number, status) VALUES (?, ?, 'in_progress')");
            $nextNum = $lastSetNum + 1;
            $stmt->bind_param("ii", $matchId, $nextNum);
            $stmt->execute();
            $currentSet = getCurrentSet($matchId);
        }
    }
    
    if ($currentSet) {
        $currentGame = getCurrentGame($matchId, $currentSet['id']);
        if (!$currentGame) {
            $stmt = $conn->prepare("INSERT IGNORE INTO games (match_id, set_id, game_number, status) VALUES (?, ?, 1, 'in_progress')");
            $stmt->bind_param("ii", $matchId, $currentSet['id']);
            $stmt->execute();
            $currentGame = getCurrentGame($matchId, $currentSet['id']);
        }
        $isTieBreak = isTieBreak($currentSet['id']);
    }
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
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --yamaha-blue: #0D1B4D;
            --yamaha-red: #E4032E;
        }
        body { 
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
        }
        .font-display { font-family: 'Rajdhani', sans-serif; }
        .font-num { 
            font-variant-numeric: tabular-nums; 
            font-family: 'Rajdhani', sans-serif;
        }
        
        .nav-bar {
            background: linear-gradient(135deg, var(--yamaha-blue) 0%, #1a2b5f 100%);
            box-shadow: 0 4px 20px rgba(13, 27, 77, 0.3);
        }
        
        .score-button {
            transition: all 0.2s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .score-button:active { 
            transform: scale(0.95);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .score-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        
        .control-card {
            background: white;
            border: 2px solid #e5e7eb;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .set-indicator {
            transition: all 0.3s ease;
        }
        
        .set-indicator.active {
            background: linear-gradient(135deg, var(--yamaha-red) 0%, #c00228 100%);
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(228, 3, 46, 0.4);
        }
    </style>
</head>
<body>
    <nav class="nav-bar text-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-display font-bold tracking-wide mb-1">
                    <i class="fas fa-gavel mr-2"></i>REFEREE CONTROL
                </h1>
                <p class="text-sm text-white/70 font-medium"><?= htmlspecialchars($match['match_title']) ?></p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="../scoreboard.php?match_id=<?= $matchId ?>" target="_blank" 
                   class="bg-white/20 hover:bg-white/30 backdrop-blur-sm px-4 py-2 rounded-lg transition-all font-medium">
                    <i class="fas fa-tv mr-2"></i> Scoreboard
                </a>
                <a href="index.php" 
                   class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded-lg transition-all font-bold">
                    <i class="fas fa-sign-out-alt mr-2"></i> Exit
                </a>
            </div>
        </div>
    </nav>

    <div class="max-w-5xl mx-auto p-6">
        
        <!-- Team Names Header -->
        <div class="control-card rounded-2xl p-6 mb-6 grid grid-cols-3 gap-6 text-center items-center">
            <div class="text-left">
                <div class="text-xs font-bold text-blue-500 uppercase tracking-wider mb-2">Team 1</div>
                <?php foreach ($matchPlayers['team1'] as $p): ?>
                    <div class="font-display font-bold text-2xl text-blue-600 leading-tight">
                        <?= htmlspecialchars($p['name']) ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="flex items-center justify-center">
                <div class="bg-gradient-to-r from-blue-500 via-gray-400 to-red-500 w-20 h-1 rounded-full"></div>
            </div>
            
            <div class="text-right">
                <div class="text-xs font-bold text-red-500 uppercase tracking-wider mb-2">Team 2</div>
                <?php foreach ($matchPlayers['team2'] as $p): ?>
                    <div class="font-display font-bold text-2xl text-red-600 leading-tight">
                        <?= htmlspecialchars($p['name']) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (!$isCompleted && $currentGame): ?>
            
            <!-- Current Game Control -->
            <div class="control-card rounded-2xl overflow-hidden mb-6 shadow-2xl">
                <div class="bg-gradient-to-r from-gray-800 via-gray-700 to-gray-800 text-white text-center py-4">
                    <div class="text-sm font-bold tracking-widest uppercase">
                        <?= $isTieBreak ? '<i class="fas fa-bolt mr-2"></i>TIE BREAK' : 'CURRENT GAME' ?>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 divide-x divide-gray-200">
                    <!-- Team 1 Score Control -->
                    <div class="p-8 text-center bg-gradient-to-br from-blue-50 to-white">
                        <div class="font-num text-9xl font-black text-blue-600 mb-8 drop-shadow-lg" id="team1_score">
                            <?= getTennisScore($currentGame['team1_points'], $isTieBreak) ?>
                        </div>
                        <button onclick="updateScore(<?= $matchId ?>, 'team1')" 
                                class="score-button w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white py-5 rounded-xl font-bold text-xl">
                            <i class="fas fa-plus-circle text-2xl mr-2"></i>
                            ADD POINT
                        </button>
                    </div>
                    
                    <!-- Team 2 Score Control -->
                    <div class="p-8 text-center bg-gradient-to-br from-red-50 to-white">
                        <div class="font-num text-9xl font-black text-red-600 mb-8 drop-shadow-lg" id="team2_score">
                            <?= getTennisScore($currentGame['team2_points'], $isTieBreak) ?>
                        </div>
                        <button onclick="updateScore(<?= $matchId ?>, 'team2')" 
                                class="score-button w-full bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white py-5 rounded-xl font-bold text-xl">
                            <i class="fas fa-plus-circle text-2xl mr-2"></i>
                            ADD POINT
                        </button>
                    </div>
                </div>
            </div>

            <!-- Set Scores Display -->
            <div class="control-card rounded-2xl p-6 mb-6">
                <h3 class="text-sm font-bold text-gray-500 mb-4 uppercase tracking-wider flex items-center">
                    <i class="fas fa-list-ol mr-2"></i>Set Scores
                </h3>
                <div class="flex justify-center gap-3">
                    <?php for ($i=1; $i<=$match['number_of_sets']; $i++): 
                        $s = null; 
                        foreach($allSets as $set) {
                            if($set['set_number']==$i) $s=$set;
                        }
                        $isActive = ($s && $s['status']=='in_progress');
                    ?>
                        <div class="set-indicator <?= $isActive ? 'active' : 'bg-gray-100' ?> rounded-xl px-5 py-4 border-2 <?= $isActive ? 'border-red-600' : 'border-gray-200' ?> min-w-[100px]">
                            <div class="text-xs font-bold <?= $isActive ? 'text-white' : 'text-gray-400' ?> mb-2 uppercase tracking-wider">
                                Set <?= $i ?>
                            </div>
                            <div class="font-display font-black text-3xl <?= $isActive ? 'text-white' : 'text-gray-700' ?>">
                                <span class="<?= $isActive ? '' : 'text-blue-600' ?>"><?= $s ? $s['team1_games'] : 0 ?></span>
                                <span class="mx-2 <?= $isActive ? 'text-white/50' : 'text-gray-400' ?>">-</span>
                                <span class="<?= $isActive ? '' : 'text-red-600' ?>"><?= $s ? $s['team2_games'] : 0 ?></span>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="grid grid-cols-2 gap-4">
                <button onclick="undoScore(<?= $matchId ?>)" 
                        class="control-card rounded-xl py-4 font-bold text-yellow-700 border-2 border-yellow-400 bg-gradient-to-r from-yellow-50 to-yellow-100 hover:from-yellow-100 hover:to-yellow-200 transition-all hover:scale-105">
                    <i class="fas fa-undo-alt mr-2 text-lg"></i> UNDO LAST POINT
                </button>
                <button onclick="finishMatch(<?= $matchId ?>)" 
                        class="control-card rounded-xl py-4 font-bold text-gray-700 border-2 border-gray-400 bg-gradient-to-r from-gray-100 to-gray-200 hover:from-gray-200 hover:to-gray-300 transition-all hover:scale-105">
                    <i class="fas fa-flag-checkered mr-2 text-lg"></i> FORCE FINISH
                </button>
            </div>

        <?php else: ?>
            
            <!-- Match Completed State -->
            <div class="control-card rounded-2xl p-12 text-center">
                <div class="w-24 h-24 bg-gradient-to-br from-green-400 to-green-600 text-white rounded-full flex items-center justify-center mx-auto mb-6 shadow-2xl">
                    <i class="fas fa-trophy text-5xl"></i>
                </div>
                <h2 class="text-3xl font-display font-black text-gray-800 mb-2">MATCH COMPLETED</h2>
                <p class="text-gray-500 mb-8 font-medium">Final score has been recorded</p>
                
                <div class="bg-gradient-to-r from-gray-100 to-gray-200 rounded-2xl py-8 mb-8 border-2 border-gray-300">
                    <div class="text-sm text-gray-500 font-bold uppercase tracking-wider mb-2">Final Score</div>
                    <div class="font-display text-6xl font-black text-gray-800">
                        <span class="text-blue-600"><?= $finalScore['team1_sets'] ?></span>
                        <span class="mx-4 text-gray-400">-</span>
                        <span class="text-red-600"><?= $finalScore['team2_sets'] ?></span>
                    </div>
                </div>

                <a href="index.php" 
                   class="inline-flex items-center bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-8 py-4 rounded-xl font-bold shadow-xl transition-all transform hover:scale-105">
                    <i class="fas fa-home mr-3 text-xl"></i> BACK TO DASHBOARD
                </a>
            </div>
            
        <?php endif; ?>
        
    </div>

    <script>
        function updateScore(matchId, team) {
            fetch('../api/update-score.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ match_id: matchId, team: team })
            })
            .then(r => r.json())
            .then(data => {
                if(data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Error updating score');
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('Network error. Please try again.');
            });
        }
        
        function undoScore(matchId) {
            if(confirm('Undo the last point?')) {
                fetch('../api/undo-score.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ match_id: matchId })
                })
                .then(r => r.json())
                .then(data => {
                    if(data.success) location.reload();
                    else alert(data.message || 'Error undoing score');
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('Network error. Please try again.');
                });
            }
        }
        
        function finishMatch(matchId) {
            if(confirm('Force finish this match? This action cannot be undone.')) {
                fetch('../api/finish-match.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ match_id: matchId })
                })
                .then(r => r.json())
                .then(data => {
                    if(data.success) location.reload();
                    else alert(data.message || 'Error finishing match');
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('Network error. Please try again.');
                });
            }
        }
    </script>
</body>
</html>