<?php
declare(strict_types=1);

require_once __DIR__ . '/../service/dentistDashboardService.php';

class DentistDashboardController {

    private $service;

    public function __construct($pdo) {
        $this->service = new DentistDashboardService($pdo);
    }

    // ══════════════════════════════════════════════════════
    //  GET /api/dentist/dashboard
    //  🔒 Token dentiste — AuthMiddleware::handleDentist()
    //
    //  1 seul appel API pour tout le dashboard Dentaldash.tsx
    //
    //  Retourne :
    //    clinic_status        → <StatCard value={clinic_status} isStatus />
    //    patients_seen_today  → <StatCard value={patients_seen_today} />  "021"
    //    todays_patients      → <StatCard value={todays_patients} />      "40"
    //    total_patients       → <StatCard value={total_patients} />       "+200"
    //    appointments[]       → <UpcomingAppointments>
    //      .name              → {a.name}
    //      .time              → {a.time}  "on going" | "12:30 pm"
    //      .live              → bool → couleur/gras
    //    next_patient{}       → <NextPatient>
    //      .full_name         → "full name : ....."
    //      .age               → "Age : 38"
    //      .gender            → "Gender :"
    //      .time              → "Time : 11:00 pm"
    //      .reason            → "Reason : Teeth whitening"
    //      .visit_type        → "visit type : ..."
    //      .allergies         → "Allergies :"
    //      .medical_notes     → "Medical nots :"
    //      .status            → "Status : confirmed / Pending"
    //      .due_payment       → "Due Payment : 1,2000 DA"
    //    requests[]           → <AppointmentRequests>
    //      .name              → {r.name}
    //      .type              → {r.type}
    //    review{}             → <PatientsReview>
    //      .bars[].label      → "Excellent"|"Great"|"Good"|"Average"
    //      .bars[].percent    → 19|26|27|28
    //    calendar{}           → <Calendar>
    //      .weeks[][]         → cell.n, cell.h, cell.t, cell.m
    //      .label             → "Mars 2026"
    //    trends[]             → <TrendChart>
    //      .date, .count
    //    unread_messages      → badge TopBar
    // ══════════════════════════════════════════════════════

    public function getDashboard(array $dentist): void {
        $dentistId = (int) $dentist['id_dentist'];
        $result    = $this->service->getDashboard($dentistId);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    // ══════════════════════════════════════════════════════
    //  PUT /api/dentist/status
    //  StatCard isStatus → dropdown "Available"/"Busy"/"Offline"
    //  Body : { "clinic_status": "Available" }
    // ══════════════════════════════════════════════════════

    public function updateStatus(array $dentist): void {
        $dentistId = (int) $dentist['id_dentist'];
        $data      = json_decode(file_get_contents('php://input'), true) ?? [];
        $result    = $this->service->updateStatus($dentistId, $data);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    // ══════════════════════════════════════════════════════
    //  PUT /api/dentist/appointments/{id}/accept
    //  Icône ✓ dans AppointmentRequests
    // ══════════════════════════════════════════════════════

    public function acceptRequest(array $dentist, int $appointmentId): void {
        $dentistId = (int) $dentist['id_dentist'];
        $result    = $this->service->acceptRequest($dentistId, $appointmentId);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    // ══════════════════════════════════════════════════════
    //  PUT /api/dentist/appointments/{id}/refuse
    //  Icône ✗ dans AppointmentRequests
    // ══════════════════════════════════════════════════════

    public function refuseRequest(array $dentist, int $appointmentId): void {
        $dentistId = (int) $dentist['id_dentist'];
        $result    = $this->service->refuseRequest($dentistId, $appointmentId);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    // ══════════════════════════════════════════════════════
    //  PUT /api/dentist/appointments/{id}/start
    //  Bouton "Start Consultation" dans NextPatient
    // ══════════════════════════════════════════════════════

    public function startConsultation(array $dentist, int $appointmentId): void {
        $dentistId = (int) $dentist['id_dentist'];
        $result    = $this->service->startConsultation($dentistId, $appointmentId);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    // ══════════════════════════════════════════════════════
    //  GET /api/dentist/patients/{id_patient}
    //  Bouton "view Details" dans NextPatient
    // ══════════════════════════════════════════════════════

    public function getPatientDetails(array $dentist, int $patientId): void {
        $dentistId = (int) $dentist['id_dentist'];
        $result    = $this->service->getPatientDetails($dentistId, $patientId);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    // ══════════════════════════════════════════════════════
    //  GET /api/dentist/calendar?year=2026&month=3
    //  Boutons ← → dans Calendar pour changer de mois
    // ══════════════════════════════════════════════════════

    public function getCalendar(array $dentist): void {
        $dentistId = (int) $dentist['id_dentist'];
        $result    = $this->service->getCalendar($dentistId, $_GET);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }
}