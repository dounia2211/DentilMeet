<?php
declare(strict_types=1);
require_once __DIR__ . '/../service/dentistMessageService.php';

class DentistMessageController {
    private $service;
    public function __construct($pdo) {
        $this->service = new DentistMessageService($pdo);
    }

    public function getConversations(array $dentist): void {
        $r = $this->service->getConversations((int)$dentist['id_dentist']);
        http_response_code($r['code']); echo json_encode($r['body']);
    }
    public function getMessages(array $dentist, int $patientId): void {
        $r = $this->service->getMessages((int)$dentist['id_dentist'], $patientId);
        http_response_code($r['code']); echo json_encode($r['body']);
    }
    public function send(array $dentist): void {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $r    = $this->service->send((int)$dentist['id_dentist'], $data);
        http_response_code($r['code']); echo json_encode($r['body']);
    }
}