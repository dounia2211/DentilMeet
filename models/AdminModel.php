<?php
declare(strict_types=1);

class AdminModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // ══════════════════════════════════════════════════════
    //  AUTH
    // ══════════════════════════════════════════════════════

    // Utilisé par adminService::login()
    public function findByEmail(string $email): array|false {
        $stmt = $this->pdo->prepare("
            SELECT id_admin, nom, prenom, email, password,
                   can_validate_dentist, can_suspend
            FROM admin
            WHERE email = ?
            LIMIT 1
        ");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    // ══════════════════════════════════════════════════════
    //  DASHBOARD — STAT CARDS
    // ══════════════════════════════════════════════════════

    public function getTotalUsers(): int {
        $p = (int) $this->pdo->query("SELECT COUNT(*) FROM patient")->fetchColumn();
        $d = (int) $this->pdo->query("SELECT COUNT(*) FROM dentist")->fetchColumn();
        return $p + $d;
    }

    public function getTotalPatients(): int {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM patient")->fetchColumn();
    }

    public function getTotalDentists(): int {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM dentist")->fetchColumn();
    }

    // Active = non suspendus
    public function getActiveUsers(): int {
        $p = (int) $this->pdo->query("SELECT COUNT(*) FROM patient WHERE is_suspended = 0")->fetchColumn();
        $d = (int) $this->pdo->query("SELECT COUNT(*) FROM dentist WHERE is_suspended = 0")->fetchColumn();
        return $p + $d;
    }

    // Inactive = suspendus
    public function getInactiveUsers(): int {
        $p = (int) $this->pdo->query("SELECT COUNT(*) FROM patient WHERE is_suspended = 1")->fetchColumn();
        $d = (int) $this->pdo->query("SELECT COUNT(*) FROM dentist WHERE is_suspended = 1")->fetchColumn();
        return $p + $d;
    }

    // ── DASHBOARD — Donut Chart : Patients VS Dentists ────
    // Retourne : { total, dentists, patients, dentists_percent, patients_percent }
    public function getUsersRatio(): array {
        $total    = $this->getTotalUsers();
        $dentists = $this->getTotalDentists();
        $patients = $this->getTotalPatients();
        return [
            'total'            => $total,
            'dentists'         => $dentists,
            'patients'         => $patients,
            'dentists_percent' => $total > 0 ? round($dentists / $total * 100) : 0,
            'patients_percent' => $total > 0 ? round($patients / $total * 100) : 0,
        ];
    }

    // ── DASHBOARD — Bar Chart : System Activity Overview ──
    // Retourne : [{ label, count }]
    public function getSystemActivity(): array {
        $appointments = (int) $this->pdo->query(
            "SELECT COUNT(*) FROM appointment"
        )->fetchColumn();

        $payments = (int) $this->pdo->query(
            "SELECT COUNT(*) FROM payment WHERE payment_status = 'paye'"
        )->fetchColumn();

        $newAccounts = (int) $this->pdo->query(
            "SELECT COUNT(*) FROM dentist
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        )->fetchColumn();

        $updateRequests = (int) $this->pdo->query(
            "SELECT COUNT(*) FROM dentist WHERE verification_status = 'en_attente'"
        )->fetchColumn();

        return [
            ['label' => 'Appointments',    'count' => $appointments],
            ['label' => 'Payments',        'count' => $payments],
            ['label' => 'New Account',     'count' => $newAccounts],
            ['label' => 'Update Requests', 'count' => $updateRequests],
        ];
    }

    // ── DASHBOARD — Notifications admin ───────────────────
    // Retourne : { urgent:[{message,count}], recent_activity:[{message}] }
    public function getAdminNotifications(): array {
        $pendingDentists = (int) $this->pdo->query(
            "SELECT COUNT(*) FROM dentist WHERE verification_status = 'en_attente'"
        )->fetchColumn();

        $recentPatient = $this->pdo->query(
            "SELECT full_name FROM patient ORDER BY created_at DESC LIMIT 1"
        )->fetchColumn();

        $todayAppointments = (int) $this->pdo->query(
            "SELECT COUNT(*) FROM appointment WHERE appointment_date = CURDATE()"
        )->fetchColumn();

        return [
            'urgent' => [
                ['message' => 'New dentist request',    'count' => $pendingDentists],
                ['message' => 'Profile update request', 'count' => 0],
            ],
            'recent_activity' => [
                ['message' => 'New patient created: ' . ($recentPatient ?: 'N/A')],
                ['message' => 'Appointment booked today: ' . $todayAppointments],
            ],
        ];
    }

    // ══════════════════════════════════════════════════════
    //  USER MANAGEMENT — TABS
    // ══════════════════════════════════════════════════════

    // Tab "All Users" — patients + dentistes unifiés
    // search : cherche dans full_name, email, phone
    public function getAllUsers(string $search = ''): array {
        $like = '%' . $search . '%';

        $patients = $this->pdo->prepare("
            SELECT
                id_patient                              AS id,
                full_name                               AS name,
                'Patient'                               AS role,
                phone,
                email,
                is_suspended,
                created_at,
                CASE
                    WHEN is_suspended = 1 THEN 'suspended'
                    ELSE 'en ligne'
                END                                     AS status
            FROM patient
            WHERE full_name LIKE ? OR email LIKE ? OR phone LIKE ?
            ORDER BY created_at DESC
        ");
        $patients->execute([$like, $like, $like]);

        $dentists = $this->pdo->prepare("
            SELECT
                id_dentist                              AS id,
                full_name                               AS name,
                'Doctor'                                AS role,
                phone,
                email,
                is_suspended,
                created_at,
                CASE
                    WHEN is_suspended = 1 THEN 'suspended'
                    ELSE 'en ligne'
                END                                     AS status
            FROM dentist
            WHERE full_name LIKE ? OR email LIKE ? OR phone LIKE ?
            ORDER BY created_at DESC
        ");
        $dentists->execute([$like, $like, $like]);

        return array_merge($patients->fetchAll(), $dentists->fetchAll());
    }

    // Tab "Pending Requests" — dentistes en_attente avec time_ago
    public function getPendingRequests(): array {
        $stmt = $this->pdo->prepare("
            SELECT
                id_dentist,
                full_name                               AS name,
                'Dentist'                               AS role,
                'New Account'                           AS request_type,
                COALESCE(submitted_at, created_at)      AS submitted_at,
                verification_status
            FROM dentist
            WHERE verification_status = 'en_attente'
            ORDER BY COALESCE(submitted_at, created_at) DESC
        ");
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $now = time();
        return array_map(function ($r) use ($now) {
            $diff = $now - strtotime($r['submitted_at']);
            if ($diff < 60)        $timeAgo = 'just now';
            elseif ($diff < 3600)  $timeAgo = floor($diff / 60) . ' min';
            elseif ($diff < 86400) $timeAgo = floor($diff / 3600) . ' h';
            else                   $timeAgo = floor($diff / 86400) . ' days';
            $r['time_ago'] = $timeAgo;
            return $r;
        }, $rows);
    }

    // Tab "Dentists" — tous les dentistes avec clinique
    public function getDentists(string $search = ''): array {
        $like = '%' . $search . '%';
        $stmt = $this->pdo->prepare("
            SELECT
                d.id_dentist,
                d.full_name                             AS name,
                d.speciality                            AS specialty,
                d.phone,
                d.email,
                d.is_suspended,
                d.verification_status                   AS status,
                c.name                                  AS clinic_name,
                c.city                                  AS location
            FROM dentist d
            LEFT JOIN clinic c ON d.id_clinic = c.id_clinic
            WHERE d.full_name LIKE ? OR d.email LIKE ? OR d.phone LIKE ?
            ORDER BY d.created_at DESC
        ");
        $stmt->execute([$like, $like, $like]);
        return $stmt->fetchAll();
    }

    // Tab "Patients"
    public function getPatients(string $search = ''): array {
        $like = '%' . $search . '%';
        $stmt = $this->pdo->prepare("
            SELECT
                id_patient,
                full_name                               AS name,
                'Patient'                               AS role,
                phone,
                email,
                is_suspended,
                CASE
                    WHEN is_suspended = 1               THEN 'suspended'
                    WHEN created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR) THEN '1 YEAR'
                    ELSE 'en ligne'
                END                                     AS status
            FROM patient
            WHERE full_name LIKE ? OR email LIKE ? OR phone LIKE ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$like, $like, $like]);
        return $stmt->fetchAll();
    }

    // ══════════════════════════════════════════════════════
    //  ACTIONS — Approve / Reject / Suspend
    // ══════════════════════════════════════════════════════

    // Approve dentiste — bouton "Approve" dans Pending Requests
    public function approveDentist(int $dentistId, int $adminId): bool {
        $stmt = $this->pdo->prepare("
            UPDATE dentist
            SET verification_status = 'approuve',
                validated_by        = ?
            WHERE id_dentist        = ?
              AND verification_status = 'en_attente'
        ");
        $stmt->execute([$adminId, $dentistId]);
        return $stmt->rowCount() > 0;
    }

    // Reject dentiste — bouton "Reject" dans Pending Requests
    public function rejectDentist(int $dentistId, int $adminId): bool {
        $stmt = $this->pdo->prepare("
            UPDATE dentist
            SET verification_status = 'refuse',
                validated_by        = ?
            WHERE id_dentist        = ?
              AND verification_status = 'en_attente'
        ");
        $stmt->execute([$adminId, $dentistId]);
        return $stmt->rowCount() > 0;
    }

    // Suspend patient — bouton dans Actions
    public function suspendPatient(int $patientId): bool {
        $stmt = $this->pdo->prepare("
            UPDATE patient SET is_suspended = 1 WHERE id_patient = ?
        ");
        $stmt->execute([$patientId]);
        return $stmt->rowCount() > 0;
    }

    // Suspend dentiste
    public function suspendDentist(int $dentistId): bool {
        $stmt = $this->pdo->prepare("
            UPDATE dentist SET is_suspended = 1 WHERE id_dentist = ?
        ");
        $stmt->execute([$dentistId]);
        return $stmt->rowCount() > 0;
    }

    // ══════════════════════════════════════════════════════
    //  VIEW DETAILS
    // ══════════════════════════════════════════════════════

    public function getDentistDetails(int $dentistId): array|false {
        $stmt = $this->pdo->prepare("
            SELECT
                d.id_dentist,
                d.full_name,
                d.email,
                d.phone,
                d.speciality,
                d.year_of_experience,
                d.description,
                d.verification_status,
                d.is_suspended,
                d.created_at,
                d.diploma_document,
                d.license_number,
                d.clinic_status,
                c.name                                  AS clinic_name,
                c.city                                  AS location,
                c.address                               AS clinic_address
            FROM dentist d
            LEFT JOIN clinic c ON d.id_clinic = c.id_clinic
            WHERE d.id_dentist = ?
            LIMIT 1
        ");
        $stmt->execute([$dentistId]);
        return $stmt->fetch();
    }

    public function getPatientDetails(int $patientId): array|false {
        $stmt = $this->pdo->prepare("
            SELECT
                id_patient,
                full_name,
                email,
                phone,
                gender,
                birth_date,
                address,
                is_suspended,
                created_at,
                photo,
                TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) AS age
            FROM patient
            WHERE id_patient = ?
            LIMIT 1
        ");
        $stmt->execute([$patientId]);
        return $stmt->fetch();
    }
}