<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole(['admin']);

$conn = getDBConnection();
$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $name = sanitize($_POST['name']);
            $category = sanitize($_POST['category']);
            $team = sanitize($_POST['team'] ?? '');
            
            $stmt = $conn->prepare("INSERT INTO players (name, category, team) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $category, $team);
            
            if ($stmt->execute()) {
                $message = 'Pemain berhasil ditambahkan!';
                $messageType = 'success';
            } else {
                $message = 'Gagal menambahkan pemain!';
                $messageType = 'error';
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("DELETE FROM players WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $message = 'Pemain berhasil dihapus!';
                $messageType = 'success';
            } else {
                $message = 'Gagal menghapus pemain!';
                $messageType = 'error';
            }
        }
    }
}

// Get all players
$players = [];
$stmt = $conn->prepare("SELECT * FROM players ORDER BY category, name");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $players[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pemain - Admin</title>
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
                    <h1 class="text-xl font-bold">Kelola Pemain</h1>
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
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?= $messageType === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' ?>">
                <i class="fas <?= $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> mr-2"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Add Player Form -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">
                        <i class="fas fa-user-plus text-green-600 mr-2"></i>Tambah Pemain
                    </h2>
                    <form method="POST" action="" class="space-y-4">
                        <input type="hidden" name="action" value="add">
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Pemain *</label>
                            <input type="text" name="name" required 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Kategori *</label>
                            <select name="category" required 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                <option value="">Pilih Kategori</option>
                                <option value="MS">Men's Single (MS)</option>
                                <option value="WS">Women's Single (WS)</option>
                                <option value="MD">Men's Double (MD)</option>
                                <option value="WD">Women's Double (WD)</option>
                                <option value="XD">Mixed Double (XD)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Tim</label>
                            <input type="text" name="team" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                   placeholder="Nama tim (opsional)">
                        </div>

                        <button type="submit" 
                                class="w-full bg-gradient-to-r from-green-500 to-green-600 text-white py-2 rounded-lg font-semibold hover:from-green-600 hover:to-green-700 transition-all">
                            <i class="fas fa-plus mr-2"></i>Tambah Pemain
                        </button>
                    </form>
                </div>
            </div>

            <!-- Players List -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="px-6 py-4 bg-gradient-to-r from-gray-800 to-gray-900 text-white">
                        <h2 class="text-xl font-bold"><i class="fas fa-list mr-2"></i>Daftar Pemain</h2>
                    </div>
                    <div class="p-6">
                        <?php if (empty($players)): ?>
                            <div class="text-center py-12 text-gray-500">
                                <i class="fas fa-users text-5xl mb-4 block"></i>
                                Belum ada pemain. Tambahkan pemain baru di form sebelah.
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php
                                $grouped = [];
                                foreach ($players as $player) {
                                    $grouped[$player['category']][] = $player;
                                }
                                foreach ($grouped as $category => $categoryPlayers):
                                ?>
                                    <div class="mb-6">
                                        <h3 class="text-lg font-bold text-gray-800 mb-3 flex items-center">
                                            <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm mr-2">
                                                <?= getCategoryName($category) ?>
                                            </span>
                                            <span class="text-gray-500 text-sm">(<?= count($categoryPlayers) ?> pemain)</span>
                                        </h3>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                            <?php foreach ($categoryPlayers as $player): ?>
                                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                                    <div class="flex justify-between items-start">
                                                        <div class="flex-1">
                                                            <h4 class="font-semibold text-gray-800"><?= htmlspecialchars($player['name']) ?></h4>
                                                            <?php if ($player['team']): ?>
                                                                <p class="text-sm text-gray-600 mt-1">
                                                                    <i class="fas fa-users text-xs mr-1"></i><?= htmlspecialchars($player['team']) ?>
                                                                </p>
                                                            <?php endif; ?>
                                                        </div>
                                                        <form method="POST" action="" class="inline" onsubmit="return confirm('Hapus pemain ini?')">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="id" value="<?= $player['id'] ?>">
                                                            <button type="submit" class="text-red-600 hover:text-red-800">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

