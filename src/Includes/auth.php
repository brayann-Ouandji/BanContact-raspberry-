<?php
// src/includes/auth.php

require_once __DIR__ . '/db.php';

class Auth {

    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            session_start();
        }
    }

    public static function login(string $email, string $password): bool {
        $stmt = DB::get()->prepare(
            'SELECT id, nom, prenom, mot_de_passe, role
             FROM users WHERE email = ? AND actif = 1 LIMIT 1'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['mot_de_passe'])) {
            self::start();
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_role'] = $user['role'];   // 'admin' | 'merchant' | 'user'
            $_SESSION['user_nom']  = $user['prenom'] . ' ' . $user['nom'];
            return true;
        }
        return false;
    }

    public static function logout(): void {
        self::start();
        session_unset();
        session_destroy();
    }

    public static function check(): bool {
        self::start();
        return isset($_SESSION['user_id']);
    }

    public static function id(): int {
        return (int)($_SESSION['user_id'] ?? 0);
    }

    public static function role(): string {
        return $_SESSION['user_role'] ?? '';
    }

    public static function isAdmin(): bool    { return self::role() === 'admin'; }
    public static function isMerchant(): bool { return self::role() === 'merchant'; }
    public static function isUser(): bool     { return self::role() === 'user'; }

    /** Redirige vers login si non connecté */
    public static function require(): void {
        self::start();
        if (!self::check()) {
            header('Location: ' . BASE_URL . 'login.php');
            exit;
        }
    }

    /** Redirige si le rôle ne fait pas partie des rôles autorisés */
    public static function requireRole(string ...$roles): void {
        self::require();
        if (!in_array(self::role(), $roles, true)) {
            header('Location: ' . BASE_URL . 'dashboard.php');
            exit;
        }
    }
}
