<?php
require_once 'config/config.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    $role = $_SESSION['role'];
    if ($role === 'admin') {
        header('Location: ' . BASE_URL . 'admin/index.php');
    } elseif ($role === 'wasit') {
        header('Location: ' . BASE_URL . 'wasit/index.php');
    } else {
        header('Location: ' . BASE_URL . 'penonton/index.php');
    }
    exit();
} else {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}
?>

