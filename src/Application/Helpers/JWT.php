<?php

namespace App\Application\Helpers;

use Firebase\JWT\JWT as FirebaseJWT;
use Firebase\JWT\Key;
use Exception;

class JWT
{
    private static string $secret = 'balsas-v2-2025';

    public static function generateToken(array $payload): string
    {
        $issuedAt = time();
        $expire = $issuedAt + 60*60;
        $payload = array_merge($payload, [
            'iat' => $issuedAt,
            'exp' => $expire,
        ]);

        return FirebaseJWT::encode($payload, self::$secret, 'HS256');
    }

    public static function validateToken(string $token): object
    {
        return FirebaseJWT::decode($token, new Key(self::$secret, 'HS256'));
    }
}
