<?php
declare(strict_types=1);
require_once __DIR__ . '/../service/dentistNotificationService.php';

class DentistNotificationController {
    private $service;
    public function __construct($pdo) {
        $this->service = new DentistNotificationService($pdo);
    }

    public function getAll(array $dentist): void {
        $r = $this->service->getAll((int)$dentist['id_dentist']);
        http_response_code($r['code']); echo json_encode($r['body']);
    }
    public function getUnreadCount(array $dentist): void {
        $r = $this->service->getUnreadCount((int)$dentist['id_dentist']);
        http_response_code($r['code']); echo json_encode($r['body']);
    }
    public function markAsRead(array $dentist, int $id): void {
        $r = $this->service->markAsRead((int)$dentist['id_dentist'], $id);
        http_response_code($r['code']); echo json_encode($r['body']);
    }
    public function markAllAsRead(array $dentist): void {
        $r = $this->service->markAllAsRead((int)$dentist['id_dentist']);
        http_response_code($r['code']); echo json_encode($r['body']);
    }
    public function delete(array $dentist, int $id): void {
        $r = $this->service->delete((int)$dentist['id_dentist'], $id);
        http_response_code($r['code']); echo json_encode($r['body']);
    }
}