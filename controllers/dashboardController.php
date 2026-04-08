<?php
declare(strict_types=1);

require_once __DIR__ . '/../service/dashboardService.php';

class DashboardController {

    private $dashboardService;

    public function __construct($pdo) {
        $this->dashboardService = new DashboardService($pdo);
    }

    // ── getDashboard() ────────────────────────────────────────
    // GET /api/dashboard
    // 🔒 Protégé — token JWT obligatoire
    //
    // Ce que FRONT envoie :
    //   Header: Authorization: Bearer <token>
    //   (rien dans le body, rien dans le query string)
    //
    // Ce que FRONT reçoit :
    // {
    //   "favorites_count": 5,
    //   "upcoming_appointments_count": 2,
    //   "new_messages_count": 1,
    //   "remaining_payment": 3500,
    //   "suggestions": [
    //     {
    //       "id_dentist": 3,
    //       "full_name": "Dr. Meziane Ali",
    //       "speciality": "Orthodontie",
    //       "photo": "meziane.jpg",
    //       "avg_rating": 4.8
    //     },
    //     { ... },
    //     { ... },
    //     { ... }
    //   ]
    // }
    public function getDashboard(array $patient): void {
        $patientId = (int) $patient['id_patient'];

        $result = $this->dashboardService->getDashboard($patientId);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }
}