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

// Complete match
$stmt = $conn->prepare("UPDATE matches SET status = 'completed' WHERE id = ?");
$stmt->bind_param("i", $matchId);
$stmt->execute();

// Complete current set and game if exists
$currentSet = getCurrentSet($matchId);
if ($currentSet) {
    $stmt = $conn->prepare("UPDATE sets SET status = 'completed', completed_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $currentSet['id']);
    $stmt->execute();
    
    $currentGame = getCurrentGame($matchId, $currentSet['id']);
    if ($currentGame) {
        $stmt = $conn->prepare("UPDATE games SET status = 'completed', completed_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $currentGame['id']);
        $stmt->execute();
    }
}

echo json_encode(['success' => true]);
?>

