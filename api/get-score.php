<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$matchId = intval($_GET['match_id'] ?? 0);

if ($matchId === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid match ID']);
    exit();
}

$conn = getDBConnection();

// Get match
$match = getActiveMatch($matchId);
if (!$match) {
    echo json_encode(['success' => false, 'message' => 'Match not found']);
    exit();
}

// Get players
$matchPlayers = getMatchPlayers($matchId);

// Get all sets
$allSets = getAllSets($matchId);

// Get current set and game
$currentSet = getCurrentSet($matchId);
$currentGame = null;
if ($currentSet) {
    $currentGame = getCurrentGame($matchId, $currentSet['id']);
}

$response = [
    'success' => true,
    'match' => [
        'id' => $match['id'],
        'title' => $match['match_title'],
        'date' => $match['match_date'],
        'play_type' => $match['play_type'],
        'number_of_sets' => $match['number_of_sets'],
        'status' => $match['status']
    ],
    'players' => [
        'team1' => array_map(function($p) { return $p['name']; }, $matchPlayers['team1']),
        'team2' => array_map(function($p) { return $p['name']; }, $matchPlayers['team2'])
    ],
    'sets' => [],
    'current_game' => null
];

// Format sets
foreach ($allSets as $set) {
    $response['sets'][] = [
        'set_number' => $set['set_number'],
        'team1_games' => $set['team1_games'],
        'team2_games' => $set['team2_games'],
        'status' => $set['status']
    ];
}

// Format current game
if ($currentGame) {
    $response['current_game'] = [
        'team1_points' => $currentGame['team1_points'],
        'team2_points' => $currentGame['team2_points'],
        'team1_score' => getTennisScore($currentGame['team1_points']),
        'team2_score' => getTennisScore($currentGame['team2_points']),
        'serving_team' => $currentGame['serving_team']
    ];
}

echo json_encode($response);
?>

