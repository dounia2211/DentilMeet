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
require_once 'models/appointmentModel.php';
require_once 'utils/tokenUtil.php';
require_once 'middlewares/validateMiddlewares.php';
require_once 'middlewares/authMiddlewares.php';
require_once 'service/authService.php';
require_once 'service/appointmentService.php';
require_once 'controllers/authController.php';
require_once 'controllers/appointmentController.php';

// Load .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Connect to DB
$db = new Database();
$pdo = $db->connect();


// Simple router
$requestUri =  $_SERVER['REQUEST_URI'];
$basePath='/signup/index.php';
$uri= str_replace($basePath, '', strtok($requestUri, '?'));
$uri = rtrim($uri, '/');
$method = $_SERVER['REQUEST_METHOD'];



if ($uri === '/api/auth/signup' && $method === 'POST') {
    $controller = new AuthController($pdo);
    $controller->signup();

}elseif ($uri === '/api/auth/login' && $method === 'POST') {
    $controller = new AuthController($pdo);
    $controller->login(); 

} elseif ($uri === '/api/appointments/booked-days' && $method === 'GET') {
    $controller = new AppointmentController($pdo);
    $controller->getBookedDays();

} elseif ($uri === '/api/appointments/available-slots' && $method === 'GET') {
    $controller = new AppointmentController($pdo);
    $controller->getAvailableSlots();  
    
} elseif ($uri === '/api/appointments' && $method === 'GET') {
    $patient    = AuthMiddleware::handle(); // stops if no valid token
    $controller = new AppointmentController($pdo);
    $controller->getAll($patient);

} elseif ($uri === '/api/appointments' && $method === 'POST') {
    $patient    = AuthMiddleware::handle();
    $controller = new AppointmentController($pdo);
    $controller->create($patient);

} elseif (preg_match('#^/api/appointments/(\d+)/confirmation$#', $uri, $m) && $method === 'GET') {
    $patient    = AuthMiddleware::handle();
    $controller = new AppointmentController($pdo);
    $controller->getConfirmation($patient, $m[1]);

} elseif (preg_match('#^/api/appointments/(\d+)/cancel$#', $uri, $m) && $method === 'PUT') {
    $patient    = AuthMiddleware::handle();
    $controller = new AppointmentController($pdo);
    $controller->cancel($patient, $m[1]);
 
} else {
    http_response_code(404);
    echo json_encode(['message' => 'Route not found.']);
}
