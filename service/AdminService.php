<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/adminModel.php';

class AdminService {

    private $m;
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->m   = new AdminModel($pdo);
    }

    // ══════════════════════════════════════════════════════
    //  AUTH
    //  POST /api/admin/login
    //  Body : { "email": "admin@dentilmeet.com", "password": "Admin@2026!" }
    // ══════════════════════════════════════════════════════

    public function login(array $data): array {
        $email    = trim($data['email']    ?? '');
        $password =      $data['password'] ?? '';

        if (empty($email) || empty($password)) {
            return ['code' => 400, 'body' => ['message' => 'Email and password are required.']];
        }

        $admin = $this->m->findByEmail($email);

        if (!$admin || !password_verify($password, $admin['password'])) {
            return ['code' => 401, 'body' => ['message' => 'Invalid email or password.']];
        }

        // Génère le token JWT avec id_admin
        $token = \TokenUtil::generate([
            'id_admin'             => $admin['id_admin'],
            'email'                => $admin['email'],
            'nom'                  => $admin['nom'],
            'prenom'               => $admin['prenom'],
            'can_validate_dentist' => (bool) $admin['can_validate_dentist'],
            'can_suspend'          => (bool) $admin['can_suspend'],
            'role'                 => 'admin',
        ]);

        return ['code' => 200, 'body' => [
            'token'                => $token,
            'id_admin'             => (int) $admin['id_admin'],
            'nom'                  => $admin['nom'],
            'prenom'               => $admin['prenom'],
            'email'                => $admin['email'],
            'can_validate_dentist' => (bool) $admin['can_validate_dentist'],
            'can_suspend'          => (bool) $admin['can_suspend'],
        ]];
    }

    // ══════════════════════════════════════════════════════
    //  DASHBOARD
    //  GET /api/admin/dashboard
    //
    //  Retourne :
    //    total_users       → stat card "Total Users"        109
    //    total_patients    → stat card "Total Patients"     81
    //    total_dentists    → stat card "Total Dentists"     28
    //    active_users      → stat card "Active Users"       99
    //    inactive_users    → stat card "Inactive Users"     10
    //    ratio             → donut chart Patients VS Dentists
    //    activity[]        → bar chart System Activity Overview
    //    notifications{}   → bloc Notifications (Urgent + Recent Activity)
    // ══════════════════════════════════════════════════════

    public function getDashboard(): array {
        return ['code' => 200, 'body' => [

            // ── STAT CARDS (droite) ──────────────────────
            'total_users'    => $this->m->getTotalUsers(),
            'total_patients' => $this->m->getTotalPatients(),
            'total_dentists' => $this->m->getTotalDentists(),
            'active_users'   => $this->m->getActiveUsers(),
            'inactive_users' => $this->m->getInactiveUsers(),

            // ── DONUT CHART ──────────────────────────────
            // { total, dentists, patients,
            //   dentists_percent, patients_percent }
            'ratio'          => $this->m->getUsersRatio(),

            // ── BAR CHART ────────────────────────────────
            // [{ label:"Appointments", count:50 }, ...]
            'activity'       => $this->m->getSystemActivity(),

            // ── NOTIFICATIONS ADMIN ───────────────────────
            // { urgent:[{message,count}],
            //   recent_activity:[{message}] }
            'notifications'  => $this->m->getAdminNotifications(),
        ]];
    }

    // ══════════════════════════════════════════════════════
    //  USER MANAGEMENT — TABS
    // ══════════════════════════════════════════════════════

    // Tab "All Users"
    // GET /api/admin/users?search=Ahmed
    public function getAllUsers(array $query): array {
        $search = trim($query['search'] ?? '');
        $users  = $this->m->getAllUsers($search);
        return ['code' => 200, 'body' => [
            'users' => $users,
            'total' => count($users),
        ]];
    }

    // Tab "Pending Requests"
    // GET /api/admin/users/pending
    public function getPendingRequests(): array {
        $requests = $this->m->getPendingRequests();
        return ['code' => 200, 'body' => [
            'requests' => $requests,
            'total'    => count($requests),
        ]];
    }

    // Tab "Dentists"
    // GET /api/admin/users/dentists?search=Ahmed
    public function getDentists(array $query): array {
        $search   = trim($query['search'] ?? '');
        $dentists = $this->m->getDentists($search);
        return ['code' => 200, 'body' => [
            'dentists' => $dentists,
            'total'    => count($dentists),
        ]];
    }

    // Tab "Patients"
    // GET /api/admin/users/patients?search=Youcef
    public function getPatients(array $query): array {
        $search   = trim($query['search'] ?? '');
        $patients = $this->m->getPatients($search);
        return ['code' => 200, 'body' => [
            'patients' => $patients,
            'total'    => count($patients),
        ]];
    }

    // ══════════════════════════════════════════════════════
    //  ACTIONS — Approve / Reject / Suspend
    // ══════════════════════════════════════════════════════

    // PUT /api/admin/dentists/{id}/approve
    public function approveDentist(int $adminId, int $dentistId): array {
        if (empty($dentistId) || $dentistId <= 0) {
            return ['code' => 400, 'body' => ['message' => 'Invalid dentist ID.']];
        }
        $ok = $this->m->approveDentist($dentistId, $adminId);
        if (!$ok) {
            return ['code' => 404, 'body' => ['message' => 'Dentist not found or already processed.']];
        }
        return ['code' => 200, 'body' => ['message' => 'Dentist approved successfully.']];
    }

    // PUT /api/admin/dentists/{id}/reject
    public function rejectDentist(int $adminId, int $dentistId): array {
        if (empty($dentistId) || $dentistId <= 0) {
            return ['code' => 400, 'body' => ['message' => 'Invalid dentist ID.']];
        }
        $ok = $this->m->rejectDentist($dentistId, $adminId);
        if (!$ok) {
            return ['code' => 404, 'body' => ['message' => 'Dentist not found or already processed.']];
        }
        return ['code' => 200, 'body' => ['message' => 'Dentist rejected.']];
    }

    // PUT /api/admin/patients/{id}/suspend
    public function suspendPatient(int $adminId, int $patientId): array {
        if ($patientId <= 0) {
            return ['code' => 400, 'body' => ['message' => 'Invalid patient ID.']];
        }
        $ok = $this->m->suspendPatient($patientId);
        if (!$ok) {
            return ['code' => 404, 'body' => ['message' => 'Patient not found.']];
        }
        return ['code' => 200, 'body' => ['message' => 'Patient suspended.']];
    }

    // PUT /api/admin/dentists/{id}/suspend
    public function suspendDentist(int $adminId, int $dentistId): array {
        if ($dentistId <= 0) {
            return ['code' => 400, 'body' => ['message' => 'Invalid dentist ID.']];
        }
        $ok = $this->m->suspendDentist($dentistId);
        if (!$ok) {
            return ['code' => 404, 'body' => ['message' => 'Dentist not found.']];
        }
        return ['code' => 200, 'body' => ['message' => 'Dentist suspended.']];
    }

    // ══════════════════════════════════════════════════════
    //  VIEW DETAILS
    // ══════════════════════════════════════════════════════

    // GET /api/admin/dentists/{id}
    public function getDentistDetails(int $dentistId): array {
        if ($dentistId <= 0) {
            return ['code' => 400, 'body' => ['message' => 'Invalid dentist ID.']];
        }
        $dentist = $this->m->getDentistDetails($dentistId);
        if (!$dentist) {
            return ['code' => 404, 'body' => ['message' => 'Dentist not found.']];
        }
        return ['code' => 200, 'body' => $dentist];
    }

    // GET /api/admin/patients/{id}
    public function getPatientDetails(int $patientId): array {
        if ($patientId <= 0) {
            return ['code' => 400, 'body' => ['message' => 'Invalid patient ID.']];
        }
        $patient = $this->m->getPatientDetails($patientId);
        if (!$patient) {
            return ['code' => 404, 'body' => ['message' => 'Patient not found.']];
        }
        return ['code' => 200, 'body' => $patient];
    }
}