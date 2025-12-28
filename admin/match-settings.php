<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole(['admin']);

$conn = getDBConnection();
$message = '';
$messageType = '';

// Get all players
$players = [];
$stmt = $conn->prepare("SELECT * FROM players ORDER BY category, name");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $players[] = $row;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matchTitle = sanitize($_POST['match_title']);
    $matchDate = sanitize($_POST['match_date']);
    $playType = sanitize($_POST['play_type']);
    $numberOfSets = intval($_POST['number_of_sets']);
    $gamePerSetType = sanitize($_POST['game_per_set_type']);
    $gamePerSetValue = intval($_POST['game_per_set_value']);
    $deuceEnabled = isset($_POST['deuce_enabled']) ? 1 : 0;
    
    // Get selected players
    $team1Players = $_POST['team1_players'] ?? [];
    $team2Players = $_POST['team2_players'] ?? [];
    
    // Validation
    if (empty($matchTitle) || empty($matchDate)) {
        $message = 'Judul dan tanggal pertandingan wajib diisi!';
        $messageType = 'error';
    } elseif ($playType === 'Single' && (count($team1Players) !== 1 || count($team2Players) !== 1)) {
        $message = 'Pertandingan Single memerlukan 1 pemain per tim!';
        $messageType = 'error';
    } elseif ($playType === 'Double' && (count($team1Players) !== 2 || count($team2Players) !== 2)) {
        $message = 'Pertandingan Double memerlukan 2 pemain per tim!';
        $messageType = 'error';
    } else {
        // Insert match
        $userId = $_SESSION['user_id'];
        $stmt = $conn->prepare("INSERT INTO matches (match_title, match_date, play_type, number_of_sets, game_per_set_type, game_per_set_value, deuce_enabled, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssisiii", $matchTitle, $matchDate, $playType, $numberOfSets, $gamePerSetType, $gamePerSetValue, $deuceEnabled, $userId);
        
        if ($stmt->execute()) {
            $matchId = $conn->insert_id;
            
            // Insert team1 players
            $position = 1;
            foreach ($team1Players as $playerId) {
                $stmt2 = $conn->prepare("INSERT INTO match_players (match_id, player_id, team_position, player_position) VALUES (?, ?, 'team1', ?)");
                $pos = 'player' . $position;
                $stmt2->bind_param("iis", $matchId, $playerId, $pos);
                $stmt2->execute();
                $position++;
            }
            
            // Insert team2 players
            $position = 1;
            foreach ($team2Players as $playerId) {
                $stmt2 = $conn->prepare("INSERT INTO match_players (match_id, player_id, team_position, player_position) VALUES (?, ?, 'team2', ?)");
                $pos = 'player' . $position;
                $stmt2->bind_param("iis", $matchId, $playerId, $pos);
                $stmt2->execute();
                $position++;
            }
            
            $message = 'Pertandingan berhasil dibuat!';
            $messageType = 'success';
            
            // Clear form
            $_POST = [];
        } else {
            $message = 'Gagal membuat pertandingan!';
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Match - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        function updatePlayerSelection() {
            const playType = document.getElementById('play_type').value;
            const team1Select = document.getElementById('team1_players');
            const team2Select = document.getElementById('team2_players');
            
            if (playType === 'Single') {
                team1Select.setAttribute('multiple', 'false');
                team2Select.setAttribute('multiple', 'false');
                team1Select.size = 1;
                team2Select.size = 1;
            } else {
                team1Select.setAttribute('multiple', 'true');
                team2Select.setAttribute('multiple', 'true');
                team1Select.size = 2;
                team2Select.size = 2;
            }
        }
        
        function updateGamePerSet() {
            const type = document.getElementById('game_per_set_type').value;
            const valueInput = document.getElementById('game_per_set_value');
            const valueSelect = document.getElementById('game_per_set_value_select');
            
            if (type === 'Normal') {
                valueInput.style.display = 'block';
                valueSelect.style.display = 'none';
                valueInput.value = 6;
            } else {
                valueInput.style.display = 'none';
                valueSelect.style.display = 'block';
            }
        }
    </script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-gradient-to-r from-green-600 to-blue-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="hover:text-gray-200"><i class="fas fa-arrow-left mr-2"></i>Kembali</a>
                    <h1 class="text-xl font-bold">Pengaturan Match</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm"><i class="fas fa-user mr-2"></i><?= htmlspecialchars($_SESSION['full_name']) ?></span>
                    <a href="../logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?= $messageType === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' ?>">
                <i class="fas <?= $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> mr-2"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-lg p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">
                <i class="fas fa-cog text-green-600 mr-2"></i>Buat Pertandingan Baru
            </h2>

            <form method="POST" action="" class="space-y-6">
                <!-- Match Title -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Judul Pertandingan *</label>
                    <input type="text" name="match_title" required 
                           value="<?= htmlspecialchars($_POST['match_title'] ?? '') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                           placeholder="Contoh: Final Turnamen Tenis 2024">
                </div>

                <!-- Date -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Tanggal *</label>
                    <input type="date" name="match_date" required 
                           value="<?= htmlspecialchars($_POST['match_date'] ?? date('Y-m-d')) ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>

                <!-- Play Type -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Tipe Permainan *</label>
                    <select name="play_type" id="play_type" required onchange="updatePlayerSelection()"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <option value="Single" <?= ($_POST['play_type'] ?? '') === 'Single' ? 'selected' : '' ?>>Single (Tunggal)</option>
                        <option value="Double" <?= ($_POST['play_type'] ?? '') === 'Double' ? 'selected' : '' ?>>Double (Ganda)</option>
                    </select>
                </div>

                <!-- Number of Sets -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Jumlah Set *</label>
                    <select name="number_of_sets" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?= $i ?>" <?= ($_POST['number_of_sets'] ?? 3) == $i ? 'selected' : '' ?>><?= $i ?> Set</option>
                        <?php endfor; ?>
                    </select>
                </div>

                <!-- Game per Set -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Jumlah Game per Set *</label>
                    <div class="grid grid-cols-2 gap-4">
                        <select name="game_per_set_type" id="game_per_set_type" required onchange="updateGamePerSet()"
                                class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="Normal" <?= ($_POST['game_per_set_type'] ?? 'Normal') === 'Normal' ? 'selected' : '' ?>>Normal</option>
                            <option value="BestOf" <?= ($_POST['game_per_set_type'] ?? '') === 'BestOf' ? 'selected' : '' ?>>Best Of</option>
                        </select>
                        <input type="number" name="game_per_set_value" id="game_per_set_value" required min="1" max="11"
                               value="<?= htmlspecialchars($_POST['game_per_set_value'] ?? 6) ?>"
                               class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <select name="game_per_set_value" id="game_per_set_value_select" style="display: none;"
                                class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="3">3</option>
                            <option value="5">5</option>
                            <option value="7">7</option>
                            <option value="9">9</option>
                            <option value="11">11</option>
                            <option value="13">13</option>
                            <option value="15">15</option>
                        </select>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Normal: 1-11 (default 6), Best Of: pilih dari opsi</p>
                </div>

                <!-- Deuce -->
                <div>
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" name="deuce_enabled" value="1" 
                               <?= !isset($_POST['deuce_enabled']) || $_POST['deuce_enabled'] ? 'checked' : '' ?>
                               class="w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500">
                        <span class="text-sm font-semibold text-gray-700">Aktifkan Deuce</span>
                    </label>
                </div>

                <!-- Players Selection -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-gray-200">
                    <!-- Team 1 -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-users text-blue-600 mr-2"></i>Tim 1 *
                        </label>
                        <select name="team1_players[]" id="team1_players" required multiple size="1"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <?php foreach ($players as $player): ?>
                                <option value="<?= $player['id'] ?>"><?= htmlspecialchars($player['name']) ?> (<?= getCategoryName($player['category']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Pilih 1 untuk Single, 2 untuk Double</p>
                    </div>

                    <!-- Team 2 -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-users text-red-600 mr-2"></i>Tim 2 *
                        </label>
                        <select name="team2_players[]" id="team2_players" required multiple size="1"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <?php foreach ($players as $player): ?>
                                <option value="<?= $player['id'] ?>"><?= htmlspecialchars($player['name']) ?> (<?= getCategoryName($player['category']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Pilih 1 untuk Single, 2 untuk Double</p>
                    </div>
                </div>

                <?php if (empty($players)): ?>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>
                        Belum ada pemain. <a href="players.php" class="text-blue-600 hover:underline">Tambah pemain terlebih dahulu</a>
                    </div>
                <?php endif; ?>

                <!-- Submit Button -->
                <div class="pt-4">
                    <button type="submit" 
                            class="w-full bg-gradient-to-r from-green-500 to-green-600 text-white py-3 rounded-lg font-semibold hover:from-green-600 hover:to-green-700 transition-all shadow-lg">
                        <i class="fas fa-save mr-2"></i>Simpan Pertandingan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        updatePlayerSelection();
        updateGamePerSet();
    </script>
</body>
</html>

