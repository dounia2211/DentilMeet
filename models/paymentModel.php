<?php
declare(strict_types=1);

// ✅ La table `payment` EXISTE déjà dans votre SQL avec ces colonnes :
//    id_payment, method_payment ENUM('especes','carte','virement'),
//    payment_status ENUM('paye','en_attente','rembourse'),
//    transaction_reference, invoice_number, invoice_date, invoice_path,
//    amount, paid_at, id_appointment (NOT NULL, FK vers appointment)
//
// ⚠️  Le paiement est LIÉ à un appointment dans votre SQL
//     → le frontend doit envoyer id_appointment

class PaymentModel {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // ── create() ──────────────────────────────────────────────────────
    // Utilise les vrais noms de colonnes de votre table `payment`
    // method_payment : 'especes' | 'carte' | 'virement'
    // payment_status : 'paye' | 'en_attente' | 'rembourse'
    public function create(int $appointmentId, float $amount, string $method, string $status, string $transactionRef): int|false {
        $stmt = $this->pdo->prepare("
            INSERT INTO payment
                (id_appointment, amount, method_payment, payment_status, transaction_reference, paid_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$appointmentId, $amount, $method, $status, $transactionRef]);
        return $this->pdo->lastInsertId();
    }

    // ── getByPatient() ────────────────────────────────────────────────
    // JOIN appointment pour relier le paiement au patient
    // JOIN dentist pour avoir le nom du dentiste
    public function getByPatient(int $patientId): array {
        $stmt = $this->pdo->prepare("
            SELECT
                p.id_payment,
                p.amount,
                p.method_payment,
                p.payment_status,
                p.transaction_reference,
                p.paid_at,
                a.appointment_date,
                a.appointment_time,
                a.service_type,
                d.full_name AS dentist_name
            FROM payment p
            JOIN appointment a ON p.id_appointment = a.id_appointment
            JOIN dentist     d ON a.id_dentist      = d.id_dentist
            WHERE a.id_patient = ?
            ORDER BY p.paid_at DESC
        ");
        $stmt->execute([$patientId]);
        return $stmt->fetchAll();
    }

    // ── getById() ─────────────────────────────────────────────────────
    public function getById(int $paymentId, int $patientId): array|false {
        $stmt = $this->pdo->prepare("
            SELECT
                p.id_payment,
                p.amount,
                p.method_payment,
                p.payment_status,
                p.transaction_reference,
                p.paid_at,
                a.appointment_date,
                a.appointment_time,
                a.service_type,
                d.full_name AS dentist_name
            FROM payment p
            JOIN appointment a ON p.id_appointment = a.id_appointment
            JOIN dentist     d ON a.id_dentist      = d.id_dentist
            WHERE p.id_payment = ? AND a.id_patient = ?
        ");
        $stmt->execute([$paymentId, $patientId]);
        return $stmt->fetch();
    }

    // ── existsForAppointment() ────────────────────────────────────────
    // Vérifie si un paiement existe déjà pour cet appointment
    public function existsForAppointment(int $appointmentId): bool {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM payment
            WHERE id_appointment = ? AND payment_status = 'paye'
        ");
        $stmt->execute([$appointmentId]);
        return (bool) $stmt->fetchColumn();
    }
}