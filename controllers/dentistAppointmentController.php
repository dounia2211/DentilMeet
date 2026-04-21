<?php

require_once __DIR__ . '/../service/dentistAppointmentService.php';

class dentistAppointmentController {
    private $dentistAppointmentService;
 
    public function __construct($pdo) {
        $this->dentistAppointmentService = new dentistAppointmentService($pdo);
    }

    //getAll()
    // Route: GET /api/dentist/appointments
    public function getAll($dentist) {
 
        // Read all filter parameters from URL query string
        $filters = [
            'search' => $_GET['search'] ?? '',
            'today'  => $_GET['today']  ?? '',
            'month'  => $_GET['month']  ?? '',
            'year'   => $_GET['year']   ?? '',
            'status' => $_GET['status'] ?? '',
        ];
 
        // Remove empty filters so they don't add useless SQL conditions
        $filters = array_filter($filters, fn($v) => $v !== '');
 
        $result = $this->dentistAppointmentService->getAll(
            $dentist['id_dentist'],
            $filters
        );
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    // getStats()
    // Route: GET /api/dentist/appointments/stats
    public function getStats($dentist) {
        $result = $this->dentistAppointmentService->getStats($dentist['id_dentist']);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    //getDetails()
    // Route: GET /api/dentist/appointments/:id/details
    public function getDetails($dentist, $id_appointment) {
        $result = $this->dentistAppointmentService->getDetails(
            $id_appointment,
            $dentist['id_dentist']
        );
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }
    

    //updatePrice()
    // Route: PUT /api/dentist/appointments/:id/price
    public function updatePrice($dentist, $id_appointment) {
        $data = json_decode(file_get_contents('php://input'), true);
 
        if (!$data) {
            http_response_code(400);
            echo json_encode(['message' => 'Invalid request body.']);
            return;
        }
 
        $result = $this->dentistAppointmentService->updatePrice(
            $id_appointment,
            $dentist['id_dentist'],
            $data
        );
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }
 
}