<?php

require_once __DIR__ . '/../service/dentistPatientProfileService.php';

class dentistPatientProfileController{
   private $service;

   public function __construct ($pdo){
     $this->service = new dentistPatientProfileService($pdo);
   }

   // GET /api/dentist/patients/:id/profile
    // Page load — returns everything in one call
    public function getProfile($dentist, $id_patient) {
        $result = $this->service->getProfile(
            $id_patient,
            $dentist['id_dentist']
        );
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }
 
    // GET /api/dentist/patients/:id/visits/all
    // "All Visits" tab clicked
    public function getAllVisits($dentist, $id_patient) {
        $result = $this->service->getAllVisits(
            $id_patient,
            $dentist['id_dentist']
        );
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }
 
    // GET /api/dentist/patients/:id/payments
    // "Payments" tab clicked
    public function getPayments($dentist, $id_patient) {
        $result = $this->service->getPayments(
            $id_patient,
            $dentist['id_dentist']
        );
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }
 
    // POST /api/dentist/patients/:id/documents
    // Upload icon in Files/Documents section
    // Uses multipart/form-data — reads from $_FILES and $_POST
    public function uploadDocument($dentist, $id_patient) {
        $file = $_FILES['file'] ?? null;
        $data = $_POST;         // document_type, notes, id_appointment
 
        if (!$file) {
            http_response_code(400);
            echo json_encode(['message' => 'No file uploaded.']);
            return;
        }
 
        $result = $this->service->uploadDocument(
            $id_patient,
            $dentist['id_dentist'],
            $file,
            $data
        );
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    // PUT /api/dentist/patients/:id/price
    // Price field + Save button at bottom
    // Body: { "id_appointment": 1, "price": 4000 }
    public function updatePrice($dentist, $id_patient) {
        $data = json_decode(file_get_contents('php://input'), true);
 
        if (!$data) {
            http_response_code(400);
            echo json_encode(['message' => 'Invalid request body.']);
            return;
        }
 
        $result = $this->service->updatePrice(
            $id_patient,
            $dentist['id_dentist'],
            $data
        );
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    // PUT /api/dentist/patients/:id/anamnesis
    // Called when: dentist edits the Anamnesis section
    public function updateAnamnesis($dentist, $id_patient) {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data) {
            http_response_code(400);
            echo json_encode(['message' => 'Invalid request body.']);
            return;
        }

        $result = $this->service->updateAnamnesis(
            (int) $id_patient,
            (int) $dentist['id_dentist'],
            $data
        );
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

}
