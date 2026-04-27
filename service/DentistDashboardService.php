<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/dentistDashboardModel.php';

class DentistDashboardService {

    private $m; // model
    private const VALID_STATUS = ['Available', 'Busy', 'Offline'];

    public function __construct($pdo) {
        $this->m = new DentistDashboardModel($pdo);
    }

    // ══════════════════════════════════════════════════════
    //  getDashboard() — 1 seul appel pour tout le dashboard
    //  GET /api/dentist/dashboard
    //
    //  Retourne EXACTEMENT ce qu'utilise Dentaldash.tsx :
    //
    //  {
    //    // ── STAT CARDS ─────────────────────────────────
    //    "clinic_status":        "Available",   ← label="Clinic Status" value=?
    //    "patients_seen_today":  "021",          ← label="Patients Seen Today"
    //    "todays_patients":      "40",           ← label="Today's Patients"
    //    "total_patients":       "+200",         ← label="Total Patients"
    //
    //    // ── UPCOMING APPOINTMENTS ──────────────────────
    //    "appointments": [
    //      { "id_appointment": 7, "name": "Full Name",   ← a.name dans .map
    //        "time": "on going", "live": true },          ← a.time, a.live
    //      { "id_appointment": 8, "name": "Full Name",
    //        "time": "12:30 pm", "live": false }
    //    ],
    //
    //    // ── NEXT PATIENT ───────────────────────────────
    //    "next_patient": {
    //      "id_appointment": 7,
    //      "full_name":    "......",   ← "full name : ....."
    //      "age":          38,         ← "Age : 38"
    //      "gender":       null,       ← "Gender :" (absent en DB)
    //      "time":         "11:00 pm", ← "Time : 11:00 pm"
    //      "reason":       "Teeth whitening", ← "Reason :"
    //      "visit_type":   "Cleaning", ← "visit type :"
    //      "allergies":    null,       ← "Allergies :" (absent en DB)
    //      "medical_notes": null,      ← "Medical nots :" (absent en DB)
    //      "status":       "confirme", ← "Status : confirmed / Pending"
    //      "due_payment":  1200.00,    ← "Due Payment : 1,2000 DA"
    //      "photo":        null
    //    },
    //
    //    // ── APPOINTMENT REQUESTS ───────────────────────
    //    "requests": [
    //      { "id_appointment": 5, "name": "Name",        ← r.name
    //        "type": "Visit type", "time": "9:00 am" }   ← r.type
    //    ],
    //
    //    // ── PATIENTS REVIEW ────────────────────────────
    //    "review": {
    //      "bars": [
    //        { "label": "Excellent", "percent": 19 },    ← ["Excellent",19]
    //        { "label": "Great",     "percent": 26 },    ← ["Great",26]
    //        { "label": "Good",      "percent": 27 },    ← ["Good",27]
    //        { "label": "Average",   "percent": 28 }     ← ["Average",28]
    //      ],
    //      "average": 4.2,
    //      "total": 42
    //    },
    //
    //    // ── CALENDAR ───────────────────────────────────
    //    "calendar": {
    //      "weeks": [                  ← identique à CAL du front
    //        [{"n":29,"m":true,"h":false,"t":false}, ...],
    //        [{"n":5,"m":false,"h":false,"t":false},
    //         {"n":6,"m":false,"h":true,"t":false},  ← h=true = RDV
    //         {"n":10,"m":false,"h":false,"t":true}, ← t=true = today
    //         ...]
    //      ],
    //      "label": "Mars 2026",       ← affiché dans le header calendrier
    //      "year": 2026,
    //      "month": 3
    //    },
    //
    //    // ── PATIENT TRENDS ─────────────────────────────
    //    "trends": [
    //      { "date": "2026-01-15", "count": 4 },
    //      { "date": "2026-02-10", "count": 7 }
    //    ],
    //
    //    // ── MESSAGES ───────────────────────────────────
    //    "unread_messages": 3           ← badge TopBar
    //  }
    // ══════════════════════════════════════════════════════

