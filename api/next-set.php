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

$match = getActiveMatch($matchId);
$currentSet = getCurrentSet($matchId);

if (!$currentSet) {
    echo json_encode(['success' => false, 'message' => 'No active set']);
    exit();
}

// Complete current set
$stmt = $conn->prepare("UPDATE sets SET status = 'completed', completed_at = NOW() WHERE id = ?");
$stmt->bind_param("i", $currentSet['id']);
$stmt->execute();

// Complete current game if exists
$currentGame = getCurrentGame($matchId, $currentSet['id']);
if ($currentGame) {
    $stmt = $conn->prepare("UPDATE games SET status = 'completed', completed_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $currentGame['id']);
    $stmt->execute();
}

// Check if we can create next set
if ($currentSet['set_number'] < $match['number_of_sets']) {
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

echo json_encode(['success' => true]);
?>

