<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;


class TokenUtil {

    
    private static $algo   = "HS256";

    // Generate token after signup / login
    public static function generate($id_patient, $email) {
        $secret = $_ENV['JWT_SECRET'] ?? 'fallback_secret_change_this'; //Gets the JWT secret from the environment variable.If not set, uses 'fallback_secret_change_this' as default.
        $days   = (int)($_ENV['JWT_EXPIRY_DAYS'] ?? 7);
        $now    = time();

        $payload = [
            'iss'         => 'dentilmeet',        // issuer (your app name)
            'iat'         => time(),               // issued at
            'exp'         => time() + (60 * 60 * 24 * 7), // expires in 7 days
            'id_patient'  => $id_patient,
            'email'       => $email
        ];

        return JWT::encode($payload, $secret, self::$algo);
    }

    // Decode & verify token (use this in protected routes later)
    public static function verify($token) {
        try {
           $secret  = $_ENV['JWT_SECRET'] ?? 'fallback_secret_change_this';
            $decoded = JWT::decode($token, new Key($secret, self::$algo));
            return (array) $decoded;
        } catch (Exception $e) {
            return null; // token invalid or expired
        }
    }
}
