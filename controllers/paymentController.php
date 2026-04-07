<?php
declare(strict_types=1);
require_once __DIR__ . '/../middlewares/validateMiddlewares.php';
require_once __DIR__ . '/../service/paymentService.php';

class PaymentController {

    private $paymentService;

    public function __construct($pdo) {
        $this->paymentService = new PaymentService($pdo);
    }

    // ── pay() ─────────────────────────────────────────────────────────
    // POST /api/payments/pay
    // 🔒 Protégé
    // Body: { "id_appointment": 7, "amount": 2500.00, "method_payment": "carte" }
    public function pay(array $patient): void {
        $patientId = (int) $patient['id_patient'];
        $data      = json_decode(file_get_contents('php://input'), true) ?? [];

        $result = $this->paymentService->pay($patientId, $data);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    // ── getHistory() ──────────────────────────────────────────────────
    // GET /api/payments/history
    // 🔒 Protégé
    public function getHistory(array $patient): void {
        $patientId = (int) $patient['id_patient'];

        $result = $this->paymentService->getHistory($patientId);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    // ── getReceipt() ──────────────────────────────────────────────────
    // GET /api/payments/receipt?id_payment=5
    // 🔒 Protégé
    public function getReceipt(array $patient): void {
        $patientId = (int) $patient['id_patient'];

        $result = $this->paymentService->getReceipt($patientId, $_GET);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }
}