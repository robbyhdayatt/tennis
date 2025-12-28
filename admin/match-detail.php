<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole(['admin', 'wasit']); // Bisa diakses wasit juga untuk view

$matchId = intval($_GET['id'] ?? 0);
if ($matchId === 0) {
    header('Location: index.php');
    exit();
}

$conn = getDBConnection();

// Get Match
$stmt = $conn->prepare("SELECT * FROM matches WHERE id = ?");
$stmt->bind_param("i", $matchId);
$stmt->execute();
$match = $stmt->get_result()->fetch_assoc();

if (!$match) die("Match not found");

$matchPlayers = getMatchPlayers($matchId);
$allSets = getAllSets($matchId);
$finalScore = getFinalScore($matchId);
$winner = getMatchWinner($matchId);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pertandingan - <?= htmlspecialchars($match['match_title']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <a href="javascript:history.back()" class="text-gray-600 hover:text-gray-900">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
                <h1 class="text-xl font-bold text-gray-800">Detail Pertandingan</h1>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 py-8">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 p-6 text-white text-center">
                <h2 class="text-2xl font-bold mb-2"><?= htmlspecialchars($match['match_title']) ?></h2>
                <div class="flex justify-center space-x-4 text-blue-100 text-sm">
                    <span><i class="far fa-calendar-alt mr-1"></i> <?= formatDate($match['match_date']) ?></span>
                    <span><i class="fas fa-tag mr-1"></i> <?= getPlayTypeName($match['play_type']) ?></span>
                    <span class="bg-white/20 px-2 rounded"><?= getMatchStatus($match['status']) ?></span>
                </div>
            </div>
            
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div class="text-center flex-1">
                        <div class="text-xl font-bold text-blue-600 mb-1">
                            <?php foreach ($matchPlayers['team1'] as $p): ?>
                                <div class="leading-tight"><?= htmlspecialchars($p['name']) ?></div>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($winner === 'team1'): ?>
                            <span class="inline-block bg-yellow-100 text-yellow-800 text-xs font-bold px-2 py-1 rounded-full mt-2">
                                <i class="fas fa-trophy mr-1"></i> WINNER
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="px-6 text-center">
                        <div class="text-5xl font-black text-gray-800 tracking-tight">
                            <?= $finalScore['team1_sets'] ?> - <?= $finalScore['team2_sets'] ?>
                        </div>
                        <div class="text-xs text-gray-500 font-bold uppercase tracking-widest mt-1">Final Sets</div>
                    </div>

                    <div class="text-center flex-1">
                        <div class="text-xl font-bold text-red-600 mb-1">
                            <?php foreach ($matchPlayers['team2'] as $p): ?>
                                <div class="leading-tight"><?= htmlspecialchars($p['name']) ?></div>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($winner === 'team2'): ?>
                            <span class="inline-block bg-yellow-100 text-yellow-800 text-xs font-bold px-2 py-1 rounded-full mt-2">
                                <i class="fas fa-trophy mr-1"></i> WINNER
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">Rincian Per Set</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-center">
                    <thead>
                        <tr class="text-gray-500 text-sm">
                            <th class="py-2 text-left">Tim</th>
                            <?php for($i=1; $i<=$match['number_of_sets']; $i++): ?>
                                <th class="py-2">Set <?= $i ?></th>
                            <?php endfor; ?>
                        </tr>
                    </thead>
                    <tbody class="text-gray-800 font-medium">
                        <tr class="border-t border-gray-100">
                            <td class="py-3 text-left font-bold text-blue-600">Tim 1</td>
                            <?php for($i=1; $i<=$match['number_of_sets']; $i++): 
                                $set = null;
                                foreach($allSets as $s) if($s['set_number'] == $i) $set = $s;
                            ?>
                                <td class="py-3"><?= $set ? $set['team1_games'] : '-' ?></td>
                            <?php endfor; ?>
                        </tr>
                        <tr class="border-t border-gray-100">
                            <td class="py-3 text-left font-bold text-red-600">Tim 2</td>
                            <?php for($i=1; $i<=$match['number_of_sets']; $i++): 
                                $set = null;
                                foreach($allSets as $s) if($s['set_number'] == $i) $set = $s;
                            ?>
                                <td class="py-3"><?= $set ? $set['team2_games'] : '-' ?></td>
                            <?php endfor; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>