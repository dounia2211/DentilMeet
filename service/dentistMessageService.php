<?php
declare(strict_types=1);
require_once __DIR__ . '/../models/dentistMessageModel.php';

class DentistMessageService {
    private $m;
    public function __construct($pdo) {
        $this->m = new DentistMessageModel($pdo);
    }

    // GET /api/dentist/messages — liste conversations panneau gauche
    public function getConversations(int $dentistId): array {
        return ['code' => 200, 'body' => $this->m->getConversations($dentistId)];
    }

    // GET /api/dentist/messages/{id_patient} — messages d'une conv
    public function getMessages(int $dentistId, int $patientId): array {
        $this->m->markAsRead($dentistId, $patientId);
        $details  = $this->m->getPatientDetails($patientId, $dentistId);
        $messages = $this->m->getMessages($dentistId, $patientId);
        return ['code' => 200, 'body' => [
            'patient'  => $details,   // panneau droit "Patient Details"
            'messages' => $messages,  // bulle de chat
        ]];
    }

    // POST /api/dentist/messages/send — envoyer un message
    public function send(int $dentistId, array $data): array {
        $patientId = (int)($data['id_patient'] ?? 0);
        $text      = trim($data['message_text'] ?? '');
        if ($patientId <= 0) return ['code' => 400, 'body' => ['message' => 'id_patient required.']];
        if (empty($text))   return ['code' => 400, 'body' => ['message' => 'message_text required.']];
        if (strlen($text) > 2000) return ['code' => 400, 'body' => ['message' => 'message_text too long.']];
        $id = $this->m->send($dentistId, $patientId, $text);
        // Notif patient : "You have a new message from Dr. Ahmed"
    $dentistName = $this->m->getDentistName($dentistId); 
    require_once __DIR__ . '/../service/notificationService.php';
    $notifPatient = new notificationService($this->pdo);
    $notifPatient->createDentistMessageNotification(
        $patientId,
        $dentistName,
        $dentistId
    );
        return ['code' => 201, 'body' => ['message' => 'Sent.', 'id_message' => $id]];
    }
}
