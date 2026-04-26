<?php
declare(strict_types=1);

class DentistMessageModel {
    private $pdo;
    public function __construct($pdo) { $this->pdo = $pdo; }

    // Liste des conversations — panneau gauche
    // Retourne : id_patient, name, preview, time, type, status, phone, email, duePayment
    public function getConversations(int $dentistId): array {
        $stmt = $this->pdo->prepare("
            SELECT
                p.id_patient,
                p.full_name                                    AS name,
                p.phone,
                p.email,
                a.service_type                                 AS type,
                a.appointment_status                           AS status,
                a.total_price                                  AS duePayment,
                (SELECT message_text FROM chat_message
                 WHERE (sender_id = p.id_patient AND receiver_id = :d1)
                    OR (sender_id = :d2 AND receiver_id = p.id_patient)
                 ORDER BY sent_at DESC LIMIT 1)                AS preview,
                (SELECT DATE_FORMAT(sent_at, '%l:%i %p') FROM chat_message
                 WHERE (sender_id = p.id_patient AND receiver_id = :d3)
                    OR (sender_id = :d4 AND receiver_id = p.id_patient)
                 ORDER BY sent_at DESC LIMIT 1)                AS time
            FROM appointment a
            JOIN patient p ON a.id_patient = p.id_patient
            WHERE a.id_dentist = :d5
              AND a.appointment_status IN ('confirme', 'termine')
            GROUP BY p.id_patient
            ORDER BY MAX(a.appointment_date) DESC
        ");
        $stmt->execute([
            ':d1' => $dentistId, ':d2' => $dentistId,
            ':d3' => $dentistId, ':d4' => $dentistId,
            ':d5' => $dentistId,
        ]);
        return $stmt->fetchAll();
    }

    // Messages d'une conversation spécifique
    // Retourne : id_message, from ("doctor"/"user"), text, time, type
    public function getMessages(int $dentistId, int $patientId): array {
        $stmt = $this->pdo->prepare("
            SELECT
                id_message,
                message_text                                    AS text,
                DATE_FORMAT(sent_at, '%H:%i')                   AS time,
                sender_type,
                sender_id
            FROM chat_message
            WHERE (sender_id = ? AND receiver_id = ? AND sender_type = 'patient')
               OR (sender_id = ? AND receiver_id = ? AND sender_type = 'dentiste')
            ORDER BY sent_at ASC
        ");
        $stmt->execute([$patientId, $dentistId, $dentistId, $patientId]);
        $rows = $stmt->fetchAll();

        // Traduit sender_type en "doctor"/"user" pour le front
        return array_map(function($r) use ($dentistId) {
            return [
                'id_message' => (int) $r['id_message'],
                'from'       => $r['sender_type'] === 'dentiste' ? 'doctor' : 'user',
                'text'       => $r['text'],
                'time'       => $r['time'],
                'type'       => 'text', // 'image' si tu gères les images plus tard
            ];
        }, $rows);
    }

    // Envoyer un message — dentiste vers patient
    public function send(int $dentistId, int $patientId, string $text): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO chat_message
                (message_text, sender_type, sender_id, receiver_id, is_read, sent_at)
            VALUES (?, 'dentiste', ?, ?, 0, NOW())
        ");
        $stmt->execute([$text, $dentistId, $patientId]);
        return (int) $this->pdo->lastInsertId();
    }

    // Marquer les messages du patient comme lus quand le dentiste ouvre la conv
    public function markAsRead(int $dentistId, int $patientId): void {
        $stmt = $this->pdo->prepare("
            UPDATE chat_message SET is_read = 1
            WHERE sender_id = ? AND receiver_id = ? AND sender_type = 'patient'
        ");
        $stmt->execute([$patientId, $dentistId]);
    }

    // Patient details pour le panneau droit du chat
    // Retourne : name, age, gender, visit_type, reason, time, status, allergies, medicalNote, duePayment
    public function getPatientDetails(int $patientId, int $dentistId): array|false {
        $stmt = $this->pdo->prepare("
            SELECT
                p.full_name                                         AS name,
                TIMESTAMPDIFF(YEAR, p.birth_date, CURDATE())        AS age,
                p.phone,
                p.email,
                a.service_type                                       AS type,
                a.reason,
                DATE_FORMAT(a.appointment_time, '%l:%i %p')         AS time,
                a.appointment_status                                 AS status,
                a.total_price                                        AS duePayment
            FROM appointment a
            JOIN patient p ON a.id_patient = p.id_patient
            WHERE a.id_patient = ? AND a.id_dentist = ?
              AND a.appointment_status IN ('confirme','termine')
            ORDER BY a.appointment_date DESC
            LIMIT 1
        ");
        $stmt->execute([$patientId, $dentistId]);
        return $stmt->fetch();
    }
}