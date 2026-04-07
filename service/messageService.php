<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/messageModel.php';

class MessageService {

    private $messageModel;

    public function __construct($pdo) {
        $this->messageModel = new MessageModel($pdo);
    }

    // ── send() ────────────────────────────────────────────────────────
    // POST /api/messages/send
    // Body: { "id_dentist": 3, "message_text": "Bonjour docteur!", "id_appointment": 7 }
    // id_appointment est optionnel (nullable dans votre SQL)
    public function send(int $patientId, array $data): array {
        $dentistId     = (int)  ($data['id_dentist']     ?? 0);
        $messageText   = trim($data['message_text']      ?? '');
        $appointmentId = isset($data['id_appointment']) ? (int)$data['id_appointment'] : null;

        if ($dentistId <= 0) {
            return ['code' => 400, 'body' => ['message' => 'id_dentist is required.']];
        }

        if (empty($messageText)) {
            return ['code' => 400, 'body' => ['message' => 'message_text cannot be empty.']];
        }

        if (strlen($messageText) > 2000) {
            return ['code' => 400, 'body' => ['message' => 'message_text must not exceed 2000 characters.']];
        }

        $id = $this->messageModel->send(
            $patientId,
            'patient',     // sender_type — votre ENUM('patient','dentiste')
            $dentistId,
            $messageText,
            $appointmentId
        );

        return [
            'code' => 201,
            'body' => [
                'message'    => 'Message sent successfully.',
                'id_message' => (int) $id
            ]
        ];
    }

    // ── getConversation() ─────────────────────────────────────────────
    // GET /api/messages/conversation?id_dentist=3
    public function getConversation(int $patientId, array $query): array {
        $dentistId = (int)($query['id_dentist'] ?? 0);

        if ($dentistId <= 0) {
            return ['code' => 400, 'body' => ['message' => 'id_dentist is required.']];
        }

        // Marque les messages du dentiste comme lus quand le patient ouvre la conv
        $this->messageModel->markAsRead($patientId, $dentistId);

        $messages = $this->messageModel->getConversation($patientId, $dentistId);

        return [
            'code' => 200,
            'body' => [
                'id_dentist' => $dentistId,
                'messages'   => $messages,
                'count'      => count($messages)
            ]
        ];
    }

    // ── getInbox() ────────────────────────────────────────────────────
    // GET /api/messages/inbox
    public function getInbox(int $patientId): array {
        $inbox = $this->messageModel->getInbox($patientId);

        return [
            'code' => 200,
            'body' => [
                'inbox' => $inbox,
                'count' => count($inbox)
            ]
        ];
    }
}