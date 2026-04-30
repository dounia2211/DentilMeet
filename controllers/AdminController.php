<?php
declare(strict_types=1);

require_once __DIR__ . '/../service/adminService.php';

class AdminController {

    private $service;

    public function __construct($pdo) {
        $this->service = new AdminService($pdo);
    }

    // ══════════════════════════════════════════════════════
    //  POST /api/admin/login
    //  Pas de token requis — admin n'est pas encore connecté
    // ══════════════════════════════════════════════════════

    public function login(): void {
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $result = $this->service->login($data);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    // ══════════════════════════════════════════════════════
    //  GET /api/admin/dashboard
    //  🔒 Token admin — AuthMiddleware::handleAdmin()
    // ══════════════════════════════════════════════════════

    public function getDashboard(array $admin): void {
        $result = $this->service->getDashboard();
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    // ══════════════════════════════════════════════════════
    //  USER MANAGEMENT — TABS
    // ══════════════════════════════════════════════════════

    // GET /api/admin/users?search=Ahmed
    public function getAllUsers(array $admin): void {
        $result = $this->service->getAllUsers($_GET);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    // GET /api/admin/users/pending
    public function getPendingRequests(array $admin): void {
        $result = $this->service->getPendingRequests();
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    // GET /api/admin/users/dentists?search=Ahmed
    public function getDentists(array $admin): void {
        $result = $this->service->getDentists($_GET);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    // GET /api/admin/users/patients?search=Youcef
    public function getPatients(array $admin): void {
        $result = $this->service->getPatients($_GET);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    // ══════════════════════════════════════════════════════
    //  ACTIONS — Approve / Reject / Suspend
    // ══════════════════════════════════════════════════════

    // PUT /api/admin/dentists/{id}/approve
    public function approveDentist(array $admin, int $dentistId): void {
        $result = $this->service->approveDentist((int) $admin['id_admin'], $dentistId);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    // PUT /api/admin/dentists/{id}/reject
    public function rejectDentist(array $admin, int $dentistId): void {
        $result = $this->service->rejectDentist((int) $admin['id_admin'], $dentistId);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    // PUT /api/admin/patients/{id}/suspend
    public function suspendPatient(array $admin, int $patientId): void {
        $result = $this->service->suspendPatient((int) $admin['id_admin'], $patientId);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    // PUT /api/admin/dentists/{id}/suspend
    public function suspendDentist(array $admin, int $dentistId): void {
        $result = $this->service->suspendDentist((int) $admin['id_admin'], $dentistId);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    // ══════════════════════════════════════════════════════
    //  VIEW DETAILS
    // ══════════════════════════════════════════════════════

    // GET /api/admin/dentists/{id}
    public function getDentistDetails(array $admin, int $dentistId): void {
        $result = $this->service->getDentistDetails($dentistId);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    // GET /api/admin/patients/{id}
    public function getPatientDetails(array $admin, int $patientId): void {
        $result = $this->service->getPatientDetails($patientId);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }
}