<?php
/**
 * Script untuk reset password default
 * Hapus file ini setelah digunakan untuk keamanan
 */

require_once '../config/config.php';

$conn = getDBConnection();

// Reset password untuk semua user ke "password"
$password = password_hash('password', PASSWORD_DEFAULT);

$users = ['admin', 'wasit', 'penonton'];

foreach ($users as $username) {
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
    $stmt->bind_param("ss", $password, $username);
    if ($stmt->execute()) {
        echo "Password untuk $username berhasil di-reset ke 'password'<br>";
    } else {
        echo "Error reset password untuk $username<br>";
    }
}

echo "<br>Password reset selesai!";
echo "<br><strong>Hapus file ini setelah digunakan untuk keamanan!</strong>";
?>

