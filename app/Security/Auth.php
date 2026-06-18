<?php
namespace App\Security;

use App\Core\Config;
use App\Core\Database;
use App\Core\Logger;

class Auth
{
    private static ?array $user = null;

    public static function attempt(string $email, string $password): bool
    {
        $db = Database::getInstance();
        $user = $db->query("SELECT * FROM users WHERE email = ?", [$email])->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            // Check tenant
            self::login($user);
            return true;
        }
        return false;
    }

    public static function login(array $user): void
    {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'site_id' => $user['site_id'] ?? null,
        ];
        self::$user = $user;
        // Log audit
        Logger::info('User logged in', ['user_id' => $user['id']]);
    }

    public static function user(): ?array
    {
        if (self::$user !== null) {
            return self::$user;
        }
        if (isset($_SESSION['user_id'])) {
            $db = Database::getInstance();
            $row = $db->query(
                "SELECT id, name, email, role, site_id FROM users WHERE id = ?",
                [$_SESSION['user_id']]
            )->fetch();
            if (is_array($row)) {
                self::$user = $row;
                return self::$user;
            }
        }
        return null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function role(): string
    {
        return self::user()['role'] ?? 'guest';
    }

    public static function siteId(): ?int
    {
        return self::user()['site_id'] ?? null;
    }

    public static function logout(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        self::$user = null;
    }
}