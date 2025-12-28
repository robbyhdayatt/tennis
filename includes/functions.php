<?php
// Helper Functions

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('d/m/Y H:i:s', strtotime($datetime));
}

function getMatchStatus($status) {
    $statuses = [
        'pending' => 'Menunggu',
        'active' => 'Berlangsung',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan'
    ];
    return $statuses[$status] ?? $status;
}

function getCategoryName($category) {
    $categories = [
        'MS' => "Men's Single",
        'WS' => "Women's Single",
        'MD' => "Men's Double",
        'WD' => "Women's Double",
        'XD' => 'Mixed Double'
    ];
    return $categories[$category] ?? $category;
}

function getPlayTypeName($type) {
    return $type === 'Single' ? 'Tunggal' : 'Ganda';
}

function getTennisScore($points) {
    $scores = [0 => '0', 1 => '15', 2 => '30', 3 => '40', 4 => 'AD'];
    return $scores[$points] ?? (string)$points;
}

function getActiveMatch($matchId = null) {
    $conn = getDBConnection();
    
    if ($matchId) {
        $stmt = $conn->prepare("SELECT * FROM matches WHERE id = ?");
        $stmt->bind_param("i", $matchId);
    } else {
        $stmt = $conn->prepare("SELECT * FROM matches WHERE status = 'active' ORDER BY updated_at DESC LIMIT 1");
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getMatchPlayers($matchId) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT mp.*, p.name, p.category 
        FROM match_players mp
        JOIN players p ON mp.player_id = p.id
        WHERE mp.match_id = ?
        ORDER BY mp.team_position, mp.player_position
    ");
    $stmt->bind_param("i", $matchId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $players = ['team1' => [], 'team2' => []];
    while ($row = $result->fetch_assoc()) {
        $players[$row['team_position']][] = $row;
    }
    return $players;
}

function getCurrentSet($matchId) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT * FROM sets 
        WHERE match_id = ? AND status = 'in_progress'
        ORDER BY set_number DESC LIMIT 1
    ");
    $stmt->bind_param("i", $matchId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getCurrentGame($matchId, $setId) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT * FROM games 
        WHERE match_id = ? AND set_id = ? AND status = 'in_progress'
        ORDER BY game_number DESC LIMIT 1
    ");
    $stmt->bind_param("ii", $matchId, $setId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getAllSets($matchId) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT * FROM sets 
        WHERE match_id = ?
        ORDER BY set_number ASC
    ");
    $stmt->bind_param("i", $matchId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sets = [];
    while ($row = $result->fetch_assoc()) {
        $sets[] = $row;
    }
    return $sets;
}

function getMatchWinner($matchId) {
    $conn = getDBConnection();
    $allSets = getAllSets($matchId);
    
    $team1Sets = 0;
    $team2Sets = 0;
    
    foreach ($allSets as $set) {
        if ($set['status'] === 'completed') {
            if ($set['team1_games'] > $set['team2_games']) {
                $team1Sets++;
            } elseif ($set['team2_games'] > $set['team1_games']) {
                $team2Sets++;
            }
        }
    }
    
    if ($team1Sets > $team2Sets) {
        return 'team1';
    } elseif ($team2Sets > $team1Sets) {
        return 'team2';
    }
    
    return null; // Draw or match not finished
}

function getFinalScore($matchId) {
    $allSets = getAllSets($matchId);
    $team1Sets = 0;
    $team2Sets = 0;
    
    foreach ($allSets as $set) {
        if ($set['status'] === 'completed') {
            if ($set['team1_games'] > $set['team2_games']) {
                $team1Sets++;
            } elseif ($set['team2_games'] > $set['team1_games']) {
                $team2Sets++;
            }
        }
    }
    
    return [
        'team1_sets' => $team1Sets,
        'team2_sets' => $team2Sets,
        'sets_detail' => $allSets
    ];
}
?>

