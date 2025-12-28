<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

requireRole(['wasit']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$matchId = intval($data['match_id'] ?? 0);

if ($matchId === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid match ID']);
    exit();
}

$conn = getDBConnection();

$currentSet = getCurrentSet($matchId);
$currentGame = getCurrentGame($matchId, $currentSet['id']);

if (!$currentSet || !$currentGame) {
    echo json_encode(['success' => false, 'message' => 'No active set or game']);
    exit();
}

// Complete current game
$stmt = $conn->prepare("UPDATE games SET status = 'completed', completed_at = NOW() WHERE id = ?");
$stmt->bind_param("i", $currentGame['id']);
$stmt->execute();

// Create next game
$nextGameNumber = $currentGame['game_number'] + 1;
$servingTeam = ($currentGame['serving_team'] === 'team1') ? 'team2' : 'team1';
$stmt = $conn->prepare("INSERT INTO games (match_id, set_id, game_number, serving_team, status) VALUES (?, ?, ?, ?, 'in_progress')");
$stmt->bind_param("iiss", $matchId, $currentSet['id'], $nextGameNumber, $servingTeam);
$stmt->execute();

echo json_encode(['success' => true]);
?>

