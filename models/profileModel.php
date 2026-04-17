<?php
declare(strict_types=1);

class ProfileModel {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // ─────────────────────────────────────────────────────────────────
    // Carte gauche du design : photo + FULL NAME + Number + Email + Patient ID
    // ─────────────────────────────────────────────────────────────────
    public function getById(int $patientId): array|false {
        $stmt = $this->pdo->prepare("
            SELECT
                p.id_patient,
                p.full_name,
                p.email,
                p.phone,
                p.photo
            FROM patient p
            WHERE p.id_patient = ?
            LIMIT 1
        ");
        $stmt->execute([$patientId]);
        return $stmt->fetch();
    }

    // ─────────────────────────────────────────────────────────────────
    // Bloc "Anamnesis" — rempli par le DENTISTE, pas le patient
    // Colonnes : medical_conditions, medications, allergies, surgical_history
    // ─────────────────────────────────────────────────────────────────
    public function getAnamnesis(int $patientId): array {
        $stmt = $this->pdo->prepare("
            SELECT
                medical_conditions,
                medications,
                allergies,
                surgical_history
            FROM anamnesis
            WHERE id_patient = ?
            LIMIT 1
        ");
        $stmt->execute([$patientId]);
        $result = $stmt->fetch();

        // Si le dentiste n'a pas encore rempli l'anamnèse → valeurs par défaut
        if (!$result) {
            return [
                'medical_conditions' => 'None',
                'medications'        => 'None',
                'allergies'          => 'No known allergies',
                'surgical_history'   => 'None',
            ];
        }
        return $result;
    }

    // ─────────────────────────────────────────────────────────────────
    // Bloc "Files/Documents" — uploadés par le DENTISTE pour ce patient
    // Colonnes : id_document, file_name, file_path, file_size_kb, uploaded_at
    // ─────────────────────────────────────────────────────────────────
    public function getDocuments(int $patientId): array {
        $stmt = $this->pdo->prepare("
            SELECT
                id_document,
                file_name,
                file_path,
                file_size_kb,
                uploaded_at
            FROM patient_document
            WHERE id_patient = ?
            ORDER BY uploaded_at DESC
        ");
        $stmt->execute([$patientId]);
        return $stmt->fetchAll();
    }

    // ─────────────────────────────────────────────────────────────────
    // Bloc "Visit History" — tableau : Date / Treatment / Doctor / Payment
    // Jointure appointment + dentist + payment
    // ─────────────────────────────────────────────────────────────────
    public function getVisitHistory(int $patientId): array {
        $stmt = $this->pdo->prepare("
            SELECT
                a.id_appointment,
                a.appointment_date,
                a.service_type        AS treatment,
                d.full_name           AS doctor_name,
                p.amount              AS payment_amount,
                p.payment_status,
                p.method_payment
            FROM appointment a
            JOIN dentist  d ON a.id_dentist      = d.id_dentist
            LEFT JOIN payment p ON p.id_appointment = a.id_appointment
            WHERE a.id_patient = ?
              AND a.appointment_status != 'annule'
            ORDER BY a.appointment_date DESC
        ");
        $stmt->execute([$patientId]);
        return $stmt->fetchAll();
    }

    // ─────────────────────────────────────────────────────────────────
    // Bloc "Remaining Payment" — somme des paiements en_attente
    // Affiché en bas à droite du design avec bouton "Pay Now"
    // ─────────────────────────────────────────────────────────────────
    public function getRemainingPayment(int $patientId): float {
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(p.amount), 0) AS remaining
            FROM payment p
            JOIN appointment a ON p.id_appointment = a.id_appointment
            WHERE a.id_patient     = ?
              AND p.payment_status = 'en_attente'
        ");
        $stmt->execute([$patientId]);
        return (float) $stmt->fetchColumn();
    }

    // ─────────────────────────────────────────────────────────────────
    // Update photo de profil du patient
    // Appelé après upload réussi dans profileService
    // ─────────────────────────────────────────────────────────────────
    public function updatePhoto(int $patientId, string $photoPath): bool {
        $stmt = $this->pdo->prepare("
            UPDATE patient SET photo = ? WHERE id_patient = ?
        ");
        return $stmt->execute([$photoPath, $patientId]);
    }
}