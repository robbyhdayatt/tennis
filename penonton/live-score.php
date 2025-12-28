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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Score - <?= htmlspecialchars($match['match_title']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --yamaha-blue: #0D1B4D;
            --yamaha-blue-light: #1a2b5f;
            --yamaha-red: #E4032E;
        }
        body { 
            font-family: 'Roboto', sans-serif; 
            background: linear-gradient(135deg, var(--yamaha-blue) 0%, var(--yamaha-blue-light) 100%);
        }
        .font-display { font-family: 'Rajdhani', sans-serif; }
        .font-num { 
            font-variant-numeric: tabular-nums; 
            font-family: 'Rajdhani', sans-serif;
        }
        @keyframes pulse { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.8; transform: scale(1.05); } }
        .live-indicator { animation: pulse 2s infinite; }
        
        .nav-bar {
            background: linear-gradient(180deg, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.5) 100%);
            border-bottom: 3px solid var(--yamaha-red);
            backdrop-filter: blur(10px);
        }
        
        .score-card {
            background: linear-gradient(180deg, rgba(0,0,0,0.4) 0%, rgba(0,0,0,0.6) 100%);
            border: 2px solid rgba(228, 3, 46, 0.3);
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        
        .set-box {
            background: rgba(0,0,0,0.3);
            border: 2px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }
        
        .set-box.active {
            background: rgba(228, 3, 46, 0.3);
            border-color: var(--yamaha-red);
            box-shadow: 0 0 15px rgba(228, 3, 46, 0.5);
            transform: scale(1.05);
        }
        
        .set-box.leading-team1 {
            border-left: 4px solid #3B82F6;
        }
        
        .set-box.leading-team2 {
            border-right: 4px solid #EF4444;
        }
        
        .team-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(5px);
        }
    </style>
</head>
<body class="min-h-screen">
    <nav class="nav-bar text-white shadow-2xl sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="hover:text-gray-300 transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>Kembali
                    </a>
                    <div class="h-8 w-px bg-white/20"></div>
                    <h1 class="text-xl font-display font-bold tracking-wide"><?= htmlspecialchars($match['match_title']) ?></h1>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="live-indicator flex items-center space-x-2 bg-red-600 px-4 py-2 rounded-full shadow-lg">
                        <div class="w-2 h-2 bg-white rounded-full animate-pulse"></div>
                        <span class="text-sm font-bold tracking-wider">LIVE</span>
                    </div>
                    <a href="../scoreboard.php?match_id=<?= $matchId ?>" target="_blank" 
                       class="bg-white/10 hover:bg-white/20 border border-white/20 px-4 py-2 rounded-lg transition-all hover:scale-105 shadow-lg">
                        <i class="fas fa-tv mr-2"></i>Fullscreen
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Team Info Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Team 1 -->
            <div class="team-card rounded-2xl p-6 text-white">
                <div class="flex items-center space-x-3 mb-3">
                    <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                    <p class="text-sm font-bold text-blue-400 uppercase tracking-wider">Team 1</p>
                </div>
                <div class="space-y-1">
                    <?php foreach ($matchPlayers['team1'] as $p): ?>
                        <p class="font-display font-bold text-3xl text-white leading-tight">
                            <?= htmlspecialchars($p['name']) ?>
                        </p>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Team 2 -->
            <div class="team-card rounded-2xl p-6 text-white">
                <div class="flex items-center space-x-3 mb-3">
                    <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                    <p class="text-sm font-bold text-red-400 uppercase tracking-wider">Team 2</p>
                </div>
                <div class="space-y-1">
                    <?php foreach ($matchPlayers['team2'] as $p): ?>
                        <p class="font-display font-bold text-3xl text-white leading-tight">
                            <?= htmlspecialchars($p['name']) ?>
                        </p>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Sets Display -->
        <div class="score-card rounded-2xl p-6 mb-8">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-white text-lg font-display font-bold uppercase tracking-wider">Set Scores</h3>
                <div class="text-white/50 text-sm font-display">
                    Best of <?= $match['number_of_sets'] ?>
                </div>
            </div>
            <div class="grid grid-cols-<?= $match['number_of_sets'] ?> gap-4" id="sets_container">
            </div>
        </div>

        <!-- Current Game Score -->
        <div class="score-card rounded-2xl p-8">
            <h3 class="text-white text-xl font-display font-bold mb-8 text-center uppercase tracking-wider">Current Game</h3>
            <div class="grid grid-cols-2 gap-8">
                <div class="text-center">
                    <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-2xl p-8 shadow-2xl">
                        <p class="text-blue-200 text-sm font-bold mb-4 uppercase tracking-wider">Team 1</p>
                        <p class="font-num text-8xl font-black text-white" id="team1_score">0</p>
                    </div>
                </div>

                <div class="text-center">
                    <div class="bg-gradient-to-br from-red-600 to-red-700 rounded-2xl p-8 shadow-2xl">
                        <p class="text-red-200 text-sm font-bold mb-4 uppercase tracking-wider">Team 2</p>
                        <p class="font-num text-8xl font-black text-white" id="team2_score">0</p>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        const matchId = <?= $matchId ?>;
        const numberOfSets = <?= $match['number_of_sets'] ?>;
        let lastUpdated = '';
        
        function updateScore() {
            fetch(`../api/get-score.php?match_id=${matchId}&last_updated=${lastUpdated}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.no_change) return;
                        lastUpdated = data.last_updated;

                        // Update sets
                        const setsContainer = document.getElementById('sets_container');
                        if (setsContainer) {
                            setsContainer.innerHTML = '';
                            for (let i = 1; i <= data.match.number_of_sets; i++) {
                                const set = data.sets.find(s => s.set_number === i);
                                const isActive = set && set.status === 'in_progress';
                                const t1Games = set ? set.team1_games : '-';
                                const t2Games = set ? set.team2_games : '-';
                                
                                let boxClass = 'set-box';
                                if (isActive) boxClass += ' active';
                                else if (set && set.team1_games > set.team2_games) boxClass += ' leading-team1';
                                else if (set && set.team2_games > set.team1_games) boxClass += ' leading-team2';
                                
                                setsContainer.innerHTML += `
                                    <div class="${boxClass} rounded-xl p-5 text-center">
                                        <p class="text-xs font-bold text-white/50 mb-3 uppercase tracking-widest">Set ${i}</p>
                                        <div class="flex justify-center items-center space-x-4">
                                            <span class="font-num text-4xl font-black text-blue-400">${t1Games}</span>
                                            <span class="text-2xl font-bold text-white/30">-</span>
                                            <span class="font-num text-4xl font-black text-red-400">${t2Games}</span>
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
                .catch(error => console.error('Error updating score:', error));
        }
        
        setInterval(updateScore, 2000);
        updateScore();
    </script>
</body>
</html>