<?php

require_once __DIR__ . '/../service/appointmentService.php';
require_once __DIR__ . '/../middlewares/validateMiddlewares.php';

class appointmentController {
    private $appointmentService;

    public function __construct($pdo) {
        $this->appointmentService = new appointmentService($pdo);
    }

    public function getBookedDays() {
 
        // Read from URL query string
        $id_dentist = $_GET['id_dentist'] ?? null;
        $year       = $_GET['year']       ?? null;
        $month      = $_GET['month']      ?? null;
 
        $result = $this->appointmentService->getBookedDays($id_dentist, $year, $month);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    public function getAvailableSlots() {
 
        $id_dentist = $_GET['id_dentist'] ?? null;
        $date       = $_GET['date']       ?? null;
 
        $result = $this->appointmentService->getAvailableSlots($id_dentist, $date);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    public function create($patient) {
 
        // Read the JSON body React sent
        $data = json_decode(file_get_contents('php://input'), true);
 
        // If body is empty or not valid JSON → stop immediately
        if (!$data) {
            http_response_code(400);
            echo json_encode(['message' => 'Request body is empty or invalid JSON.']);
            return;
        }
 
        // Call service — pass the data AND the logged-in patient's ID
        // $patient['id_patient'] comes from the JWT token (via AuthMiddleware)
        $result = $this->appointmentService->create($data, $patient['id_patient']);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    public function getConfirmation($patient, $id_appointment) {
 
        $result = $this->appointmentService->getConfirmation(
            $patient['id_patient'],
            $id_appointment
        );
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    public function cancel($patient, $id_appointment) {
 
        $result = $this->appointmentService->cancel(
            $patient['id_patient'],
            $id_appointment
        );
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    public function getAll($patient) {
 
        $result = $this->appointmentService->getAll($patient['id_patient']);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }


}