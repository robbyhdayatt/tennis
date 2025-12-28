<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole(['admin']);

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    $conn = getDBConnection();
    
    // Hapus data (Set, Game, & Score History akan terhapus otomatis karena Cascade)
    $stmt = $conn->prepare("DELETE FROM matches WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // Redirect dengan pesan sukses (opsional bisa ditambahkan logic flash message)
        header("Location: index.php");
    } else {
        echo "Gagal menghapus data: " . $conn->error;
    }
} else {
    header("Location: index.php");
}
exit();
?>