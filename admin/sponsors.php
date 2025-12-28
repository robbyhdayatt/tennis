<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole(['admin']);

$conn = getDBConnection();
$message = '';
$messageType = '';

// Handle Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = sanitize($_POST['name']);
    
    if (empty($name) || empty($_FILES['logo']['name'])) {
        $message = 'Nama dan Logo wajib diisi!';
        $messageType = 'error';
    } else {
        $targetDir = "../assets/uploads/sponsors/";
        if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
        
        $fileName = time() . '_' . basename($_FILES["logo"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
        
        $allowTypes = array('jpg','png','jpeg','gif');
        if(in_array($fileType, $allowTypes)){
            if(move_uploaded_file($_FILES["logo"]["tmp_name"], $targetFilePath)){
                // Insert ke DB (Simpan path relatif)
                $dbPath = "assets/uploads/sponsors/" . $fileName;
                $stmt = $conn->prepare("INSERT INTO sponsors (name, logo_path) VALUES (?, ?)");
                $stmt->bind_param("ss", $name, $dbPath);
                
                if($stmt->execute()){
                    $message = 'Sponsor berhasil ditambahkan!';
                    $messageType = 'success';
                } else {
                    $message = 'Database error!';
                    $messageType = 'error';
                }
            } else {
                $message = 'Gagal mengupload gambar.';
                $messageType = 'error';
            }
        } else {
            $message = 'Hanya format JPG, JPEG, PNG, & GIF yang diperbolehkan.';
            $messageType = 'error';
        }
    }
}

// Handle Delete
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = intval($_POST['id']);
    // Ambil path file dulu untuk dihapus
    $stmt = $conn->prepare("SELECT logo_path FROM sponsors WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    
    if ($res) {
        $filePath = "../" . $res['logo_path'];
        if (file_exists($filePath)) unlink($filePath);
        
        $stmt = $conn->prepare("DELETE FROM sponsors WHERE id = ?");
        $stmt->bind_param("i", $id);
        if($stmt->execute()) {
            $message = 'Sponsor dihapus!';
            $messageType = 'success';
        }
    }
}

// Get All Sponsors
$sponsors = [];
$result = $conn->query("SELECT * FROM sponsors ORDER BY created_at DESC");
while ($row = $result->fetch_assoc()) $sponsors[] = $row;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Sponsor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <nav class="bg-gradient-to-r from-green-600 to-blue-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <a href="index.php" class="hover:text-gray-200"><i class="fas fa-arrow-left mr-2"></i>Kembali</a>
                <h1 class="text-xl font-bold">Kelola Sponsor</h1>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8 grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="md:col-span-1">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold mb-4">Tambah Sponsor</h2>
                <?php if ($message): ?>
                    <div class="mb-4 p-3 rounded text-sm <?= $messageType === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                        <?= $message ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="action" value="add">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Sponsor</label>
                        <input type="text" name="name" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Logo (PNG/JPG)</label>
                        <input type="file" name="logo" required accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                    <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 font-bold">
                        <i class="fas fa-upload mr-2"></i> Upload
                    </button>
                </form>
            </div>
        </div>

        <div class="md:col-span-2">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold mb-4">Daftar Sponsor</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <?php foreach ($sponsors as $s): ?>
                        <div class="border rounded-lg p-4 relative group hover:shadow-md transition">
                            <div class="h-24 flex items-center justify-center mb-2">
                                <img src="../<?= $s['logo_path'] ?>" alt="<?= htmlspecialchars($s['name']) ?>" class="max-h-full max-w-full object-contain">
                            </div>
                            <p class="text-center font-semibold text-sm truncate"><?= htmlspecialchars($s['name']) ?></p>
                            
                            <form method="POST" class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition" onsubmit="return confirm('Hapus sponsor ini?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                <button type="submit" class="bg-red-500 text-white w-8 h-8 rounded-full hover:bg-red-600 shadow">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>