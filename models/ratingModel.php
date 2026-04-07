<?php
declare(strict_types=1);

// ✅ La table `review` EXISTE déjà dans votre SQL avec ces colonnes :
//    id_review, rating, comment, review_date, is_reported,
//    id_patient, id_dentist, id_appointment
//
// ⚠️  CONTRAINTE IMPORTANTE : id_appointment est NOT NULL dans votre SQL
//     → un review est lié à un appointment terminé
//     → le frontend doit envoyer id_appointment

class RatingModel {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // ── create() ──────────────────────────────────────────────────────
    // Utilise les vrais noms de colonnes de votre table `review`
    // rating (pas stars), review_date (pas created_at), id_appointment obligatoire
    public function create(int $patientId, int $dentistId, int $appointmentId, int $rating, ?string $comment): int|false {
        $stmt = $this->pdo->prepare("
            INSERT INTO review (id_patient, id_dentist, id_appointment, rating, comment, review_date)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$patientId, $dentistId, $appointmentId, $rating, $comment]);
        return $this->pdo->lastInsertId();
    }

    // ── hasRated() ────────────────────────────────────────────────────
    // Votre SQL a UNIQUE KEY `unique_review` (id_patient, id_appointment)
    // → 1 seul review par patient par appointment
    public function hasRated(int $patientId, int $appointmentId): bool {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM review
            WHERE id_patient = ? AND id_appointment = ?
        ");
        $stmt->execute([$patientId, $appointmentId]);
        return (bool) $stmt->fetchColumn();
    }

    // ── getByDentist() ────────────────────────────────────────────────
    // Retourne tous les reviews d'un dentiste
    // JOIN patient pour avoir le nom du patient qui a noté
    public function getByDentist(int $dentistId): array {
        $stmt = $this->pdo->prepare("
            SELECT
                r.id_review,
                r.rating,
                r.comment,
                r.review_date,
                p.full_name AS patient_name
            FROM review r
            JOIN patient p ON r.id_patient = p.id_patient
            WHERE r.id_dentist = ?
            AND   r.is_reported = 0
            ORDER BY r.review_date DESC
        ");
        $stmt->execute([$dentistId]);
        return $stmt->fetchAll();
    }

    // ── getAverage() ──────────────────────────────────────────────────
    public function getAverage(int $dentistId): array {
        $stmt = $this->pdo->prepare("
            SELECT
                ROUND(AVG(rating), 1) AS average,
                COUNT(*)              AS total
            FROM review
            WHERE id_dentist = ? AND is_reported = 0
        ");
        $stmt->execute([$dentistId]);
        return $stmt->fetch();
    }
}