    public function getDashboard(int $dentistId): array {
        $year  = (int) date('Y');
        $month = (int) date('n');

        // ── stat cards
        $clinicStatus       = $this->m->getClinicStatus($dentistId);
        $patientsSeen       = $this->m->getPatientsSeen($dentistId);
        $todaysPatients     = $this->m->getTodaysPatients($dentistId);
        $totalPatients      = $this->m->getTotalPatients($dentistId);

        // ── upcoming appointments
        // format : [{ name, time, live }] comme appts[] du front
        $appointments       = $this->m->getUpcomingAppointments($dentistId);

        // ── next patient
        $nextPatient        = $this->m->getNextPatient($dentistId);

        // ── appointment requests
        // format : [{ name, type }] comme reqs[] du front
        $requests           = $this->m->getAppointmentRequests($dentistId);

        // ── patients review
        // format : { bars:[{label,percent}], average, total }
        $review             = $this->m->getReviewStats($dentistId);

        // ── calendar
        // format : { weeks:[[{n,h,t,m},...]], label, year, month }
        $calendar           = $this->m->getCalendarData($dentistId, $year, $month);

        // ── patient trends
        $trends             = $this->m->getPatientTrends($dentistId, 90);

        // ── unread messages badge
        $unreadMessages     = $this->m->getUnreadCount($dentistId);

        return [
            'code' => 200,
            'body' => [
                // STAT CARDS — valeurs exactes affichées dans StatCard
                'clinic_status'       => $clinicStatus,
                'patients_seen_today' => str_pad((string)$patientsSeen, 3, '0', STR_PAD_LEFT), // "021"
                'todays_patients'     => (string)$todaysPatients,                              // "40"
                'total_patients'      => '+' . $totalPatients,                                 // "+200"

                // UPCOMING APPOINTMENTS
                // chaque item : { id_appointment, name, time, live }
                'appointments'        => $appointments,

                // NEXT PATIENT
                // { id_appointment, full_name, age, gender, time, reason,
                //   visit_type, allergies, medical_notes, status, due_payment, photo }
                'next_patient'        => $nextPatient,

                // APPOINTMENT REQUESTS
                // chaque item : { id_appointment, name, type, time, photo }
                'requests'            => $requests,

                // PATIENTS REVIEW
                // { bars:[{label,percent}], average, total }
                'review'              => $review,

                // CALENDAR
                // { weeks:[[{n,h,t,m},...]], label:"Mars 2026", year, month }
                'calendar'            => $calendar,

                // PATIENT TRENDS
                // [{ date, count }]
                'trends'              => $trends,

                // MESSAGES badge
                'unread_messages'     => $unreadMessages,
            ]
        ];
    }

    // ══════════════════════════════════════════════════════
    //  CLINIC STATUS
    //  PUT /api/dentist/status
    //  Body : { "clinic_status": "Available" | "Busy" | "Offline" }
    // ══════════════════════════════════════════════════════

    public function updateStatus(int $dentistId, array $data): array {
        $status = trim($data['clinic_status'] ?? '');
        if (!in_array($status, self::VALID_STATUS)) {
            return ['code' => 400, 'body' => [
                'message' => 'clinic_status must be: Available, Busy, or Offline.'
            ]];
        }
        $this->m->updateClinicStatus($dentistId, $status);
        return ['code' => 200, 'body' => [
            'message'       => 'Status updated.',
            'clinic_status' => $status
        ]];
    }

    // ══════════════════════════════════════════════════════
    //  APPOINTMENT REQUESTS — accept / refuse
    //  Boutons ✓ ✗ dans AppointmentRequests
    //  PUT /api/dentist/appointments/{id}/accept
    //  PUT /api/dentist/appointments/{id}/refuse
    // ══════════════════════════════════════════════════════

