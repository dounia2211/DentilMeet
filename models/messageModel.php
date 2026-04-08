<?php
declare(strict_types=1);

// ✅ La table `chat_message` EXISTE déjà dans votre SQL avec ces colonnes :
//    id_message, message_text, sent_at, is_read,
//    sender_type ENUM('patient','dentiste'),
//    sender_id, receiver_id, id_appointment (nullable)

class MessageModel {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // ── send() ────────────────────────────────────────────────────────
    // Utilise les vrais noms : message_text, sent_at, sender_type ENUM('patient','dentiste')
    public function send(int $senderId, string $senderType, int $receiverId, string $content, ?int $appointmentId): int|false {
        $stmt = $this->pdo->prepare("
            INSERT INTO chat_message
                (sender_id, sender_type, receiver_id, message_text, sent_at, is_read, id_appointment)
            VALUES (?, ?, ?, ?, NOW(), 0, ?)
        ");
        $success = $stmt->execute([$senderId, $senderType, $receiverId, $content, $appointmentId]);
        if ($success) {
        return (int) $this->pdo->lastInsertId(); 
    }

    // ── getConversation() ─────────────────────────────────────────────
    // Tous les messages entre un patient et un dentiste (les deux sens)
    public function getConversation(int $patientId, int $dentistId): array {
        $stmt = $this->pdo->prepare("
            SELECT
                id_message,
                sender_id,
                sender_type,
                receiver_id,
                message_text,
                is_read,
                sent_at,
                id_appointment
            FROM chat_message
            WHERE
                (sender_id = ? AND sender_type = 'patient'  AND receiver_id = ?)
                OR
                (sender_id = ? AND sender_type = 'dentiste' AND receiver_id = ?)
            ORDER BY sent_at ASC
        ");
        $stmt->execute([$patientId, $dentistId, $dentistId, $patientId]);
        return $stmt->fetchAll();
    }

    // ── markAsRead() ──────────────────────────────────────────────────
    // Marque comme lus les messages du dentiste vers le patient
    public function markAsRead(int $patientId, int $dentistId): void {
        $stmt = $this->pdo->prepare("
            UPDATE chat_message
            SET is_read = 1
            WHERE receiver_id   = ?
              AND sender_id     = ?
              AND sender_type   = 'dentiste'
              AND is_read       = 0
        ");
        $stmt->execute([$patientId, $dentistId]);
    }

    // ── getInbox() ────────────────────────────────────────────────────
    // Dernier message par conversation pour la boite de réception du patient
    public function getInbox(int $patientId): array {
        $stmt = $this->pdo->prepare("
            SELECT cm.*
            FROM chat_message cm
            INNER JOIN (
                SELECT MAX(id_message) AS last_id
                FROM chat_message
                WHERE sender_id = ? OR receiver_id = ?
                GROUP BY
                    LEAST(sender_id, receiver_id),
                    GREATEST(sender_id, receiver_id)
            ) latest ON cm.id_message = latest.last_id
            ORDER BY cm.sent_at DESC
        ");
        $stmt->execute([$patientId, $patientId]);
        return $stmt->fetchAll();
    }
}
