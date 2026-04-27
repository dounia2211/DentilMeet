<?php
declare(strict_types=1);
//  dentistDashboardModel.php

class DentistDashboardModel {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

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
    //  UPCOMING APPOINTMENT
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
    //  APPOINTMENT REQUEST
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
    // Utilisé par DentistDashboardService::acceptRequest() et refuseRequest()
    // Pour récupérer patient_name, dentist_name, date, time
    public function getAppointmentById(int $appointmentId): array|false {
        $stmt = $this->pdo->prepare("
            SELECT
                a.id_appointment,
                a.appointment_date,
                a.appointment_time,
                a.id_patient,
                a.id_dentist,
                p.full_name AS patient_name,
                d.full_name AS dentist_name
                FROM appointment a
            JOIN patient p ON a.id_patient = p.id_patient
            JOIN dentist  d ON a.id_dentist = d.id_dentist
            WHERE a.id_appointment = ?
            LIMIT 1
        ");
        $stmt->execute([$appointmentId]);
        return $stmt->fetch();
    }
}
