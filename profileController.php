<?php
declare(strict_types=1);
require_once __DIR__ . '/../service/profileService.php';

class ProfileController {

    private $profileService;

    public function __construct($pdo) {
        $this->profileService = new ProfileService($pdo);
    }

    // ─────────────────────────────────────────────────────────────────
    // GET /api/profile
    // 🔒 Protégé — AuthMiddleware::handle() appelé dans index.php
    // Retourne toutes les données de la page profil
    // ─────────────────────────────────────────────────────────────────
    public function getProfile(array $patient): void {
        $patientId = (int) $patient['id_patient'];

        $result = $this->profileService->getProfile($patientId);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    // ─────────────────────────────────────────────────────────────────
    // POST /api/profile/photo
    // 🔒 Protégé
    // Envoi en multipart/form-data avec la clé "photo"
    // ─────────────────────────────────────────────────────────────────
    public function uploadPhoto(array $patient): void {
        $patientId = (int) $patient['id_patient'];

        $result = $this->profileService->uploadPhoto($patientId, $_FILES['photo'] ?? []);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }
}