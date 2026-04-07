<?php
declare(strict_types=1);
require_once __DIR__ . '/../middlewares/validateMiddlewares.php';
require_once __DIR__ . '/../service/messageService.php';

class MessageController {

    private $messageService;

    public function __construct($pdo) {
        $this->messageService = new MessageService($pdo);
    }

    // ── send() ────────────────────────────────────────────────────────
    // POST /api/messages/send
    // 🔒 Protégé
    // Body: { "id_dentist": 3, "message_text": "Bonjour!", "id_appointment": 7 }
    public function send(array $patient): void {
        $patientId = (int) $patient['id_patient'];
        $data      = json_decode(file_get_contents('php://input'), true) ?? [];

        $result = $this->messageService->send($patientId, $data);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    // ── getConversation() ─────────────────────────────────────────────
    // GET /api/messages/conversation?id_dentist=3
    // 🔒 Protégé
    public function getConversation(array $patient): void {
        $patientId = (int) $patient['id_patient'];

        $result = $this->messageService->getConversation($patientId, $_GET);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    // ── getInbox() ────────────────────────────────────────────────────
    // GET /api/messages/inbox
    // 🔒 Protégé
    public function getInbox(array $patient): void {
        $patientId = (int) $patient['id_patient'];

        $result = $this->messageService->getInbox($patientId);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }
}