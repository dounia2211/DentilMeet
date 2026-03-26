<?php

require_once __DIR__ . '/../utils/tokenUtil.php';

class AuthMiddleware { // protect future routes with the token
 
 
    // ── handle() ─────────────────────────────────────────────────────
    // Call this at the top of any protected route/controller method.
    // Returns the decoded token payload (with id_patient and email) if valid.
    // Stops execution with 401 if the token is missing or invalid.
    public static function handle() {
 
        // getallheaders() returns all HTTP headers sent by the client
        $headers = getallheaders();
 
        // Get the Authorization header (case-insensitive fallback)
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
 
        // The header must start with "Bearer " followed by the token
        if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
            http_response_code(401);
            echo json_encode(['message' => 'Token manquant. Veuillez vous connecter.']);
            exit; // stop execution — do not continue to the controller
        }
 
        // Remove "Bearer " (7 characters) to get just the token string
        $token = substr($authHeader, 7);
 
        // Verify the token using TokenUtil
        $decoded = TokenUtil::verify($token);
 
        if ($decoded === null) {
            http_response_code(401);
            echo json_encode(['message' => 'Token invalide ou expiré. Veuillez vous reconnecter.']);
            exit; // stop execution
        }
 
        // Token is valid — return the payload so the controller can use it
        // Example: $patient['id_patient'] gives the logged-in patient's ID
        return $decoded;
    }
}
