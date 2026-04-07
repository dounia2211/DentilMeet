<?php
declare(strict_types=1);
require_once __DIR__ . '/../middlewares/validateMiddlewares.php';
require_once __DIR__ . '/../service/ratingService.php';

class RatingController {

    private $ratingService;

    public function __construct($pdo) {
        $this->ratingService = new RatingService($pdo);
    }

    // ── submit() ──────────────────────────────────────────────────────
    // POST /api/reviews/submit
    // 🔒 Protégé
    // Body: { "id_dentist": 3, "id_appointment": 7, "rating": 5, "comment": "Super!" }
    public function submit(array $patient): void {
        $patientId = (int) $patient['id_patient'];
        $data      = json_decode(file_get_contents('php://input'), true) ?? [];

        $result = $this->ratingService->submit($patientId, $data);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    // ── getForDentist() ───────────────────────────────────────────────
    // GET /api/reviews?id_dentist=3
    // 🌐 Public — pas de token
    public function getForDentist(): void {
        $dentistId = (int)($_GET['id_dentist'] ?? 0);

        $result = $this->ratingService->getForDentist($dentistId);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }
}