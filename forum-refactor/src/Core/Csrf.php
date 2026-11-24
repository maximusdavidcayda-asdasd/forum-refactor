<?php
declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY]) || !is_string($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public static function verify(?string $submittedToken): bool
    {
        $stored = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_string($stored) || $stored === '' || !is_string($submittedToken) || $submittedToken === '') {
            return false;
        }

        return hash_equals($stored, $submittedToken);
    }
}