<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');        // allow React to call your API
header('Access-Control-Allow-Methods: POST, GET,PUT, DELETE, OPTIONS');
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

// ── Dashboard
require_once 'models/dashboardModel.php';
require_once 'service/dashboardService.php';
require_once 'controllers/dashboardController.php';

// Dentist dashboard 
require_once 'models/DentistDashboardModel.php';
require_once 'service/DentistDashboardService.php';
require_once 'controllers/DentistDashboardController.php';

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

//Profile
require_once 'models/profileModel.php';
require_once 'service/profileService.php';
require_once 'controllers/profileController.php';

//Dentists
require_once 'models/dentistModel.php';
require_once 'service/dentistService.php';
require_once 'controllers/dentistController.php';

//clinic
require_once 'models/clinicModel.php';
require_once 'service/clinicService.php';
require_once 'controllers/clinicController.php';

//notification
require_once 'models/notificationModel.php';
require_once 'service/notificationService.php';
require_once 'controllers/notificationController.php';

//dentistauth
require_once __DIR__ . '/models/dentistauthModel.php';
require_once __DIR__ . '/service/dentistauthService.php'; 
require_once __DIR__ . '/controllers/dentistauthController.php';

//dentistAppointment
require_once __DIR__ . '/models/dentistAppointmentModel.php';
require_once __DIR__ . '/service/DentistAppointmentService.php';
require_once __DIR__ . '/controllers/DentistAppointmentController.php';

//dentistPatientProfile
require_once __DIR__ . '/models/dentistPatientProfileModel.php';
require_once __DIR__ . '/service/dentistPatientProfileService.php';
require_once __DIR__ . '/controllers/dentistPatientProfileController.php';

//DENTIST NOTIFICATIONS
require_once 'models/dentistNotificationModel.php';
require_once 'service/dentistNotificationService.php';
require_once 'controllers/dentistNotificationController.php';

