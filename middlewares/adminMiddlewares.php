<?php

require_once __DIR__ . '/../utils/TokenUtil.php';

class adminMiddlewares {
    public static function handle(): array {
        $headers    = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
 
        if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
            http_response_code(401);
            echo json_encode(['message' => 'Admin token missing.']);
            exit;
        }
 
        $token   = substr($authHeader, 7);
        $decoded = TokenUtil::verify($token);
 
        // Check token is valid AND belongs to an admin
        if (!$decoded || !isset($decoded['id_admin']) || $decoded['role'] !== 'admin') {
            http_response_code(401);
            echo json_encode(['message' => 'Invalid or expired admin token.']);
            exit;
        }
 
        return $decoded;
    } 

    //Permission check helpers 
    // Call these inside admin routes that need specific permissions.
 
    // Checks admin can validate dentist applications
    public static function requireValidateDentist(array $admin): void {
        if (!$admin['can_validate_dentist']) {
            http_response_code(403);
            echo json_encode(['message' => 'You do not have permission to validate dentists.']);
            exit;
        }
    }

    // Checks admin can suspend accounts
    public static function requireSuspend(array $admin): void {
        if (!$admin['can_suspend']) {
            http_response_code(403);
            echo json_encode(['message' => 'You do not have permission to suspend accounts.']);
            exit;
        }
    }
}