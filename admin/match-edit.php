<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole(['admin']);

$matchId = intval($_GET['id'] ?? 0);
if ($matchId === 0) {
    header('Location: index.php');
    exit();
}

$conn = getDBConnection();
$message = '';
$messageType = '';

// Get match
$stmt = $conn->prepare("SELECT * FROM matches WHERE id = ?");
$stmt->bind_param("i", $matchId);
$stmt->execute();
$result = $stmt->get_result();
$match = $result->fetch_assoc();

if (!$match) {
    header('Location: index.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matchTitle = sanitize($_POST['match_title']);
    $matchDate = sanitize($_POST['match_date']);
    
    if (empty($matchTitle) || empty($matchDate)) {
        $message = 'Judul dan tanggal pertandingan wajib diisi!';
        $messageType = 'error';
    } else {
        $stmt = $conn->prepare("UPDATE matches SET match_title = ?, match_date = ? WHERE id = ?");
        $stmt->bind_param("ssi", $matchTitle, $matchDate, $matchId);
        
        if ($stmt->execute()) {
            $message = 'Pertandingan berhasil diperbarui!';
            $messageType = 'success';
            // Reload match data
            $stmt = $conn->prepare("SELECT * FROM matches WHERE id = ?");
            $stmt->bind_param("i", $matchId);
            $stmt->execute();
            $result = $stmt->get_result();
            $match = $result->fetch_assoc();
        } else {
            $message = 'Gagal memperbarui pertandingan!';
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
    <title>Edit Match - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-gradient-to-r from-green-600 to-blue-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="hover:text-gray-200"><i class="fas fa-arrow-left mr-2"></i>Kembali</a>
                    <h1 class="text-xl font-bold">Edit Pertandingan</h1>
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
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?= $messageType === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' ?>">
                <i class="fas <?= $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> mr-2"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-lg p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">
                <i class="fas fa-edit text-green-600 mr-2"></i>Edit Pertandingan
            </h2>

            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <i class="fas fa-info-circle text-yellow-600 mr-2"></i>
                <strong>Catatan:</strong> Hanya judul dan tanggal yang bisa diubah. Untuk mengubah pemain atau setting lainnya, buat pertandingan baru.
            </div>

            <form method="POST" action="" class="space-y-6">
                <!-- Match Title -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Judul Pertandingan *</label>
                    <input type="text" name="match_title" required 
                           value="<?= htmlspecialchars($match['match_title']) ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>

                <!-- Date -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Tanggal *</label>
                    <input type="date" name="match_date" required 
                           value="<?= htmlspecialchars($match['match_date']) ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>

                <!-- Read-only Info -->
                <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-200">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Tipe Permainan</label>
                        <div class="px-4 py-2 bg-gray-100 rounded-lg text-gray-600">
                            <?= getPlayTypeName($match['play_type']) ?>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Jumlah Set</label>
                        <div class="px-4 py-2 bg-gray-100 rounded-lg text-gray-600">
                            <?= $match['number_of_sets'] ?> Set
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Game per Set</label>
                        <div class="px-4 py-2 bg-gray-100 rounded-lg text-gray-600">
                            <?= $match['game_per_set_type'] === 'Normal' ? 'Normal' : 'Best Of' ?> - <?= $match['game_per_set_value'] ?>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Deuce</label>
                        <div class="px-4 py-2 bg-gray-100 rounded-lg text-gray-600">
                            <?= $match['deuce_enabled'] ? 'Ya' : 'Tidak' ?>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-4">
                    <button type="submit" 
                            class="w-full bg-gradient-to-r from-green-500 to-green-600 text-white py-3 rounded-lg font-semibold hover:from-green-600 hover:to-green-700 transition-all shadow-lg">
                        <i class="fas fa-save mr-2"></i>Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