//dentistMessage
require_once 'models/dentistMessageModel.php';
require_once 'service/dentistMessageService.php';
require_once 'controllers/dentistMessageController.php';

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
//  DASHBOARD 
//  GET /api/dashboard
} elseif ($uri === '/api/dashboard' && $method === 'GET') {
    $patient    = AuthMiddleware::handle();
    $controller = new DashboardController($pdo);
    $controller->getDashboard($patient);

//  DENTIST DASHBOARD 

// GET /api/dentist/dashboard
// Retourne TOUT en 1 appel :
//   clinic_status, patients_seen_today, todays_patients, total_patients
//   appointments[{name,time,live}]
//   next_patient{full_name,age,gender,time,reason,visit_type,
//                allergies,medical_notes,status,due_payment}
//   requests[{name,type}]
//   review{bars[{label,percent}], average, total}
//   calendar{weeks[[{n,h,t,m}]], label, year, month}
//   trends[{date,count}]
//   unread_messages
} elseif ($uri === '/api/dentist/dashboard' && $method === 'GET') {
    $dentist    = AuthMiddleware::handleDentist();
    (new DentistDashboardController($pdo))->getDashboard($dentist);

// PUT /api/dentist/status
// Body : { "clinic_status": "Available" | "Busy" | "Offline" }
// StatCard isStatus → dropdown du design
} elseif ($uri === '/api/dentist/status' && $method === 'PUT') {
    $dentist = AuthMiddleware::handleDentist();
    (new DentistDashboardController($pdo))->updateStatus($dentist);

// PUT /api/dentist/appointments/{id}/accept
// Icône ✓ dans AppointmentRequests
} elseif (preg_match('#^/api/dentist/appointments/(\d+)/accept$#', $uri, $m) && $method === 'PUT') {
    $dentist = AuthMiddleware::handleDentist();
    (new DentistDashboardController($pdo))->acceptRequest($dentist, (int)$m[1]);

// PUT /api/dentist/appointments/{id}/refuse
// Icône ✗ dans AppointmentRequests
} elseif (preg_match('#^/api/dentist/appointments/(\d+)/refuse$#', $uri, $m) && $method === 'PUT') {
    $dentist = AuthMiddleware::handleDentist();
    (new DentistDashboardController($pdo))->refuseRequest($dentist, (int)$m[1]);

// PUT /api/dentist/appointments/{id}/start
// Bouton "Start Consultation" dans NextPatient
} elseif (preg_match('#^/api/dentist/appointments/(\d+)/start$#', $uri, $m) && $method === 'PUT') {
    $dentist = AuthMiddleware::handleDentist();
    (new DentistDashboardController($pdo))->startConsultation($dentist, (int)$m[1]);

// GET /api/dentist/patients/{id_patient}
// Bouton "view Details" dans NextPatient
} elseif (preg_match('#^/api/dentist/patients/(\d+)$#', $uri, $m) && $method === 'GET') {
    $dentist = AuthMiddleware::handleDentist();
    (new DentistDashboardController($pdo))->getPatientDetails($dentist, (int)$m[1]);

// GET /api/dentist/calendar?year=2026&month=3
// Boutons ← → pour changer de mois dans Calendar
} elseif ($uri === '/api/dentist/calendar' && $method === 'GET') {
    $dentist = AuthMiddleware::handleDentist();
    (new DentistDashboardController($pdo))->getCalendar($dentist);
    
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

//  PROFILE
// GET/api/profile 
} elseif ($uri === '/api/profile' && $method === 'GET') {
    $patient    = AuthMiddleware::handle();
    $controller = new ProfileController($pdo);
    $controller->getProfile($patient);

//POST/api/profile/photo
} elseif ($uri === '/api/profile/photo' && $method === 'POST') {
    $patient    = AuthMiddleware::handle();
    $controller = new ProfileController($pdo);
    $controller->uploadPhoto($patient);
    
//Dentists
//GET /api/dentists/suggestions
} elseif ($uri === '/api/dentists/suggestions' && $method === 'GET') {
    $controller = new DentistController($pdo);
    $controller->getSuggestions();

// GET /api/dentists/search?q=chen&page=1   
} elseif ($uri === '/api/dentists/search' && $method === 'GET') {
    $controller = new DentistController($pdo);
    $controller->search();

// GET /api/dentists/filter?speciality=Orthodontie&page=1
} elseif ($uri === '/api/dentists/filter' && $method === 'GET') {
    $controller = new DentistController($pdo);
    $controller->filter();

// GET /api/dentists/specialities   
} elseif ($uri === '/api/dentists/specialities' && $method === 'GET') {
    $controller = new DentistController($pdo);
    $controller->getSpecialities();
    
// GET /api/dentists/:id
} elseif (preg_match('#^/api/dentists/(\d+)$#', $uri, $m) && $method === 'GET') {
    $controller = new DentistController($pdo);
    $controller->getOne($m[1]);

// GET /api/dentists?page=1   
} elseif ($uri === '/api/dentists' && $method === 'GET') {
    $controller = new DentistController($pdo);
    $controller->getAll();

//clinic
} elseif (preg_match('#^/api/clinics/(\d+)/dentists$#', $uri, $m) && $method==='GET'){
    $controller = new clinicController($pdo);
    $controller -> getDentists($m[1]);

} elseif (preg_match('#^/api/clinics/(\d+)$#', $uri, $m ) && $method ==='GET'){
    $controller = new clinicController($pdo);
    $controller -> getOne($m[1]); 

// NOTIFICATION ROUTES — all require token
//order is critical

// GET /api/notifications/unread-count
// Used by: bell icon red dot on every page
} elseif ($uri === '/api/notifications/unread-count' && $method === 'GET') {
    $patient    = AuthMiddleware::handle();
    $controller = new NotificationController($pdo);
    $controller->getUnreadCount($patient);

// PUT /api/notifications/read-all
// Used by: "Clear all" button 
} elseif ($uri === '/api/notifications/read-all' && $method === 'PUT') {
    $patient    = AuthMiddleware::handle();
    $controller = new NotificationController($pdo);
    $controller->markAllAsRead($patient);
    
// PUT /api/notifications/:id/read
// Used by: clicking on a single notification to mark it read   
} elseif (preg_match('#^/api/notifications/(\d+)/read$#', $uri, $m) && $method === 'PUT') {
    $patient    = AuthMiddleware::handle();
    $controller = new NotificationController($pdo);
    $controller->markAsRead($patient, $m[1]); 

// DELETE /api/notifications/:id
// Used by: X button on a notification to delete it
} elseif (preg_match('#^/api/notifications/(\d+)$#', $uri, $m) && $method === 'DELETE') {
    $patient    = AuthMiddleware::handle();
    $controller = new NotificationController($pdo);
    $controller->delete($patient, $m[1]);

// GET /api/notifications
// Used by: opening the notification panel
// Must be LAST — most general notification route
} elseif ($uri === '/api/notifications' && $method === 'GET') {
    $patient    = AuthMiddleware::handle();
    $controller = new NotificationController($pdo);
    $controller->getAll($patient);

//  DENTIST AUTH ROUTES 
 
// POST /api/dentist/auth/signup
// Receives: multipart/form-data (has file uploads)
// No token needed — dentist is not logged in yet
} elseif ($uri === '/api/dentist/auth/signup' && $method === 'POST') {
    $controller = new DentistAuthController($pdo);
    $controller->signup();
 
// POST /api/dentist/auth/login
// No token needed — dentist is not logged in yet
} elseif ($uri === '/api/dentist/auth/login' && $method === 'POST') {
    $controller = new DentistAuthController($pdo);
    $controller->login();


// DENTIST APPOINTMENTS PAGE — NEW ROUTES
 
// GET /api/dentist/appointments/stats
// Returns: new_appointments (021), total_this_month (91), revenue (18350 DA)
// Called when: appointments page loads
} elseif ($uri === '/api/dentist/appointments/stats' && $method === 'GET') {
    $dentist    = AuthMiddleware::handleDentist();
    $controller = new DentistAppointmentController($pdo);
    $controller->getStats($dentist);
 
// GET /api/dentist/appointments/:id/details
// Returns: full appointment + patient info for the Details panel
// Called when: dentist clicks "view Details" button
} elseif (preg_match('#^/api/dentist/appointments/(\d+)/details$#', $uri, $m) && $method === 'GET') {
    $dentist    = AuthMiddleware::handleDentist();
    $controller = new DentistAppointmentController($pdo);
    $controller->getDetails($dentist, $m[1]);

 
// PUT /api/dentist/appointments/:id/price
// Updates total_price for an appointment
// Called when: dentist uses the / edit icon to set price
} elseif (preg_match('#^/api/dentist/appointments/(\d+)/price$#', $uri, $m) && $method === 'PUT') {
    $dentist    = AuthMiddleware::handleDentist();
    $controller = new DentistAppointmentController($pdo);
    $controller->updatePrice($dentist, $m[1]);
 
// GET /api/dentist/appointments
// Returns full appointments list with filters
// Called when: page loads, search used, filter applied
// MUST be last dentist appointment route
} elseif ($uri === '/api/dentist/appointments' && $method === 'GET') {
    $dentist    = AuthMiddleware::handleDentist();
    $controller = new DentistAppointmentController($pdo);
    $controller->getAll($dentist);

//  DENTIST PATIENT PROFILE ROUTES
//  All require dentist token → AuthMiddleware::handleDentist()
 
// GET /api/dentist/patients/:id/profile
// Called when: dentist opens patient profile page
// Returns: patient info + anamnesis + my visits + documents (ONE call)
} elseif (preg_match('#^/api/dentist/patients/(\d+)/profile$#', $uri, $m) && $method === 'GET') {
    $dentist = AuthMiddleware::handleDentist();
    (new DentistPatientProfileController($pdo))->getProfile($dentist, (int)$m[1]);
 
// GET /api/dentist/patients/:id/visits/all
// Called when: dentist clicks "All Visits" tab
// Returns: visits from ALL dentists for this patient
} elseif (preg_match('#^/api/dentist/patients/(\d+)/visits/all$#', $uri, $m) && $method === 'GET') {
    $dentist = AuthMiddleware::handleDentist();
    (new DentistPatientProfileController($pdo))->getAllVisits($dentist, (int)$m[1]);
 
// GET /api/dentist/patients/:id/payments
// Called when: dentist clicks "Payments" tab
// Returns: payments with Paid/Unpaid status, amount, method, related visit
} elseif (preg_match('#^/api/dentist/patients/(\d+)/payments$#', $uri, $m) && $method === 'GET') {
    $dentist = AuthMiddleware::handleDentist();
    (new DentistPatientProfileController($pdo))->getPayments($dentist, (int)$m[1]);
 
// POST /api/dentist/patients/:id/documents
// Called when: dentist clicks upload icon in Files/Documents
// Sends: multipart/form-data with file + document_type + notes
} elseif (preg_match('#^/api/dentist/patients/(\d+)/documents$#', $uri, $m) && $method === 'POST') {
    $dentist = AuthMiddleware::handleDentist();
    (new DentistPatientProfileController($pdo))->uploadDocument($dentist, (int)$m[1]);
 
// PUT /api/dentist/patients/:id/info
// Called when: dentist clicks edit icon in General information
// Body: { "address": "...", "birth_date": "1988-03-15" }
} elseif (preg_match('#^/api/dentist/patients/(\d+)/info$#', $uri, $m) && $method === 'PUT') {
    $dentist = AuthMiddleware::handleDentist();
    (new DentistPatientProfileController($pdo))->updateGeneralInfo($dentist, (int)$m[1]);
 
// PUT /api/dentist/patients/:id/price
// Called when: dentist types price and clicks Save button
// Body: { "id_appointment": 1, "price": 4000 }
} elseif (preg_match('#^/api/dentist/patients/(\d+)/price$#', $uri, $m) && $method === 'PUT') {
    $dentist = AuthMiddleware::handleDentist();
    (new DentistPatientProfileController($pdo))->updatePrice($dentist, (int)$m[1]);

// PUT /api/dentist/patients/:id/anamnesis
// Called when dentist edits medical conditions, medications, allergies, surgical history
} elseif (preg_match('#^/api/dentist/patients/(\d+)/anamnesis$#', $uri, $m) && $method === 'PUT') {
    $dentist = AuthMiddleware::handleDentist();
    (new DentistPatientProfileController($pdo))->updateAnamnesis($dentist, (int)$m[1]);

//  DENTIST AUTH ROUTES 
 
// POST /api/dentist/auth/signup
// Receives: multipart/form-data (has file uploads)
// No token needed — dentist is not logged in yet
} elseif ($uri === '/api/dentist/auth/signup' && $method === 'POST') {
    $controller = new DentistAuthController($pdo);
    $controller->signup();
 
// POST /api/dentist/auth/login
// No token needed — dentist is not logged in yet
} elseif ($uri === '/api/dentist/auth/login' && $method === 'POST') {
    $controller = new DentistAuthController($pdo);
    $controller->login();


// DENTIST NOTIFICATION
// GET /api/dentist/notifications
} elseif ($uri === '/api/dentist/notifications' && $method === 'GET') {
    $dentist = AuthMiddleware::handleDentist();
    (new DentistNotificationController($pdo))->getAll($dentist);

// GET /api/dentist/notifications/unread-count
} elseif ($uri === '/api/dentist/notifications/unread-count' && $method === 'GET') {
    $dentist = AuthMiddleware::handleDentist();
    (new DentistNotificationController($pdo))->getUnreadCount($dentist);

// PUT /api/dentist/notifications/{id}/read
} elseif (preg_match('#^/api/dentist/notifications/(\d+)/read$#', $uri, $m) && $method === 'PUT') {
    $dentist = AuthMiddleware::handleDentist();
    (new DentistNotificationController($pdo))->markAsRead($dentist, (int)$m[1]);

// PUT /api/dentist/notifications/read-all
} elseif ($uri === '/api/dentist/notifications/read-all' && $method === 'PUT') {
    $dentist = AuthMiddleware::handleDentist();
    (new DentistNotificationController($pdo))->markAllAsRead($dentist);

// DELETE /api/dentist/notifications/{id}
} elseif (preg_match('#^/api/dentist/notifications/(\d+)$#', $uri, $m) && $method === 'DELETE') {
    $dentist = AuthMiddleware::handleDentist();
    (new DentistNotificationController($pdo))->delete($dentist, (int)$m[1]);

//DENTIST MESSAGE

// GET /api/dentist/messages  — liste conversations
} elseif ($uri === '/api/dentist/messages' && $method === 'GET') {
    $dentist = AuthMiddleware::handleDentist();
    (new DentistMessageController($pdo))->getConversations($dentist);

// GET /api/dentist/messages/{id_patient}  — messages + patient details
} elseif (preg_match('#^/api/dentist/messages/(\d+)$#', $uri, $m) && $method === 'GET') {
    $dentist = AuthMiddleware::handleDentist();
    (new DentistMessageController($pdo))->getMessages($dentist, (int)$m[1]);

// POST /api/dentist/messages/send
} elseif ($uri === '/api/dentist/messages/send' && $method === 'POST') {
    $dentist = AuthMiddleware::handleDentist();
    (new DentistMessageController($pdo))->send($dentist);

    
} else {
    http_response_code(404);
    echo json_encode(['message' => 'Route not found.']);
}
