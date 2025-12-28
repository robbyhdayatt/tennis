<?php
// api/undo-score.php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');
requireRole(['wasit']);

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $matchId = intval($data['match_id'] ?? 0);

    if ($matchId === 0) {
        throw new Exception('Invalid match ID');
    }

    $conn = getDBConnection();

    // Ambil history terakhir
    $stmt = $conn->prepare("SELECT * FROM score_history WHERE match_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("i", $matchId);
    $stmt->execute();
    $history = $stmt->get_result()->fetch_assoc();

    if (!$history) {
        throw new Exception('Tidak ada riwayat untuk di-undo');
    }

    // Logic Undo
    $team = $history['team'];
    $gameId = $history['game_id'];
    $setId = $history['set_id'];

    // 1. Cek status Game
    $stmt = $conn->prepare("SELECT * FROM games WHERE id = ?");
    $stmt->bind_param("i", $gameId);
    $stmt->execute();
    $game = $stmt->get_result()->fetch_assoc();

    if ($game['status'] === 'completed') {
        // RE-OPEN GAME
        $conn->query("UPDATE games SET status = 'in_progress', completed_at = NULL WHERE id = $gameId");
        
        // Kurangi skor set
        $gamesField = $team . '_games';
        $conn->query("UPDATE sets SET {$gamesField} = {$gamesField} - 1, status = 'in_progress', completed_at = NULL WHERE id = $setId");
        
        // Buka kembali match jika status completed
        $conn->query("UPDATE matches SET status = 'active' WHERE id = $matchId");
        
        // Hapus game/set "baru" yang kosong yang otomatis terbuat
        $conn->query("DELETE FROM games WHERE match_id = $matchId AND id > $gameId AND team1_points = 0 AND team2_points = 0");
        $conn->query("DELETE FROM sets WHERE match_id = $matchId AND id > $setId AND team1_games = 0 AND team2_games = 0");
    }

    // Kurangi Poin Pemain
    $pointsField = $team . '_points';
    $conn->query("UPDATE games SET {$pointsField} = GREATEST(0, {$pointsField} - 1) WHERE id = $gameId");

    // Hapus History
    $conn->query("DELETE FROM score_history WHERE id = " . $history['id']);
    
    // Update timestamp match
    $conn->query("UPDATE matches SET updated_at = NOW() WHERE id = $matchId");

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>