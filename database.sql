-- ============================================================
--  database.sql — Schéma RFID Pay
--  À placer à la racine du projet (mon-projet/database.sql)
-- ============================================================

CREATE DATABASE IF NOT EXISTS rfid_payment CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE rfid_payment;

CREATE TABLE users (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    nom          VARCHAR(100) NOT NULL,
    prenom       VARCHAR(100) NOT NULL,
    email        VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    role         ENUM('admin', 'user', 'merchant') NOT NULL DEFAULT 'user',
    actif        TINYINT(1) NOT NULL DEFAULT 1,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE badges (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    uid     VARCHAR(50) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    actif   TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE comptes (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL UNIQUE,
    solde      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE transactions (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    merchant_id INT DEFAULT NULL,
    montant     DECIMAL(10,2) NOT NULL,
    type        ENUM('credit', 'paiement') NOT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)     REFERENCES users(id),
    FOREIGN KEY (merchant_id) REFERENCES users(id)
);

-- ── Données de démo ──────────────────────────────────────
-- Mot de passe par défaut : "password" pour tous les comptes
INSERT INTO users (nom, prenom, email, mot_de_passe, role) VALUES
('Admin',   'System', 'admin@rfid.local',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Durand',  'Claire', 'user@rfid.local',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
('Martin',  'Bob',    'merchant@rfid.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'merchant');

-- Comptes associés
INSERT INTO comptes (user_id, solde) VALUES (1, 0.00), (2, 50.00), (3, 0.00);

-- Badge de démo pour l'utilisateur Claire
INSERT INTO badges (uid, user_id) VALUES ('AABBCCDD', 2);
