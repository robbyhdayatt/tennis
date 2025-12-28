<?php
// api/update-score.php
error_reporting(E_ALL);
ini_set('display_errors', 0);
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    requireRole(['wasit']);
    
    $data = json_decode(file_get_contents('php://input'), true);
    $matchId = intval($data['match_id'] ?? 0);
    $team = $data['team'] ?? '';
    $action = $data['action'] ?? 'add'; // Default add
    
    if ($matchId === 0 || !in_array($team, ['team1', 'team2'])) {
        throw new Exception('Invalid parameters');
    }

    // Hanya handle action 'add' di sini, remove sebaiknya pakai undo
    if ($action !== 'add') {
        throw new Exception('Use undo for removing points');
    }

    $conn = getDBConnection();
    
    // 1. Get Context
    $currentSet = getCurrentSet($matchId);
    if (!$currentSet) throw new Exception('No active set');
    
    $currentGame = getCurrentGame($matchId, $currentSet['id']);
    if (!$currentGame) throw new Exception('No active game');
    
    $match = getActiveMatch($matchId);
    
    // 2. Cek apakah ini Tie-Break Game?
    $isTieBreak = ($currentSet['team1_games'] == 6 && $currentSet['team2_games'] == 6);
    
    // 3. Hitung Poin Baru
    $pointsField = $team . '_points';
    $opponentField = ($team === 'team1' ? 'team2' : 'team1') . '_points';
    
    $currentPoints = $currentGame[$pointsField];
    $opponentPoints = $currentGame[$opponentField];
    $newPoints = $currentPoints + 1;

    // 4. Update Poin di DB
    $stmt = $conn->prepare("UPDATE games SET {$pointsField} = ? WHERE id = ?");
    $stmt->bind_param("ii", $newPoints, $currentGame['id']);
    $stmt->execute();

    // 5. Log History
    $userId = $_SESSION['user_id'];
    $scoreDisplay = $isTieBreak ? $newPoints : getTennisScore($newPoints);
    $stmt = $conn->prepare("INSERT INTO score_history (match_id, set_id, game_id, action_type, team, new_score, updated_by) VALUES (?, ?, ?, 'point_added', ?, ?, ?)");
    $stmt->bind_param("iiissi", $matchId, $currentSet['id'], $currentGame['id'], $team, $scoreDisplay, $userId);
    $stmt->execute();

    // 6. Cek Kondisi Menang Game
    $gameWon = false;
    
    if ($isTieBreak) {
        // Aturan Tie-Break: Minimal 7 poin, selisih 2
        if ($newPoints >= 7 && ($newPoints - $opponentPoints >= 2)) {
            $gameWon = true;
        }
    } else {
        // Aturan Normal
        if ($match['deuce_enabled']) {
            if ($newPoints >= 4 && ($newPoints - $opponentPoints >= 2)) $gameWon = true;
        } else {
            if ($newPoints >= 4) $gameWon = true;
        }
    }

    if ($gameWon) {
        // Update Games Count di Set
        $gamesField = $team . '_games';
        $newGamesCount = $currentSet[$gamesField] + 1;
        
        $stmt = $conn->prepare("UPDATE sets SET {$gamesField} = ? WHERE id = ?");
        $stmt->bind_param("ii", $newGamesCount, $currentSet['id']);
        $stmt->execute();
        
        // Tutup Game Saat Ini
        $stmt = $conn->prepare("UPDATE games SET status = 'completed', completed_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $currentGame['id']);
        $stmt->execute();

        // Cek Kondisi Menang Set
        $setWon = false;
        $opponentGames = $currentSet[($team === 'team1' ? 'team2' : 'team1') . '_games'];
        
        if ($isTieBreak) {
            // Jika menang tie-break (skor jadi 7-6), set selesai
            $setWon = true; 
        } else {
            // Normal Set logic
            if ($match['game_per_set_type'] == 'Normal') {
                // Standar tenis: menang jika 6-x (selisih 2) atau 7-5. 
                // Jika 6-6, lanjut tie break (belum menang set)
                if ($newGamesCount >= 6 && ($newGamesCount - $opponentGames >= 2)) {
                    $setWon = true;
                } elseif ($newGamesCount == 7 && $opponentGames == 5) {
                    $setWon = true;
                }
            } else {
                // Logic BestOf (misal pingpong atau custom)
                $target = ($match['game_per_set_value'] + 1) / 2;
                if ($newGamesCount >= $target) $setWon = true;
            }
        }

        if ($setWon) {
            // Tutup Set
            $stmt = $conn->prepare("UPDATE sets SET status = 'completed', completed_at = NOW() WHERE id = ?");
            $stmt->bind_param("i", $currentSet['id']);
            $stmt->execute();
            
            // Cek Match Selesai?
            $stmt = $conn->prepare("SELECT COUNT(*) as completed_sets FROM sets WHERE match_id = ? AND status = 'completed'");
            $stmt->bind_param("i", $matchId);
            $stmt->execute();
            $result = $stmt->get_result();
            $completedSets = $result->fetch_assoc()['completed_sets'];
            
            if ($completedSets >= ceil($match['number_of_sets'] / 2)) {
                $stmt = $conn->prepare("UPDATE matches SET status = 'completed' WHERE id = ?");
                $stmt->bind_param("i", $matchId);
                $stmt->execute();
            } else {
                // Buat Set Baru
                $nextSet = $currentSet['set_number'] + 1;
                $stmt = $conn->prepare("INSERT INTO sets (match_id, set_number, status) VALUES (?, ?, 'in_progress')");
                $stmt->bind_param("ii", $matchId, $nextSet);
                $stmt->execute();
                $newSetId = $conn->insert_id;
                
                // Buat Game Baru di Set Baru
                $stmt = $conn->prepare("INSERT INTO games (match_id, set_id, game_number, status) VALUES (?, ?, 1, 'in_progress')");
                $stmt->bind_param("ii", $matchId, $newSetId);
                $stmt->execute();
            }
        } else {
            // Buat Game Baru (Next Game) dalam Set yang sama
            // Ganti Server
            $nextServer = ($currentGame['serving_team'] == 'team1') ? 'team2' : 'team1';
            $nextGameNum = $currentGame['game_number'] + 1;
            
            $stmt = $conn->prepare("INSERT INTO games (match_id, set_id, game_number, serving_team, status) VALUES (?, ?, ?, ?, 'in_progress')");
            $stmt->bind_param("iiss", $matchId, $currentSet['id'], $nextGameNum, $nextServer);
            $stmt->execute();
        }
    }
    
    // Update timestamp match agar polling client mendeteksi perubahan
    $stmt = $conn->prepare("UPDATE matches SET updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $matchId);
    $stmt->execute();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>