-- Schemat MySQL dla API modularnego monolitu (PDO + surowe SQL w repozytoriach).

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rate_limit_hits (
    client_key VARCHAR(64) NOT NULL,
    window_start INT UNSIGNED NOT NULL,
    hit_count INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (client_key, window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Użytkownik demonstracyjny: hasło = "password" (usuń lub zmień w produkcji)
INSERT INTO users (email, name, password_hash)
VALUES (
    'demo@example.com',
    'Demo User',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
)
ON DUPLICATE KEY UPDATE email = VALUES(email);
