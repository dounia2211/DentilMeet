<?php
declare(strict_types=1);

// ============================================================
//  TABLES UTILISÉES :
//    favorite     → id_patient, id_dentist
//    appointment  → id_patient, appointment_date,
//                   appointment_status, total_price, id_appointment
//    chat_message → receiver_id, is_read, sender_type
//    payment      → id_appointment, payment_status
//    dentist      → id_dentist, full_name, speciality, photo
//    review       → id_dentist, rating, is_reported
// ============================================================

class DashboardModel {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // ── getFavoritesCount() ──────────────────────────────────
    // Tables  : favorite
    // Colonnes: id_patient
    // FRONT reçoit : favorites_count → carte "5 Favorite Doctors"
    public function getFavoritesCount(int $patientId): int {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM favorite
            WHERE id_patient = ?
        ");
        $stmt->execute([$patientId]);
        return (int) $stmt->fetchColumn();
    }

    // ── getUpcomingCount() ───────────────────────────────────
    // Tables  : appointment
    // Colonnes: id_patient, appointment_date, appointment_status
    // FRONT reçoit : upcoming_appointments_count → carte "2 Upcoming Appointments"
    public function getUpcomingCount(int $patientId): int {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM appointment
            WHERE id_patient         = ?
              AND appointment_date   >= CURDATE()
              AND appointment_status  = 'confirme'
        ");
        $stmt->execute([$patientId]);
        return (int) $stmt->fetchColumn();
    }

    // ── getUnreadMessagesCount() ─────────────────────────────
    // Tables  : chat_message
    // Colonnes: receiver_id, sender_type, is_read
    // FRONT reçoit : new_messages_count → carte "1 New messages"
    public function getUnreadMessagesCount(int $patientId): int {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM chat_message
            WHERE receiver_id = ?
              AND sender_type = 'dentiste'
              AND is_read     = 0
        ");
        $stmt->execute([$patientId]);
        return (int) $stmt->fetchColumn();
    }

    // ── getRemainingPayment() ────────────────────────────────
    // Tables  : appointment, payment
    // Colonnes: id_patient, appointment_status, total_price,
    //           id_appointment, payment_status
    // FRONT reçoit : remaining_payment → carte "3500 DA / Pay Now"
    public function getRemainingPayment(int $patientId): float {
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(a.total_price), 0)
            FROM appointment a
            WHERE a.id_patient         = ?
              AND a.appointment_status  = 'confirme'
              AND a.id_appointment NOT IN (
                  SELECT id_appointment
                  FROM payment
                  WHERE payment_status = 'paye'
              )
        ");
        $stmt->execute([$patientId]);
        return (float) $stmt->fetchColumn();
    }

    // ── getSuggestions() ─────────────────────────────────────
    // Tables  : dentist, review
    // Colonnes dentist : id_dentist, full_name, speciality, photo
    // Colonnes review  : id_dentist, rating, is_reported
    // FRONT reçoit : suggestions[] → 4 cartes dentistes dans le dashboard
    //   id_dentist | full_name | speciality | photo | avg_rating
    public function getSuggestions(int $limit = 4): array {
        $stmt = $this->pdo->prepare("
            SELECT
                d.id_dentist,
                d.full_name,
                d.speciality,
                d.photo,
                ROUND(AVG(r.rating), 1) AS avg_rating
            FROM dentist d
            LEFT JOIN review r ON d.id_dentist  = r.id_dentist
                               AND r.is_reported = 0
            GROUP BY d.id_dentist
            ORDER BY avg_rating DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}