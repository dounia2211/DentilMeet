<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/paymentModel.php';

class PaymentService {

    private $paymentModel;

    // Méthodes acceptées — correspondent à votre ENUM('especes','carte','virement')
    private const METHODS = ['especes', 'carte', 'virement'];

    public function __construct($pdo) {
        $this->paymentModel = new PaymentModel($pdo);
    }

    // ── pay() ─────────────────────────────────────────────────────────
    // POST /api/payments/pay
    // Body: { "id_appointment": 7, "amount": 2500.00, "method_payment": "carte" }
    //
    // Simulation fake payment :
    //   1. Validation des données
    //   2. Vérification qu'un paiement n'existe pas déjà
    //   3. Génération d'une fausse référence de transaction
    //   4. Simulation 90% succès / 10% échec
    //   5. Sauvegarde en DB avec les vrais noms de colonnes
    public function pay(int $patientId, array $data): array {
        $appointmentId = (int)  ($data['id_appointment']  ?? 0);
        $amount        = (float)($data['amount']           ?? 0);
        $method        = trim(strtolower($data['method_payment'] ?? ''));

        // ── Validation ────────────────────────────────────────────────
        if ($appointmentId <= 0) {
            return ['code' => 400, 'body' => ['message' => 'id_appointment is required.']];
        }

        if ($amount <= 0) {
            return ['code' => 400, 'body' => ['message' => 'amount must be greater than 0.']];
        }

        // Vérifier que method_payment correspond à votre ENUM SQL
        if (!in_array($method, self::METHODS)) {
            return [
                'code' => 400,
                'body' => ['message' => 'method_payment must be: especes, carte, or virement.']
            ];
        }

        // ── Anti-doublon ──────────────────────────────────────────────
        if ($this->paymentModel->existsForAppointment($appointmentId)) {
            return ['code' => 409, 'body' => ['message' => 'This appointment is already paid.']];
        }

        // ── Simulation gateway de paiement ────────────────────────────
        // Génère une référence unique — comme le ferait Stripe ou PayPal
        $transactionRef = 'TXN-' . strtoupper(bin2hex(random_bytes(8)));

        // 90% succès, 10% échec (simulation réaliste)
        $success = (rand(1, 10) <= 9);
        // payment_status correspond à votre ENUM('paye','en_attente','rembourse')
        $status  = $success ? 'paye' : 'en_attente';

        // ── Sauvegarde ────────────────────────────────────────────────
        $id = $this->paymentModel->create(
            $appointmentId,
            $amount,
            $method,
            $status,
            $transactionRef
        );

        // ── Réponse ───────────────────────────────────────────────────
        if ($success) {
            return [
                'code' => 201,
                'body' => [
                    'message'               => 'Payment successful.',
                    'id_payment'            => (int) $id,
                    'transaction_reference' => $transactionRef,
                    'payment_status'        => 'paye',
                    'amount'                => $amount,
                    'method_payment'        => $method
                ]
            ];
        } else {
            return [
                'code' => 402,
                'body' => [
                    'message'               => 'Payment failed. Please try again.',
                    'transaction_reference' => $transactionRef,
                    'payment_status'        => 'en_attente'
                ]
            ];
        }
    }

    // ── getHistory() ──────────────────────────────────────────────────
    // GET /api/payments/history
    public function getHistory(int $patientId): array {
        $payments = $this->paymentModel->getByPatient($patientId);

        return [
            'code' => 200,
            'body' => [
                'payments' => $payments,
                'count'    => count($payments)
            ]
        ];
    }

    // ── getReceipt() ──────────────────────────────────────────────────
    // GET /api/payments/receipt?id_payment=5
    public function getReceipt(int $patientId, array $query): array {
        $paymentId = (int)($query['id_payment'] ?? 0);

        if ($paymentId <= 0) {
            return ['code' => 400, 'body' => ['message' => 'id_payment is required.']];
        }

        $payment = $this->paymentModel->getById($paymentId, $patientId);

        if (!$payment) {
            return ['code' => 404, 'body' => ['message' => 'Payment not found.']];
        }

        return ['code' => 200, 'body' => ['payment' => $payment]];
    }
}