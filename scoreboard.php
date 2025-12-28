<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

// Logic Match (Sama seperti sebelumnya)
$matchId = intval($_GET['match_id'] ?? 0);
if ($matchId === 0) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id FROM matches WHERE status = 'active' ORDER BY updated_at DESC LIMIT 1");
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) $matchId = $res->fetch_assoc()['id'];
}

$match = null;
$sponsors = [];

if ($matchId > 0) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM matches WHERE id = ?");
    $stmt->bind_param("i", $matchId);
    $stmt->execute();
    $match = $stmt->get_result()->fetch_assoc();

    // Fetch Multiple Sponsors
    if ($match) {
        $stmtSpon = $conn->prepare("
            SELECT s.* FROM match_sponsors ms 
            JOIN sponsors s ON ms.sponsor_id = s.id 
            WHERE ms.match_id = ?
        ");
        $stmtSpon->bind_param("i", $matchId);
        $stmtSpon->execute();
        $resSpon = $stmtSpon->get_result();
        while ($s = $resSpon->fetch_assoc()) {
            $sponsors[] = $s;
        }
    }
}

// Tampilan STANDBY (Jika tidak ada match)
if (!$match): 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Scoreboard - Standby</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { 
            background: linear-gradient(135deg, #0D1B4D 0%, #1a2b5f 100%); 
        }
    </style>
</head>
<body class="flex items-center justify-center h-screen text-white overflow-hidden">
    <div class="text-center">
        <div class="text-8xl font-black tracking-tight mb-6 text-white/90">TENNIS</div>
        <div class="text-3xl font-bold tracking-wider border-t-4 border-red-600 pt-6 inline-block px-12">
            WAITING FOR MATCH
        </div>
    </div>
</body>
</html>
<?php exit(); endif; 

// DATA MATCH
$matchPlayers = getMatchPlayers($matchId);
$currentSet = getCurrentSet($matchId);
$currentGame = $currentSet ? getCurrentGame($matchId, $currentSet['id']) : null;
$isCompleted = ($match['status'] === 'completed');
$winner = $isCompleted ? getMatchWinner($matchId) : null;
$finalScore = $isCompleted ? getFinalScore($matchId) : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($match['match_title']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@300;400;500;600;700&family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --yamaha-blue: #0D1B4D;
            --yamaha-blue-light: #1a2b5f;
            --yamaha-red: #E4032E;
            --yamaha-black: #0a0a0a;
        }
        
        body { 
            font-family: 'Roboto', sans-serif; 
            background: linear-gradient(135deg, var(--yamaha-blue) 0%, var(--yamaha-blue-light) 100%);
            color: white;
            overflow: hidden;
        }
        
        .font-display { 
            font-family: 'Rajdhani', sans-serif; 
        }
        
        .font-num { 
            font-variant-numeric: tabular-nums; 
            font-family: 'Rajdhani', sans-serif;
        }

        /* Scoreboard Container */
        .scoreboard-main {
            background: linear-gradient(180deg, rgba(0,0,0,0.4) 0%, rgba(0,0,0,0.6) 100%);
            border: 3px solid rgba(228, 3, 46, 0.5);
            box-shadow: 0 20px 60px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.1);
        }

        /* Player Row Styling */
        .player-row {
            background: linear-gradient(90deg, rgba(255,255,255,0.05) 0%, rgba(255,255,255,0.08) 100%);
            border-left: 4px solid transparent;
            transition: all 0.3s ease;
        }

        .player-row.serving {
            border-left-color: var(--yamaha-red);
            background: linear-gradient(90deg, rgba(228, 3, 46, 0.15) 0%, rgba(228, 3, 46, 0.05) 100%);
        }

        .player-row.winner {
            border-left-color: #FFD700;
            background: linear-gradient(90deg, rgba(255, 215, 0, 0.2) 0%, rgba(255, 215, 0, 0.05) 100%);
        }

        /* Score Box */
        .score-box {
            background: rgba(0,0,0,0.4);
            border: 2px solid rgba(255,255,255,0.1);
            min-width: 80px;
            text-align: center;
        }

        .score-box.current {
            background: var(--yamaha-red);
            border-color: var(--yamaha-red);
            box-shadow: 0 0 20px rgba(228, 3, 46, 0.6);
            transform: scale(1.05);
        }

        .score-box.leading {
            background: rgba(228, 3, 46, 0.3);
            border-color: var(--yamaha-red);
        }

        /* Live Badge */
        .live-badge {
            background: var(--yamaha-red);
            box-shadow: 0 0 15px rgba(228, 3, 46, 0.8);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.8; transform: scale(1.02); }
        }

        /* Winner Badge */
        .winner-badge {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: var(--yamaha-black);
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.5);
            animation: bounce 1s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        /* Header Bar */
        .header-bar {
            background: linear-gradient(180deg, rgba(0,0,0,0.6) 0%, rgba(0,0,0,0.4) 100%);
            border-bottom: 3px solid var(--yamaha-red);
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }

        /* Server Indicator */
        .server-dot {
            width: 12px;
            height: 12px;
            background: var(--yamaha-red);
            border-radius: 50%;
            box-shadow: 0 0 10px var(--yamaha-red);
            animation: glow 1.5s infinite;
        }

        @keyframes glow {
            0%, 100% { opacity: 1; box-shadow: 0 0 10px var(--yamaha-red); }
            50% { opacity: 0.6; box-shadow: 0 0 20px var(--yamaha-red); }
        }

        /* Match Info Box */
        .match-info {
            background: rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
        }

        /* Set Label */
        .set-label {
            font-size: 0.7rem;
            opacity: 0.6;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Responsive Font Sizes */
        @media (max-width: 1536px) {
            .player-name { font-size: 2rem; }
            .current-score { font-size: 4rem; }
        }
    </style>
</head>
<body>

    <div class="flex flex-col h-screen">
        
        <!-- HEADER -->
        <div class="header-bar px-8 py-5">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-8">
                    <!-- Match Info -->
                    <div>
                        <h1 class="text-2xl font-display font-bold uppercase tracking-wider text-white">
                            <?= htmlspecialchars($match['match_title']) ?>
                        </h1>
                        <p class="text-xs font-medium text-white/70 mt-1 uppercase tracking-wide">
                            <?= getPlayTypeName($match['play_type']) ?> • <?= formatDate($match['match_date']) ?>
                        </p>
                    </div>
                    
                    <!-- Sponsors Section -->
                    <?php if (!empty($sponsors)): ?>
                        <div class="flex items-center space-x-6 pl-8 border-l border-white/20">
                            <?php foreach ($sponsors as $s): ?>
                                <img src="<?= htmlspecialchars($s['logo_path']) ?>" 
                                     alt="Sponsor" 
                                     class="h-20 object-contain max-w-[250px] opacity-95 hover:opacity-100 transition-all duration-300"
                                     style="filter: drop-shadow(0 2px 8px rgba(0,0,0,0.3));">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($isCompleted): ?>
                    <div class="winner-badge px-6 py-2 rounded font-bold text-sm tracking-widest uppercase">
                        <i class="fas fa-trophy mr-2"></i>Match Completed
                    </div>
                <?php else: ?>
                    <div class="live-badge flex items-center space-x-3 px-6 py-2 rounded">
                        <div class="w-3 h-3 bg-white rounded-full animate-pulse"></div>
                        <span class="text-sm font-bold tracking-widest uppercase">LIVE</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- MAIN SCOREBOARD -->
        <div class="flex-1 flex items-center justify-center p-8">
            <div class="scoreboard-main rounded-2xl w-full max-w-7xl p-8">
                
                <!-- Match Info Ribbon -->
                <div class="match-info rounded-lg px-6 py-3 mb-6 flex justify-between items-center">
                    <div class="text-sm font-display font-semibold">
                        <span class="text-white/50">BEST OF</span>
                        <span class="text-white ml-2 text-lg"><?= $match['number_of_sets'] ?></span>
                    </div>
                    <?php if($isCompleted && $winner): ?>
                        <div class="text-yellow-400 font-bold text-sm uppercase tracking-wider">
                            <i class="fas fa-crown mr-2"></i>
                            TEAM <?= $winner == 'team1' ? '1' : '2' ?> WINS
                        </div>
                    <?php endif; ?>
                </div>

                <!-- TEAM 1 ROW -->
                <div class="player-row rounded-lg p-6 mb-4 <?= $currentGame && $currentGame['serving_team'] === 'team1' ? 'serving' : '' ?> <?= $isCompleted && $winner === 'team1' ? 'winner' : '' ?>">
                    <div class="flex items-center justify-between">
                        
                        <!-- Player Names & Server -->
                        <div class="flex items-center space-x-4 flex-1">
                            <?php if ($currentGame && $currentGame['serving_team'] === 'team1'): ?>
                                <div class="server-dot"></div>
                            <?php else: ?>
                                <div class="w-3"></div>
                            <?php endif; ?>
                            
                            <div>
                                <?php foreach ($matchPlayers['team1'] as $idx => $p): ?>
                                    <div class="player-name font-display font-bold text-4xl leading-tight <?= $idx > 0 ? 'mt-1' : '' ?>">
                                        <?= htmlspecialchars($p['name']) ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Set Scores -->
                        <div class="flex items-center space-x-3">
                            <div id="team1_sets" class="flex space-x-2"></div>
                            
                            <!-- Current Game Score -->
                            <div class="score-box current rounded-lg px-6 py-4 ml-4">
                                <div class="current-score font-num font-black text-6xl" id="team1_score">
                                    <?= $isCompleted ? $finalScore['team1_sets'] : '0' ?>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- VS DIVIDER -->
                <div class="flex justify-center my-4">
                    <div class="bg-white/10 px-8 py-2 rounded-full">
                        <span class="font-display font-bold text-white/50 text-sm tracking-widest">VS</span>
                    </div>
                </div>

                <!-- TEAM 2 ROW -->
                <div class="player-row rounded-lg p-6 <?= $currentGame && $currentGame['serving_team'] === 'team2' ? 'serving' : '' ?> <?= $isCompleted && $winner === 'team2' ? 'winner' : '' ?>">
                    <div class="flex items-center justify-between">
                        
                        <!-- Player Names & Server -->
                        <div class="flex items-center space-x-4 flex-1">
                            <?php if ($currentGame && $currentGame['serving_team'] === 'team2'): ?>
                                <div class="server-dot"></div>
                            <?php else: ?>
                                <div class="w-3"></div>
                            <?php endif; ?>
                            
                            <div>
                                <?php foreach ($matchPlayers['team2'] as $idx => $p): ?>
                                    <div class="player-name font-display font-bold text-4xl leading-tight <?= $idx > 0 ? 'mt-1' : '' ?>">
                                        <?= htmlspecialchars($p['name']) ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Set Scores -->
                        <div class="flex items-center space-x-3">
                            <div id="team2_sets" class="flex space-x-2"></div>
                            
                            <!-- Current Game Score -->
                            <div class="score-box current rounded-lg px-6 py-4 ml-4">
                                <div class="current-score font-num font-black text-6xl" id="team2_score">
                                    <?= $isCompleted ? $finalScore['team2_sets'] : '0' ?>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>

    </div>

    <script>
        const matchId = <?= $matchId ?>;
        const numberOfSets = <?= $match['number_of_sets'] ?>;
        let lastUpdated = '';
        const isCompleted = <?= $isCompleted ? 'true' : 'false' ?>;

        function updateScoreboard() {
            if(isCompleted) return;

            fetch(`api/get-score.php?match_id=${matchId}&last_updated=${lastUpdated}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success && !data.no_change) {
                        lastUpdated = data.last_updated;
                        if(data.match.status === 'completed') location.reload();

                        // Update Set Scores
                        const team1Sets = document.getElementById('team1_sets');
                        const team2Sets = document.getElementById('team2_sets');
                        team1Sets.innerHTML = '';
                        team2Sets.innerHTML = '';

                        for (let i = 1; i <= numberOfSets; i++) {
                            const set = data.sets.find(s => s.set_number === i);
                            const isActive = (set && set.status === 'in_progress');
                            const t1Games = set ? set.team1_games : '-';
                            const t2Games = set ? set.team2_games : '-';
                            
                            // Determine leading class
                            let t1Class = 'score-box';
                            let t2Class = 'score-box';
                            
                            if (set) {
                                if (set.team1_games > set.team2_games) t1Class += ' leading';
                                if (set.team2_games > set.team1_games) t2Class += ' leading';
                            }

                            team1Sets.innerHTML += `
                                <div class="${t1Class} rounded px-4 py-3">
                                    <div class="set-label mb-1">S${i}</div>
                                    <div class="font-num font-bold text-2xl">${t1Games}</div>
                                </div>
                            `;
                            
                            team2Sets.innerHTML += `
                                <div class="${t2Class} rounded px-4 py-3">
                                    <div class="set-label mb-1">S${i}</div>
                                    <div class="font-num font-bold text-2xl">${t2Games}</div>
                                </div>
                            `;
                        }

                        // Update Current Game Score
                        if (data.current_game) {
                            document.getElementById('team1_score').innerText = data.current_game.team1_score;
                            document.getElementById('team2_score').innerText = data.current_game.team2_score;
                        }
                    }
                });
        }
        
        setInterval(updateScoreboard, 1000);
        updateScoreboard();
    </script>
</body>
</html>