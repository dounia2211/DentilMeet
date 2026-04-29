<?php

require_once __DIR__ . '/../service/adminService.php';

class adminController{
    private $adminService;
    public function __construct($pdo){
        $this->adminService = new adminService($pdo);
    }

    // POST /api/admin/auth/login
    // Body: { "email": "admin@dentilmeet.com", "password": "..." }
    public function login() {
        $data = json_decode(file_get_contents('php://input'), true);
 
        if (!$data) {
            http_response_code(400);
            echo json_encode(['message' => 'Invalid request body.']);
            return;
        }
 
        $result = $this->adminService->login($data);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }
}