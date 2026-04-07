<?php
declare(strict_types=1);
require_once __DIR__ . '/../middlewares/validateMiddlewares.php';
require_once __DIR__ . '/../service/favoriteService.php';

class FavoriteController {

    private $favoriteService;

    public function __construct($pdo) {
        $this->favoriteService = new FavoriteService($pdo);
    }

    // ── add() ─────────────────────────────────────────────────────────
    // POST /api/favorites/add
    // 🔒 Protégé — AuthMiddleware::handle() appelé dans index.php
    // Body: { "id_dentist": 3 }
    public function add(array $patient): void {
        $patientId = (int) $patient['id_patient'];
        $data      = json_decode(file_get_contents('php://input'), true) ?? [];

        $result = $this->favoriteService->add($patientId, $data);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    // ── remove() ──────────────────────────────────────────────────────
    // DELETE /api/favorites/remove
    // 🔒 Protégé
    // Body: { "id_dentist": 3 }
    public function remove(array $patient): void {
        $patientId = (int) $patient['id_patient'];
        $data      = json_decode(file_get_contents('php://input'), true) ?? [];

        $result = $this->favoriteService->remove($patientId, $data);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    // ── list() ────────────────────────────────────────────────────────
    // GET /api/favorites
    // 🔒 Protégé
    public function list(array $patient): void {
        $patientId = (int) $patient['id_patient'];

        $result = $this->favoriteService->getAll($patientId);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }
}