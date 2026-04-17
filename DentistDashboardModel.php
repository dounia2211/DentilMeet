<?php
declare(strict_types=1);

// ============================================================
//  dentistDashboardModel.php
//
//  Variables retournées = exactement ce qu'utilise Dentaldash.tsx
//
//  ┌─────────────────────────────────────────────────────────┐
//  │  STAT CARDS (ligne 495-498)                             │
//  │  <StatCard label="Clinic Status"     value="Available"  │
//  │  <StatCard label="Patients Seen Today" value="021"      │
//  │  <StatCard label="Today's Patients"  value="40"         │
//  │  <StatCard label="Total Patients"    value="+200"       │
//  │  → clinic_status, patients_seen_today,                  │
//  │    todays_patients, total_patients                      │
//  ├─────────────────────────────────────────────────────────┤
//  │  UPCOMING APPOINTMENTS (ligne 167-170)                  │
//  │  { name: "Full Name", time: "on going", live: true }    │
//  │  { name: "Full Name", time: "12:30 pm" }                │
//  │  → appointments[].name, appointments[].time,            │
//  │    appointments[].live (bool)                           │
//  ├─────────────────────────────────────────────────────────┤
//  │  NEXT PATIENT (ligne 243-271)                           │
//  │  full name : .....   Age : 38                           │
//  │  Gender :            Time : 11:00 pm                    │
//  │  Reason : Teeth whitening                               │
//  │  visit type : ...visit type: ...                        │
//  │  Allergies :         Medical nots :                     │
//  │  Status : confirmed / Pending                           │
//  │  Due Payment : 1,2000 DA                                │
//  │  → next_patient.full_name, .age, .gender, .time,        │
//  │    .reason, .visit_type, .allergies,                    │
//  │    .medical_notes, .status, .due_payment                │
//  ├─────────────────────────────────────────────────────────┤
//  │  APPOINTMENT REQUESTS (ligne 342-343)                   │
//  │  { name: "Name", type: "Visit type" }                   │
//  │  → requests[].name, requests[].type                     │
//  ├─────────────────────────────────────────────────────────┤
//  │  PATIENTS REVIEW (ligne 391)                            │
//  │  ["Excellent",19],["Great",26],["Good",27],["Average",28]│
//  │  → review.Excellent, .Great, .Good, .Average (%)        │
//  ├─────────────────────────────────────────────────────────┤
//  │  CALENDAR (ligne 437)                                   │
//  │  "Mars 2026" → month/year                               │
//  │  cell.n=day, cell.h=has_appt, cell.t=today, cell.m=other│
//  │  → calendar[row][col].n .h .t .m                        │
//  └─────────────────────────────────────────────────────────┘
//
//  TABLES DB :
//    dentist      → id_dentist, clinic_status, full_name
//    appointment  → id_appointment, appointment_date, appointment_time,
//                   appointment_status, reason, service_type, total_price,
//                   id_patient, id_dentist
//    patient      → id_patient, full_name, birth_date, photo
//    review       → id_review, rating, is_reported, id_dentist
//    chat_message → receiver_id, is_read, sender_type
//
//  ⚠️  SQL À LANCER UNE SEULE FOIS :
//  ALTER TABLE `dentist`
//    ADD COLUMN `clinic_status`
//      ENUM('Available','Busy','Offline') NOT NULL DEFAULT 'Available';
// ============================================================

class DentistDashboardModel {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // ══════════════════════════════════════════════════════
    //  STAT CARDS
    //  Dentaldash.tsx ligne 495-498 :
    //    value="Available"  → clinic_status
    //    value="021"        → patients_seen_today
    //    value="40"         → todays_patients
    //    value="+200"       → total_patients
    // ══════════════════════════════════════════════════════