    public function acceptRequest(int $dentistId, int $appointmentId): array {
        $ok = $this->m->acceptRequest($appointmentId, $dentistId);
        if (!$ok) return ['code' => 404, 'body' => ['message' => 'Request not found.']];

          // Récupère les infos du RDV pour le message
    $appt = $this->m->getAppointmentById($appointmentId); 
    if ($appt) {
    require_once __DIR__ . '/../models/dentistNotificationModel.php';
    $dentistNotif = new DentistNotificationModel($this->pdo);
    $dentistNotif->createConfirmedNotif(
    $dentistId,
    $appt['patient_name'],
    $appt['appointment_date'], 
    $appt['appointment_time']  
);


    // "Your appointment has been confirmed"
    require_once __DIR__ . '/../service/notificationService.php';
    $notifPatient = new notificationService($this->pdo);
    $notifPatient->createConfirmationByDentistNotification(
        (int)$appt['id_patient'],
        $appt['dentist_name'],
        $appt['appointment_date'],
        $appt['appointment_time'],
        $dentistId
    );
    }
        
        return ['code' => 200, 'body' => ['message' => 'Appointment confirmed.']];
    }

    public function refuseRequest(int $dentistId, int $appointmentId): array {
        $ok = $this->m->refuseRequest($appointmentId, $dentistId);
        if (!$ok) return ['code' => 404, 'body' => ['message' => 'Request not found.']];

          //"Your appointment was not accepted"
    $appt = $this->m->getAppointmentById($appointmentId);

    require_once __DIR__ . '/../service/notificationService.php';
    $notifPatient = new notificationService($this->pdo);
    $notifPatient->createRefusedNotification(
        (int)$appt['id_patient'],
        $appt['dentist_name'],
        $appt['appointment_date'],
        $dentistId
    );
        
        return ['code' => 200, 'body' => ['message' => 'Appointment cancelled.']];
    }

    // ══════════════════════════════════════════════════════
    //  START CONSULTATION
    //  Bouton "Start Consultation" dans NextPatient
    //  PUT /api/dentist/appointments/{id}/start
    // ══════════════════════════════════════════════════════

    public function startConsultation(int $dentistId, int $appointmentId): array {
        $ok = $this->m->startConsultation($appointmentId, $dentistId);
        if (!$ok) return ['code' => 404, 'body' => ['message' => 'Appointment not found or not confirmed.']];
        return ['code' => 200, 'body' => [
            'message'        => 'Consultation started.',
            'id_appointment' => $appointmentId
        ]];
    }

    // ══════════════════════════════════════════════════════
    //  VIEW DETAILS
    //  Bouton "view Details" dans NextPatient
    //  GET /api/dentist/patients/{id_patient}
    // ══════════════════════════════════════════════════════

    public function getPatientDetails(int $dentistId, int $patientId): array {
        if ($patientId <= 0) return ['code' => 400, 'body' => ['message' => 'Invalid id_patient.']];
        $patient = $this->m->getPatientDetails($patientId);
        if (!$patient) return ['code' => 404, 'body' => ['message' => 'Patient not found.']];
        $history = $this->m->getPatientHistory($patientId, $dentistId);
        return ['code' => 200, 'body' => [
            'patient' => $patient,
            'history' => $history
        ]];
    }

    // ══════════════════════════════════════════════════════
    //  CALENDAR — changer de mois
    //  GET /api/dentist/calendar?year=2026&month=3
    // ══════════════════════════════════════════════════════

    public function getCalendar(int $dentistId, array $query): array {
        $year  = (int)($query['year']  ?? date('Y'));
        $month = (int)($query['month'] ?? date('n'));
        if ($month < 1 || $month > 12) {
            return ['code' => 400, 'body' => ['message' => 'month must be 1-12.']];
        }
        $calendar = $this->m->getCalendarData($dentistId, $year, $month);
        return ['code' => 200, 'body' => $calendar];
    }
}
