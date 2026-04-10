<?php

require_once __DIR__ . '/../service/clinicService.php';

class clinicController {
    private $clinicService;

    public function __construct($pdo){
        $this->clinicService = new clinicService($pdo);
    }

    // Route: GET /api/clinics/:id
    public function getOne($id_clinic) {
        $result = $this->clinicService->getOne($id_clinic);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    // Route: GET /api/clinics/:id/dentists
    public function getDentists($id_clinic) {
        $result = $this->clinicService->getDentists($id_clinic);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }
}