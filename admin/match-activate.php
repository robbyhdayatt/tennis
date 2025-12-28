<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole(['admin']);

$matchId = intval($_GET['id'] ?? 0);

if ($matchId > 0) {
    $conn = getDBConnection();
    
    // Update match status
    $stmt = $conn->prepare("UPDATE matches SET status = 'active' WHERE id = ?");
    $stmt->bind_param("i", $matchId);
    $stmt->execute();
    
    // Create first set
    $stmt = $conn->prepare("INSERT INTO sets (match_id, set_number, status) VALUES (?, 1, 'in_progress')");
    $stmt->bind_param("i", $matchId);
    $stmt->execute();
    $setId = $conn->insert_id;
    
    // Create first game
    $stmt = $conn->prepare("INSERT INTO games (match_id, set_id, game_number, status) VALUES (?, ?, 1, 'in_progress')");
    $stmt->bind_param("ii", $matchId, $setId);
    $stmt->execute();
}

header('Location: index.php');
exit();
?>

