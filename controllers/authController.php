<?php
require_once __DIR__ . '/../middlewares/validateMiddlewares.php';
require_once __DIR__ . '/../service/authService.php';
class AuthController {
    private $authService;
 
    public function __construct($pdo) {
        $this->authService = new AuthService($pdo);
    }
 
    // ── signup() ─────────────────────────────────────────────────────
    // Handles POST /api/auth/signup
    public function signup() {
 
        // reads the raw request body as a plain string
        $data = json_decode(file_get_contents('php://input'), true);
 
        // If the body is missing or not valid JSON, stop immediately
        if (!$data) {
            http_response_code(400);
            echo json_encode(['message' => 'Invalid or empty request body']);
            return;
        }
 
        // ── Run all input validations ─────────────────────────────────
        // validateSignup() checks:
        //   - username: not empty, 3-100 chars, valid characters
        //   - email: not empty, valid format, max 150 chars
        //   - password: not empty, min 8 chars
        //   - phone: valid format (only if provided)
        $errors = ValidateMiddleware::validateSignup($data);
 
        // If there are any validation errors, stop and send them to React
        // React can then display them under each field
        if (!empty($errors)) {
            http_response_code(400); // 400 Bad Request = client sent invalid data
            echo json_encode(['errors' => $errors]);
            return;
        }
 
        // ── Call the service ──────────────────────────────────────────
        // All the real work happens inside AuthService::signup()
        // It returns an array like: ['code' => 201, 'body' => [...]]
        $result = $this->authService->signup($data);
 
        // Send the HTTP status code and JSON response
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    public function login() {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data) {
            http_response_code(400);
            echo json_encode(['message' => 'Invalid or empty request body']);
            return;
        }

        $errors = ValidateMiddleware::validateLogin($data);

        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['errors' => $errors]);
            return;
        }

        $result = $this->authService->login($data);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }
}
