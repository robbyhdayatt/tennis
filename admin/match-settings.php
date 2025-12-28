<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole(['admin']);

$conn = getDBConnection();
$message = '';
$messageType = '';

// Get Players & Sponsors
$players = [];
$res = $conn->query("SELECT * FROM players ORDER BY category, name");
while ($r = $res->fetch_assoc()) $players[] = $r;

$sponsors = [];
$res = $conn->query("SELECT * FROM sponsors ORDER BY name");
while ($r = $res->fetch_assoc()) $sponsors[] = $r;

// Handle Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matchTitle = sanitize($_POST['match_title']);
    $matchDate = sanitize($_POST['match_date']);
    $playType = sanitize($_POST['play_type']);
    $numberOfSets = intval($_POST['number_of_sets']);
    $gamePerSetType = sanitize($_POST['game_per_set_type']);
    $gamePerSetValue = intval($_POST['game_per_set_value']);
    $deuceEnabled = isset($_POST['deuce_enabled']) ? 1 : 0;
    
    // Ambil array sponsors (bisa kosong)
    $selectedSponsors = $_POST['sponsors'] ?? [];
    
    $team1 = $_POST['team1'] ?? [];
    $team2 = $_POST['team2'] ?? [];
    
    // Validation
    $requiredCount = ($playType === 'Single') ? 1 : 2;
    
    if (count($team1) !== $requiredCount || count($team2) !== $requiredCount) {
        $message = "Untuk $playType, setiap tim harus memiliki tepat $requiredCount pemain!";
        $messageType = 'error';
    } else {
        $userId = $_SESSION['user_id'];
        
        // 1. Insert Match
        $stmt = $conn->prepare("INSERT INTO matches (match_title, match_date, play_type, number_of_sets, game_per_set_type, game_per_set_value, deuce_enabled, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssisiii", $matchTitle, $matchDate, $playType, $numberOfSets, $gamePerSetType, $gamePerSetValue, $deuceEnabled, $userId);
        
        if ($stmt->execute()) {
            $matchId = $conn->insert_id;
            
            // 2. Insert Sponsors ke tabel match_sponsors
            if (!empty($selectedSponsors)) {
                $stmtSpon = $conn->prepare("INSERT INTO match_sponsors (match_id, sponsor_id) VALUES (?, ?)");
                foreach ($selectedSponsors as $sid) {
                    $stmtSpon->bind_param("ii", $matchId, $sid);
                    $stmtSpon->execute();
                }
            }
            
            // 3. Insert Players
            foreach (['team1' => $team1, 'team2' => $team2] as $teamName => $members) {
                $pos = 1;
                foreach ($members as $pid) {
                    $stmt2 = $conn->prepare("INSERT INTO match_players (match_id, player_id, team_position, player_position) VALUES (?, ?, ?, ?)");
                    $pPos = 'player' . $pos++;
                    $stmt2->bind_param("iiss", $matchId, $pid, $teamName, $pPos);
                    $stmt2->execute();
                }
            }
            $message = 'Pertandingan berhasil dibuat!';
            $messageType = 'success';
        } else {
            $message = 'Error: ' . $conn->error;
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
    <title>Setup Match</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Style untuk checkbox Pemain & Sponsor */
        .check-card:checked + div { border-color: #2563EB; background-color: #EFF6FF; }
        .check-card:checked + div .check-icon { display: block; }
        
        /* Custom Scrollbar */
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="bg-gray-50">
    <nav class="bg-gradient-to-r from-green-600 to-blue-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 py-4 flex items-center space-x-4">
            <a href="index.php"><i class="fas fa-arrow-left"></i> Kembali</a>
            <h1 class="text-xl font-bold">Setup Pertandingan Baru</h1>
        </div>
    </nav>

    <div class="max-w-5xl mx-auto px-4 py-8">
        <?php if ($message): ?>
            <div class="p-4 mb-6 rounded-lg <?= $messageType==='success'?'bg-green-100 text-green-700':'bg-red-100 text-red-700' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="bg-white rounded-xl shadow-lg p-8 space-y-8" id="matchForm">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Judul Event</label>
                    <input type="text" name="match_title" required class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" placeholder="Final Cup 2024">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Tanggal</label>
                    <input type="date" name="match_date" required value="<?= date('Y-m-d') ?>" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Tipe Permainan</label>
                    <select name="play_type" id="play_type" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" onchange="resetSelection()">
                        <option value="Single">Single (1 vs 1)</option>
                        <option value="Double">Double (2 vs 2)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Sponsor (Boleh Pilih Banyak)</label>
                    <div class="border rounded-lg p-3 bg-gray-50 h-[100px] overflow-y-auto custom-scrollbar">
                        <?php if(empty($sponsors)): ?>
                            <p class="text-sm text-gray-400 italic p-2">Belum ada data sponsor.</p>
                        <?php else: ?>
                            <div class="grid grid-cols-1 gap-2">
                                <?php foreach($sponsors as $s): ?>
                                    <label class="flex items-center space-x-3 cursor-pointer bg-white p-2 rounded border hover:bg-blue-50 transition-colors">
                                        <input type="checkbox" name="sponsors[]" value="<?= $s['id'] ?>" class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500">
                                        <div class="flex items-center space-x-2 overflow-hidden">
                                            <?php if(!empty($s['logo_path'])): ?>
                                                <img src="../<?= htmlspecialchars($s['logo_path']) ?>" class="h-6 w-6 object-contain flex-shrink-0">
                                            <?php else: ?>
                                                <i class="fas fa-image text-gray-300"></i>
                                            <?php endif; ?>
                                            <span class="text-sm font-medium text-gray-700 truncate"><?= htmlspecialchars($s['name']) ?></span>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4 bg-gray-50 p-4 rounded-lg">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Set</label>
                        <select name="number_of_sets" class="w-full border rounded px-2 py-2">
                            <option value="1">1 Set</option>
                            <option value="3" selected>3 Set (Best of 3)</option>
                            <option value="5">5 Set (Best of 5)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Game Rules</label>
                        <div class="flex space-x-2">
                            <select name="game_per_set_type" id="game_type" class="border rounded px-2 py-2 bg-white flex-1" onchange="toggleGameValue()">
                                <option value="Normal">Normal</option>
                                <option value="BestOf">Best Of</option>
                            </select>
                            <input type="number" id="val_normal" value="6" min="1" class="border rounded px-2 py-2 w-20 text-center" onchange="updateRealValue()" placeholder="6">
                            <select id="val_bestof" class="border rounded px-2 py-2 w-20 bg-white hidden" onchange="updateRealValue()">
                                <?php foreach([3, 5, 7, 9, 11, 13, 15] as $val): ?>
                                    <option value="<?= $val ?>"><?= $val ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="game_per_set_value" id="real_value" value="6">
                        </div>
                        <p class="text-[10px] text-gray-400 mt-1" id="game_desc">First to 6 games wins set</p>
                    </div>

                    <div class="flex items-center mt-6 md:mt-0">
                        <label class="flex items-center space-x-2 cursor-pointer select-none">
                            <input type="checkbox" name="deuce_enabled" value="1" checked class="w-5 h-5 text-blue-600 rounded">
                            <span class="font-bold text-gray-700">Aktifkan Deuce?</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 border-t pt-6">
                <div>
                    <h3 class="font-bold text-blue-600 text-lg mb-3 border-b-2 border-blue-600 pb-1">Pilih Pemain TIM 1</h3>
                    <p class="text-xs text-gray-500 mb-3" id="limit_info_1">Pilih 1 pemain</p>
                    <div class="h-64 overflow-y-auto space-y-2 pr-2 custom-scrollbar border rounded-lg bg-gray-50 p-2">
                        <?php foreach ($players as $p): ?>
                            <label class="cursor-pointer block relative">
                                <input type="checkbox" name="team1[]" value="<?= $p['id'] ?>" class="check-card absolute opacity-0 w-0 h-0" onchange="validateLimit('team1', this)">
                                <div class="border rounded-lg p-3 hover:bg-white bg-white shadow-sm mb-2 transition flex justify-between items-center">
                                    <div>
                                        <div class="font-bold text-gray-800"><?= htmlspecialchars($p['name']) ?></div>
                                        <div class="text-xs text-gray-500"><?= $p['category'] ?></div>
                                    </div>
                                    <i class="fas fa-check-circle text-blue-600 hidden check-icon"></i>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div>
                    <h3 class="font-bold text-red-600 text-lg mb-3 border-b-2 border-red-600 pb-1">Pilih Pemain TIM 2</h3>
                    <p class="text-xs text-gray-500 mb-3" id="limit_info_2">Pilih 1 pemain</p>
                    <div class="h-64 overflow-y-auto space-y-2 pr-2 custom-scrollbar border rounded-lg bg-gray-50 p-2">
                        <?php foreach ($players as $p): ?>
                            <label class="cursor-pointer block relative">
                                <input type="checkbox" name="team2[]" value="<?= $p['id'] ?>" class="check-card absolute opacity-0 w-0 h-0" onchange="validateLimit('team2', this)">
                                <div class="border rounded-lg p-3 hover:bg-white bg-white shadow-sm mb-2 transition flex justify-between items-center">
                                    <div>
                                        <div class="font-bold text-gray-800"><?= htmlspecialchars($p['name']) ?></div>
                                        <div class="text-xs text-gray-500"><?= $p['category'] ?></div>
                                    </div>
                                    <i class="fas fa-check-circle text-blue-600 hidden check-icon"></i>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-xl shadow-lg transition transform hover:scale-[1.01]">
                Simpan & Buat Pertandingan
            </button>
        </form>
    </div>

    <script>
        // --- Logic Game/Set Rules ---
        function toggleGameValue() {
            const type = document.getElementById('game_type').value;
            const inputNormal = document.getElementById('val_normal');
            const selectBestOf = document.getElementById('val_bestof');
            const desc = document.getElementById('game_desc');

            if (type === 'Normal') {
                inputNormal.classList.remove('hidden');
                selectBestOf.classList.add('hidden');
                desc.textContent = "First to X games wins set";
            } else {
                inputNormal.classList.add('hidden');
                selectBestOf.classList.remove('hidden');
                desc.textContent = "Majority wins (e.g. Best of 7 = First to 4)";
            }
            updateRealValue();
        }

        function updateRealValue() {
            const type = document.getElementById('game_type').value;
            const val = type === 'Normal' 
                ? document.getElementById('val_normal').value 
                : document.getElementById('val_bestof').value;
            document.getElementById('real_value').value = val;
        }

        // --- Logic Player Selection ---
        function getLimit() {
            return document.getElementById('play_type').value === 'Single' ? 1 : 2;
        }

        function resetSelection() {
            const limit = getLimit();
            document.querySelectorAll('.check-card').forEach(cb => {
                cb.checked = false;
                cb.disabled = false;
            });
            document.getElementById('limit_info_1').textContent = `Pilih ${limit} pemain`;
            document.getElementById('limit_info_2').textContent = `Pilih ${limit} pemain`;
        }

        function validateLimit(teamName, checkbox) {
            const limit = getLimit();
            const checkedBoxes = document.querySelectorAll(`input[name="${teamName}[]"]:checked`);
            
            if (checkedBoxes.length > limit) {
                checkbox.checked = false;
                alert(`Maksimal ${limit} pemain untuk tipe ini!`);
                return;
            }

            const otherTeam = teamName === 'team1' ? 'team2' : 'team1';
            const thisVal = checkbox.value;
            const otherCheckbox = document.querySelector(`input[name="${otherTeam}[]"][value="${thisVal}"]`);
            
            if (otherCheckbox) {
                if (checkbox.checked) {
                    if (otherCheckbox.checked) {
                        alert('Pemain ini sudah dipilih di tim lawan!');
                        checkbox.checked = false;
                    } else {
                        otherCheckbox.disabled = true;
                    }
                } else {
                    otherCheckbox.disabled = false;
                }
            }
        }
        toggleGameValue();
    </script>
</body>
</html>