    // clinic_status → "Available" | "Busy" | "Offline"
    // DB : dentist.clinic_status
    public function getClinicStatus(int $dentistId): string {
        $stmt = $this->pdo->prepare("
            SELECT clinic_status FROM dentist WHERE id_dentist = ?
        ");
        $stmt->execute([$dentistId]);
        return $stmt->fetchColumn() ?: 'Available';
    }

    public function updateClinicStatus(int $dentistId, string $status): bool {
        $stmt = $this->pdo->prepare("
            UPDATE dentist SET clinic_status = ? WHERE id_dentist = ?
        ");
        return $stmt->execute([$status, $dentistId]);
    }

    // patients_seen_today → "021"
    // DB : appointment.id_dentist, appointment_date=TODAY, appointment_status='termine'
    public function getPatientsSeen(int $dentistId): int {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM appointment
            WHERE id_dentist = ?
              AND appointment_date = CURDATE()
              AND appointment_status = 'termine'
        ");
        $stmt->execute([$dentistId]);
        return (int) $stmt->fetchColumn();
    }

    // todays_patients → "40"
    // DB : appointment.id_dentist, appointment_date=TODAY, status != annule
    public function getTodaysPatients(int $dentistId): int {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM appointment
            WHERE id_dentist = ?
              AND appointment_date = CURDATE()
              AND appointment_status != 'annule'
        ");
        $stmt->execute([$dentistId]);
        return (int) $stmt->fetchColumn();
    }

    // total_patients → "+200"
    // DB : appointment.id_dentist, COUNT DISTINCT id_patient
    public function getTotalPatients(int $dentistId): int {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(DISTINCT id_patient) FROM appointment
            WHERE id_dentist = ?
        ");
        $stmt->execute([$dentistId]);
        return (int) $stmt->fetchColumn();
    }

    // ══════════════════════════════════════════════════════
    //  UPCOMING APPOINTMENTS
    //  Dentaldash.tsx ligne 167-170 :
    //    { name: "Full Name", time: "on going", live: true }
    //    { name: "Full Name", time: "12:30 pm" }
    //  → appointments[].name  = patient.full_name
    //  → appointments[].time  = appointment_time formatté "h:i am/pm"
    //                           ou "on going" si c'est le 1er confirme
    //  → appointments[].live  = true si c'est le 1er (on going)
    // ══════════════════════════════════════════════════════

    public function getUpcomingAppointments(int $dentistId): array {
        $stmt = $this->pdo->prepare("
            SELECT
                a.id_appointment,
                p.full_name          AS name,
                DATE_FORMAT(a.appointment_time, '%l:%i %p') AS time,
                a.appointment_status,
                a.appointment_time   AS raw_time
            FROM appointment a
            JOIN patient p ON a.id_patient = p.id_patient
            WHERE a.id_dentist       = ?
              AND a.appointment_date  = CURDATE()
              AND a.appointment_status NOT IN ('annule','termine')
            ORDER BY a.appointment_time ASC
            LIMIT 4
        ");
        $stmt->execute([$dentistId]);
        $rows = $stmt->fetchAll();

        // Le premier devient "on going" avec live=true
        // comme dans le front : { name: "Full Name", time: "on going", live: true }
        foreach ($rows as $i => &$row) {
            if ($i === 0) {
                $row['time'] = 'on going';
                $row['live'] = true;
            } else {
                $row['live'] = false;
            }
            unset($row['appointment_status'], $row['raw_time']);
        }
        return $rows;
    }

    // ══════════════════════════════════════════════════════
    //  NEXT PATIENT DETAILS
    //  Dentaldash.tsx ligne 243-271 :
    //    full name : .....  → next_patient.full_name
    //    Age : 38           → next_patient.age
    //    Gender :           → next_patient.gender  (null en DB)
    //    Time : 11:00 pm    → next_patient.time
    //    Reason : Teeth whitening → next_patient.reason
    //    visit type : ...   → next_patient.visit_type
    //    Allergies :        → next_patient.allergies  (null en DB)
    //    Medical nots :     → next_patient.medical_notes (null en DB)
    //    Status : confirmed / Pending → next_patient.status
    //    Due Payment : 1,2000 DA → next_patient.due_payment
    // ══════════════════════════════════════════════════════

    public function getNextPatient(int $dentistId): array|false {
        $stmt = $this->pdo->prepare("
            SELECT
                a.id_appointment,
                p.full_name,
                TIMESTAMPDIFF(YEAR, p.birth_date, CURDATE()) AS age,
                DATE_FORMAT(a.appointment_time, '%l:%i %p')  AS time,
                a.reason,
                a.service_type                                AS visit_type,
                a.appointment_status                          AS status,
                a.total_price                                 AS due_payment,
                p.photo
            FROM appointment a
            JOIN patient p ON a.id_patient = p.id_patient
            WHERE a.id_dentist       = ?
              AND a.appointment_date  = CURDATE()
              AND a.appointment_status NOT IN ('annule','termine')
            ORDER BY a.appointment_time ASC
            LIMIT 1
        ");
        $stmt->execute([$dentistId]);
        $row = $stmt->fetch();
        if (!$row) return false;

        // gender et allergies et medical_notes n'existent pas en DB
        $row['gender']        = null;
        $row['allergies']     = null;
        $row['medical_notes'] = null;

        return $row;
    }

    // ══════════════════════════════════════════════════════
    //  APPOINTMENT REQUESTS
    //  Dentaldash.tsx ligne 342-343 :
    //    { name: "Name", type: "Visit type" }
    //  → requests[].name = patient.full_name
    //  → requests[].type = appointment.service_type
    // ══════════════════════════════════════════════════════

    public function getAppointmentRequests(int $dentistId): array {
        $stmt = $this->pdo->prepare("
            SELECT
                a.id_appointment,
                p.full_name          AS name,
                a.service_type       AS type,
                a.appointment_date,
                DATE_FORMAT(a.appointment_time, '%l:%i %p') AS time,
                p.photo
            FROM appointment a
            JOIN patient p ON a.id_patient = p.id_patient
            WHERE a.id_dentist        = ?
              AND a.appointment_status = 'en_attente'
            ORDER BY a.appointment_date ASC, a.appointment_time ASC
            LIMIT 5
        ");
        $stmt->execute([$dentistId]);
        return $stmt->fetchAll();
    }

    public function acceptRequest(int $appointmentId, int $dentistId): bool {
        $stmt = $this->pdo->prepare("
            UPDATE appointment
            SET appointment_status = 'confirme'
            WHERE id_appointment = ? AND id_dentist = ?
        ");
        $stmt->execute([$appointmentId, $dentistId]);
        return $stmt->rowCount() > 0;
    }

    public function refuseRequest(int $appointmentId, int $dentistId): bool {
        $stmt = $this->pdo->prepare("
            UPDATE appointment
            SET appointment_status = 'annule'
            WHERE id_appointment = ? AND id_dentist = ?
        ");
        $stmt->execute([$appointmentId, $dentistId]);
        return $stmt->rowCount() > 0;
    }

    // ══════════════════════════════════════════════════════
    //  PATIENTS REVIEW
    //  Dentaldash.tsx ligne 391 :
    //    ["Excellent",19],["Great",26],["Good",27],["Average",28]
    //  Le front attend : un tableau [label, pourcentage%]
    //  → review.Excellent = % des notes = 5
    //  → review.Great     = % des notes = 4
    //  → review.Good      = % des notes = 3
    //  → review.Average   = % des notes <= 2
    //  DB : review.rating, review.is_reported, review.id_dentist
    // ══════════════════════════════════════════════════════

    public function getReviewStats(int $dentistId): array {
        $stmt = $this->pdo->prepare("
            SELECT
                COUNT(*)                                          AS total,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END)     AS count_5,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END)     AS count_4,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END)     AS count_3,
                SUM(CASE WHEN rating <= 2 THEN 1 ELSE 0 END)    AS count_2,
                ROUND(AVG(rating), 1)                            AS average
            FROM review
            WHERE id_dentist = ? AND is_reported = 0
        ");
        $stmt->execute([$dentistId]);
        $r = $stmt->fetch();

        $total = max(1, (int)$r['total']);

        // Retourner exactement le format du front :
        // [["Excellent", 19], ["Great", 26], ["Good", 27], ["Average", 28]]
        return [
            'bars' => [
                ['label' => 'Excellent', 'percent' => (int) round($r['count_5'] / $total * 100)],
                ['label' => 'Great',     'percent' => (int) round($r['count_4'] / $total * 100)],
                ['label' => 'Good',      'percent' => (int) round($r['count_3'] / $total * 100)],
                ['label' => 'Average',   'percent' => (int) round($r['count_2'] / $total * 100)],
            ],
            'average' => (float) $r['average'],
            'total'   => (int)   $r['total'],
        ];
    }

    // ══════════════════════════════════════════════════════
    //  CALENDAR
    //  Dentaldash.tsx ligne 405-425 :
    //    CAL = tableau de semaines, chaque cellule :
    //    { n: day_number, h: has_appointment, t: is_today, m: other_month }
    //  → calendar[].n  = numéro du jour
    //  → calendar[].h  = true si RDV ce jour (surlignage vert clair)
    //  → calendar[].t  = true si aujourd'hui (fond vert plein)
    //  → calendar[].m  = true si jour d'un autre mois (grisé)
    //  DB : appointment.appointment_date, id_dentist, appointment_status
    // ══════════════════════════════════════════════════════

    public function getCalendarData(int $dentistId, int $year, int $month): array {
        // Récupérer les jours qui ont des RDV
        $stmt = $this->pdo->prepare("
            SELECT DAY(appointment_date) AS day
            FROM appointment
            WHERE id_dentist            = ?
              AND YEAR(appointment_date)  = ?
              AND MONTH(appointment_date) = ?
              AND appointment_status     != 'annule'
            GROUP BY DAY(appointment_date)
        ");
        $stmt->execute([$dentistId, $year, $month]);
        $apptDays = array_column($stmt->fetchAll(), 'day');

        $today     = (int) date('d');
        $todayY    = (int) date('Y');
        $todayM    = (int) date('n');
        $isThisMonth = ($year === $todayY && $month === $todayM);

        // Construire le tableau comme CAL dans le front
        // Chaque cellule : { n, h, t, m }
        // n = numéro, h = has_appt, t = today, m = other_month
        $firstDow  = (int) date('N', mktime(0,0,0,$month,1,$year)); // 1=Mon 7=Sun
        $daysInMonth = (int) date('t', mktime(0,0,0,$month,1,$year));
        $prevMonth   = $month === 1 ? 12 : $month - 1;
        $prevYear    = $month === 1 ? $year - 1 : $year;
        $daysInPrev  = (int) date('t', mktime(0,0,0,$prevMonth,1,$prevYear));

        $cells = [];
        // Jours du mois précédent
        for ($i = $firstDow - 1; $i > 0; $i--) {
            $cells[] = ['n' => $daysInPrev - $i + 1, 'm' => true, 'h' => false, 't' => false];
        }
        // Jours du mois courant
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $cells[] = [
                'n' => $d,
                'm' => false,
                'h' => in_array($d, $apptDays),
                't' => ($isThisMonth && $d === $today),
            ];
        }
        // Compléter avec le mois suivant
        $next = 1;
        while (count($cells) % 7 !== 0) {
            $cells[] = ['n' => $next++, 'm' => true, 'h' => false, 't' => false];
        }

        // Regrouper en semaines comme le front : CAL = [row0, row1, ...]
        $weeks = array_chunk($cells, 7);

        return [
            'weeks' => $weeks,        // format identique à CAL du front
            'year'  => $year,
            'month' => $month,
            'label' => date('F Y', mktime(0,0,0,$month,1,$year)), // "Mars 2026"
        ];
    }

    // ══════════════════════════════════════════════════════
    //  PATIENT TRENDS (graphique)
    //  Dentaldash.tsx : TrendChart — axes Jan/Feb/Mar
    //  → trends[].date  = "2026-01-15"
    //  → trends[].count = nombre de RDV ce jour
    //  DB : appointment.appointment_date, id_dentist
    // ══════════════════════════════════════════════════════

    public function getPatientTrends(int $dentistId, int $days = 90): array {
        $stmt = $this->pdo->prepare("
            SELECT
                appointment_date AS date,
                COUNT(*)         AS count
            FROM appointment
            WHERE id_dentist        = ?
              AND appointment_date  >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
              AND appointment_status != 'annule'
            GROUP BY appointment_date
            ORDER BY appointment_date ASC
        ");
        $stmt->execute([$dentistId, $days]);
        return $stmt->fetchAll();
    }

    // ══════════════════════════════════════════════════════
    //  START CONSULTATION
    //  Bouton "Start Consultation" dans NextPatient
    //  → passe appointment_status de 'confirme' à 'termine'
    // ══════════════════════════════════════════════════════

    public function startConsultation(int $appointmentId, int $dentistId): bool {
        $stmt = $this->pdo->prepare("
            UPDATE appointment
            SET appointment_status = 'termine'
            WHERE id_appointment   = ?
              AND id_dentist        = ?
              AND appointment_status = 'confirme'
        ");
        $stmt->execute([$appointmentId, $dentistId]);
        return $stmt->rowCount() > 0;
    }

    // ══════════════════════════════════════════════════════
    //  VIEW DETAILS — bouton "view Details" dans NextPatient
    //  → profil complet du patient + historique RDV
    // ══════════════════════════════════════════════════════

    public function getPatientDetails(int $patientId): array|false {
        $stmt = $this->pdo->prepare("
            SELECT
                p.id_patient,
                p.full_name,
                p.email,
                p.phone,
                p.photo,
                p.birth_date,
                TIMESTAMPDIFF(YEAR, p.birth_date, CURDATE()) AS age,
                p.address,
                p.created_at AS member_since
            FROM patient p
            WHERE p.id_patient = ?
        ");
        $stmt->execute([$patientId]);
        return $stmt->fetch();
    }

    public function getPatientHistory(int $patientId, int $dentistId): array {
        $stmt = $this->pdo->prepare("
            SELECT
                id_appointment,
                appointment_date,
                DATE_FORMAT(appointment_time, '%l:%i %p') AS time,
                appointment_status                         AS status,
                service_type                               AS visit_type,
                reason,
                total_price                                AS due_payment
            FROM appointment
            WHERE id_patient = ? AND id_dentist = ?
            ORDER BY appointment_date DESC
            LIMIT 10
        ");
        $stmt->execute([$patientId, $dentistId]);
        return $stmt->fetchAll();
    }

    // ══════════════════════════════════════════════════════
    //  MESSAGES INBOX
    //  TopBar : badge notifications
    //  DB : chat_message.receiver_id, is_read, sender_type='patient'
    // ══════════════════════════════════════════════════════

    public function getUnreadCount(int $dentistId): int {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM chat_message
            WHERE receiver_id = ?
              AND sender_type = 'patient'
              AND is_read     = 0
        ");
        $stmt->execute([$dentistId]);
        return (int) $stmt->fetchColumn();
    }
}