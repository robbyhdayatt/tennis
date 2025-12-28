<?php
// Disable error display to prevent JSON corruption
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    requireRole(['wasit']);

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit();
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $matchId = intval($data['match_id'] ?? 0);
    $team = $data['team'] ?? '';
    $action = $data['action'] ?? '';

    if ($matchId === 0 || !in_array($team, ['team1', 'team2']) || !in_array($action, ['add', 'remove'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit();
    }

    $conn = getDBConnection();

    // Get current set and game
    $currentSet = getCurrentSet($matchId);
    if (!$currentSet) {
        echo json_encode(['success' => false, 'message' => 'No active set found']);
        exit();
    }
    
    $currentGame = getCurrentGame($matchId, $currentSet['id']);
    if (!$currentGame) {
        echo json_encode(['success' => false, 'message' => 'No active game found']);
        exit();
    }

    // Get match settings
    $match = getActiveMatch($matchId);
    if (!$match) {
        echo json_encode(['success' => false, 'message' => 'Match not found']);
        exit();
    }
    $deuceEnabled = $match['deuce_enabled'];

    // Update score
    $pointsField = $team . '_points';
    $currentPoints = $currentGame[$pointsField];
    $opponentField = ($team === 'team1' ? 'team2' : 'team1') . '_points';
    $opponentPoints = $currentGame[$opponentField];

    if ($action === 'add') {
        $newPoints = $currentPoints + 1;
    } else {
        $newPoints = max(0, $currentPoints - 1);
    }

    // Check for game win conditions
    $gameWon = false;
    $gameWinner = null;

    if ($deuceEnabled) {
        // With deuce: win at 4 points with 2 point lead
        if ($newPoints >= 4 && ($newPoints - $opponentPoints >= 2)) {
            $gameWon = true;
            $gameWinner = $team;
        }
    } else {
        // Without deuce: win at 4 points
        if ($newPoints >= 4) {
            $gameWon = true;
            $gameWinner = $team;
        }
    }

    // Update game points
    $stmt = $conn->prepare("UPDATE games SET {$pointsField} = ? WHERE id = ?");
    $stmt->bind_param("ii", $newPoints, $currentGame['id']);
    if (!$stmt->execute()) {
        throw new Exception('Failed to update game points');
    }

    // Log score history
    $userId = $_SESSION['user_id'];
    $oldScore = getTennisScore($currentPoints) . '-' . getTennisScore($opponentPoints);
    $newScore = getTennisScore($newPoints) . '-' . getTennisScore($opponentPoints);
    $stmt = $conn->prepare("INSERT INTO score_history (match_id, set_id, game_id, action_type, team, old_score, new_score, updated_by) VALUES (?, ?, ?, 'point_added', ?, ?, ?, ?)");
    // Parameter types: i=integer, s=string
    // match_id, set_id, game_id (i,i,i), team (s), old_score (s), new_score (s), updated_by (i)
    $stmt->bind_param("iiisssi", $matchId, $currentSet['id'], $currentGame['id'], $team, $oldScore, $newScore, $userId);
    $stmt->execute(); // Don't throw error if history fails

    // If game won, update set games and create new game
    if ($gameWon) {
        // Update set games
        $gamesField = $gameWinner . '_games';
        $stmt = $conn->prepare("SELECT {$gamesField} FROM sets WHERE id = ?");
        $stmt->bind_param("i", $currentSet['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $setData = $result->fetch_assoc();
        $newGames = $setData[$gamesField] + 1;
        
        $stmt = $conn->prepare("UPDATE sets SET {$gamesField} = ? WHERE id = ?");
        $stmt->bind_param("ii", $newGames, $currentSet['id']);
        $stmt->execute();
        
        // Complete current game
        $stmt = $conn->prepare("UPDATE games SET status = 'completed', completed_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $currentGame['id']);
        $stmt->execute();
        
        // Check for set win
        $requiredGames = $match['game_per_set_value'];
        $setWon = false;
        
        if ($match['game_per_set_type'] === 'BestOf') {
            $setWon = ($newGames >= ($requiredGames + 1) / 2);
        } else {
            $opponentGamesField = ($gameWinner === 'team1' ? 'team2' : 'team1') . '_games';
            $stmt = $conn->prepare("SELECT {$opponentGamesField} FROM sets WHERE id = ?");
            $stmt->bind_param("i", $currentSet['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $opponentData = $result->fetch_assoc();
            $opponentGames = $opponentData[$opponentGamesField];
            
            $setWon = ($newGames >= $requiredGames && ($newGames - $opponentGames >= 2)) || ($newGames >= 7);
        }
        
        if ($setWon) {
            // Complete set
            $stmt = $conn->prepare("UPDATE sets SET status = 'completed', completed_at = NOW() WHERE id = ?");
            $stmt->bind_param("i", $currentSet['id']);
            $stmt->execute();
            
            // Check if match is complete
            $stmt = $conn->prepare("SELECT COUNT(*) as completed_sets FROM sets WHERE match_id = ? AND status = 'completed'");
            $stmt->bind_param("i", $matchId);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $completedSets = $data['completed_sets'];
            $requiredSets = ($match['number_of_sets'] + 1) / 2;
            
            if ($completedSets >= $requiredSets) {
                // Match complete
                $stmt = $conn->prepare("UPDATE matches SET status = 'completed' WHERE id = ?");
                $stmt->bind_param("i", $matchId);
                $stmt->execute();
            } else {
                // Create next set
                $nextSetNumber = $currentSet['set_number'] + 1;
                $stmt = $conn->prepare("INSERT INTO sets (match_id, set_number, status) VALUES (?, ?, 'in_progress')");
                $stmt->bind_param("ii", $matchId, $nextSetNumber);
                $stmt->execute();
                $newSetId = $conn->insert_id;
                
                // Create first game of new set
                $stmt = $conn->prepare("INSERT INTO games (match_id, set_id, game_number, status) VALUES (?, ?, 1, 'in_progress')");
                $stmt->bind_param("ii", $matchId, $newSetId);
                $stmt->execute();
            }
        } else {
            // Create next game
            $nextGameNumber = $currentGame['game_number'] + 1;
            $servingTeam = ($currentGame['serving_team'] === 'team1') ? 'team2' : 'team1';
            $stmt = $conn->prepare("INSERT INTO games (match_id, set_id, game_number, serving_team, status) VALUES (?, ?, ?, ?, 'in_progress')");
            $stmt->bind_param("iiss", $matchId, $currentSet['id'], $nextGameNumber, $servingTeam);
            $stmt->execute();
        }
    }

    // Get updated game - get fresh current set and game
    $updatedSet = getCurrentSet($matchId);
    $updatedGame = null;
    if ($updatedSet) {
        $updatedGame = getCurrentGame($matchId, $updatedSet['id']);
    }

    // If no current game (might be between games), return last known score
    if (!$updatedGame) {
        // Get the last game for this match
        $stmt = $conn->prepare("SELECT * FROM games WHERE match_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->bind_param("i", $matchId);
        $stmt->execute();
        $result = $stmt->get_result();
        $updatedGame = $result->fetch_assoc();
    }

    if ($updatedGame) {
        echo json_encode([
            'success' => true,
            'team1_score' => getTennisScore($updatedGame['team1_points']),
            'team2_score' => getTennisScore($updatedGame['team2_points']),
            'game_won' => $gameWon
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'team1_score' => '0',
            'team2_score' => '0',
            'game_won' => $gameWon
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>

