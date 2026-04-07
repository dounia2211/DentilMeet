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
require_once 'utils/tokenUtil.php';
require_once 'middlewares/validateMiddlewares.php';
require_once 'middlewares/authMiddlewares.php';

// ── Auth 
require_once 'models/patientModels.php';
require_once 'service/authService.php';
require_once 'controllers/authController.php';

// ── Appointments 
require_once 'models/appointmentModel.php';
require_once 'service/appointmentService.php';
require_once 'controllers/appointmentController.php';

// ── Favorites 
require_once 'models/favoriteModel.php';
require_once 'service/favoriteService.php';
require_once 'controllers/favoriteController.php';

// ── Reviews (Ratings)
require_once 'models/ratingModel.php';
require_once 'service/ratingService.php';
require_once 'controllers/ratingController.php';

// ── Messages 
require_once 'models/messageModel.php';
require_once 'service/messageService.php';
require_once 'controllers/messageController.php';

// ── Payments
require_once 'models/paymentModel.php';
require_once 'service/paymentService.php';
require_once 'controllers/paymentController.php';

// Load .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Connect to DB
$db = new Database();
$pdo = $db->connect();


// Simple router
$requestUri =  $_SERVER['REQUEST_URI'];
$basePath='/DentilMeet-main';
$uri    = strtok($_SERVER['PATH_INFO'] ?? $_SERVER['REQUEST_URI'], '?');
$method = $_SERVER['REQUEST_METHOD'];
//  AUTH
// POST /api/auth/signup
if ($uri === '/api/auth/signup' && $method === 'POST') {
    $controller = new AuthController($pdo);
    $controller->signup();

// POST /api/auth/login
}elseif ($uri === '/api/auth/login' && $method === 'POST') {
    $controller = new AuthController($pdo);
    $controller->login(); 

//  APPOINTMENTS
// GET /api/appointments/booked-days?id_dentist=1&year=2026&month=4
} elseif ($uri === '/api/appointments/booked-days' && $method === 'GET') {
    $controller = new AppointmentController($pdo);
    $controller->getBookedDays();

// GET /api/appointments/available-slots?id_dentist=1&date=2026-04-10
} elseif ($uri === '/api/appointments/available-slots' && $method === 'GET') {
    $controller = new AppointmentController($pdo);
    $controller->getAvailableSlots();  
    
// GET /api/appointments
} elseif ($uri === '/api/appointments' && $method === 'GET') {
    $patient    = AuthMiddleware::handle(); // stops if no valid token
    $controller = new AppointmentController($pdo);
    $controller->getAll($patient);

// POST /api/appointments
} elseif ($uri === '/api/appointments' && $method === 'POST') {
    $patient    = AuthMiddleware::handle();
    $controller = new AppointmentController($pdo);
    $controller->create($patient);

// GET /api/appointments/{id}/confirmation
} elseif (preg_match('#^/api/appointments/(\d+)/confirmation$#', $uri, $m) && $method === 'GET') {
    $patient    = AuthMiddleware::handle();
    $controller = new AppointmentController($pdo);
    $controller->getConfirmation($patient, $m[1]);

// PUT /api/appointments/{id}/cancel
} elseif (preg_match('#^/api/appointments/(\d+)/cancel$#', $uri, $m) && $method === 'PUT') {
    $patient    = AuthMiddleware::handle();
    $controller = new AppointmentController($pdo);
    $controller->cancel($patient, $m[1]);

//  FAVORITES 
// POST /api/favorites/add
} elseif ($uri === '/api/favorites/add' && $method === 'POST') {
    $patient    = AuthMiddleware::handle();
    $controller = new FavoriteController($pdo);
    $controller->add($patient);

// DELETE /api/favorites/remove
} elseif ($uri === '/api/favorites/remove' && $method === 'DELETE') {
    $patient    = AuthMiddleware::handle();
    $controller = new FavoriteController($pdo);
    $controller->remove($patient);

// GET /api/favorites
} elseif ($uri === '/api/favorites' && $method === 'GET') {
    $patient    = AuthMiddleware::handle();
    $controller = new FavoriteController($pdo);
    $controller->list($patient);

//  REVIEWS (RATINGS)
// POST /api/reviews/submit
} elseif ($uri === '/api/reviews/submit' && $method === 'POST') {
    $patient    = AuthMiddleware::handle();
    $controller = new RatingController($pdo);
    $controller->submit($patient);

// GET /api/reviews?id_dentist=3
} elseif ($uri === '/api/reviews' && $method === 'GET') {
    $controller = new RatingController($pdo);
    $controller->getForDentist();

//  MESSAGES
// POST /api/messages/send
} elseif ($uri === '/api/messages/send' && $method === 'POST') {
    $patient    = AuthMiddleware::handle();
    $controller = new MessageController($pdo);
    $controller->send($patient);

// GET /api/messages/conversation?id_dentist=3
} elseif ($uri === '/api/messages/conversation' && $method === 'GET') {
    $patient    = AuthMiddleware::handle();
    $controller = new MessageController($pdo);
    $controller->getConversation($patient);

// GET /api/messages/inbox
} elseif ($uri === '/api/messages/inbox' && $method === 'GET') {
    $patient    = AuthMiddleware::handle();
    $controller = new MessageController($pdo);
    $controller->getInbox($patient);

//  PAYMENTS
// POST /api/payments/pay
} elseif ($uri === '/api/payments/pay' && $method === 'POST') {
    $patient    = AuthMiddleware::handle();
    $controller = new PaymentController($pdo);
    $controller->pay($patient);

// GET /api/payments/history
} elseif ($uri === '/api/payments/history' && $method === 'GET') {
    $patient    = AuthMiddleware::handle();
    $controller = new PaymentController($pdo);
    $controller->getHistory($patient);

// GET /api/payments/receipt?id_payment=5
} elseif ($uri === '/api/payments/receipt' && $method === 'GET') {
    $patient    = AuthMiddleware::handle();
    $controller = new PaymentController($pdo);
    $controller->getReceipt($patient);


} else {
    http_response_code(404);
    echo json_encode(['message' => 'Route not found.']);
}
