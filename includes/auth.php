<?php
// Authentication Functions

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit();
    }
}

function requireRole($allowedRoles) {
    requireLogin();
    if (!in_array($_SESSION['role'], $allowedRoles)) {
        header('Location: ' . BASE_URL . 'index.php?error=unauthorized');
        exit();
    }
}

function login($username, $password) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id, username, password, role, full_name FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            return true;
        }
    }
    return false;
}

function logout() {
    session_unset();
    session_destroy();
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role'],
            'full_name' => $_SESSION['full_name']
        ];
    }
    return null;
}
?>

