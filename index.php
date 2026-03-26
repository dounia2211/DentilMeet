<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');        // allow React to call your API
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight request from React
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'vendor/autoload.php';
require_once 'config/database.php';
require_once 'models/patientModels.php';
require_once 'utils/tokenUtil.php';
require_once 'middlewares/validateMiddlewares.php';
require_once 'middlewares/authMiddlewares.php';
require_once 'service/authService.php';
require_once 'controllers/authController.php';

// Load .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Connect to DB
$db = new Database();
$pdo = $db->connect();


// Simple router
$uri = strtok($_SERVER['PATH_INFO'] ?? $_SERVER['REQUEST_URI'], '?');
$method = $_SERVER['REQUEST_METHOD'];



if ($uri === '/api/auth/signup' && $method === 'POST') {
    $controller = new AuthController($pdo);
    $controller->signup();

} else {
    http_response_code(404);
    echo json_encode(['message' => 'Route not found.']);
}