-- Tennis Scoreboard Database Schema
-- Run this SQL script in your MySQL database

CREATE DATABASE IF NOT EXISTS tennis_scoreboard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tennis_scoreboard;

-- Users Table (Admin, Wasit, Penonton)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'wasit', 'penonton') NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Players Table
CREATE TABLE IF NOT EXISTS players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category ENUM('MS', 'WS', 'MD', 'WD', 'XD') NOT NULL,
    team VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Matches Table
CREATE TABLE IF NOT EXISTS matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_title VARCHAR(200) NOT NULL,
    match_date DATE NOT NULL,
    play_type ENUM('Single', 'Double') NOT NULL,
    number_of_sets INT NOT NULL DEFAULT 3,
    game_per_set_type ENUM('Normal', 'BestOf') NOT NULL DEFAULT 'Normal',
    game_per_set_value INT NOT NULL DEFAULT 6,
    deuce_enabled BOOLEAN DEFAULT TRUE,
    status ENUM('pending', 'active', 'completed', 'cancelled') DEFAULT 'pending',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_date (match_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Match Players (Many-to-Many relationship)
CREATE TABLE IF NOT EXISTS match_players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT NOT NULL,
    player_id INT NOT NULL,
    team_position ENUM('team1', 'team2') NOT NULL,
    player_position ENUM('player1', 'player2') DEFAULT 'player1',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
    UNIQUE KEY unique_match_player (match_id, player_id, team_position, player_position),
    INDEX idx_match (match_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sets Table
CREATE TABLE IF NOT EXISTS sets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT NOT NULL,
    set_number INT NOT NULL,
    team1_games INT DEFAULT 0,
    team2_games INT DEFAULT 0,
    team1_tiebreak INT DEFAULT NULL,
    team2_tiebreak INT DEFAULT NULL,
    status ENUM('in_progress', 'completed') DEFAULT 'in_progress',
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    UNIQUE KEY unique_match_set (match_id, set_number),
    INDEX idx_match (match_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Games Table (Current game being played)
CREATE TABLE IF NOT EXISTS games (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT NOT NULL,
    set_id INT NOT NULL,
    game_number INT NOT NULL,
    team1_points INT DEFAULT 0,
    team2_points INT DEFAULT 0,
    serving_team ENUM('team1', 'team2') DEFAULT 'team1',
    status ENUM('in_progress', 'completed') DEFAULT 'in_progress',
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    FOREIGN KEY (set_id) REFERENCES sets(id) ON DELETE CASCADE,
    INDEX idx_match_set (match_id, set_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Score History (Audit trail)
CREATE TABLE IF NOT EXISTS score_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT NOT NULL,
    set_id INT NOT NULL,
    game_id INT NOT NULL,
    action_type ENUM('point_added', 'point_removed', 'game_completed', 'set_completed', 'match_completed') NOT NULL,
    team ENUM('team1', 'team2') DEFAULT NULL,
    old_score VARCHAR(50) DEFAULT NULL,
    new_score VARCHAR(50) DEFAULT NULL,
    updated_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    FOREIGN KEY (set_id) REFERENCES sets(id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_match (match_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (password: admin123)
-- Password hash menggunakan password_hash() PHP
INSERT INTO users (username, password, role, full_name) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Administrator'),
('wasit', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'wasit', 'Wasit Utama'),
('penonton', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'penonton', 'Penonton');

-- Note: Default password untuk semua user adalah "password"
-- Silakan ubah password setelah login pertama kali

