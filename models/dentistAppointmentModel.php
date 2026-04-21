<?php

class dentistAppointmentModel{
    private $pdo;

    public function __construct ($pdo){
        $this->pdo =$pdo; 
    }

    //getall()
    //Used by the main appointments TABLE on the page
    // Returns all appointments for this dentist
    //support filtering
    function getAll($id_dentist, $filters = []){
        $conditions= ["a.id_dentist = ?"];
        $params = [$id_dentist];

       // Search by patient name
       if (!empty($filters['search'])) {
            $conditions[] = "p.full_name LIKE ?";
            $params[]     = '%' . $filters['search'] . '%';
        }

        // Filter by "Today" — only show today's appointments
        if (!empty($filters['today']) && $filters['today'] === 'true') {
            $conditions[] = "DATE(a.appointment_date) = CURDATE()";
        }

        // Filter by month — e.g. month=3 (March)
        if (!empty($filters['month'])) {
            $conditions[] = "MONTH(a.appointment_date) = ?";
            $params[]     = (int) $filters['month'];
        }

        // Filter by year — e.g. year=2026
        if (!empty($filters['year'])) {
            $conditions[] = "YEAR(a.appointment_date) = ?";
            $params[]     = (int) $filters['year'];
        }

        // Filter by status — e.g. status=confirme
        if (!empty($filters['status'])) {
            $conditions[] = "a.appointment_status = ?";
            $params[]     = $filters['status'];
        }

        $where = implode(' AND ', $conditions);

        $stmt= $this->pdo->prepare("
         SELECT
           a.id_appointment,
            a.appointment_date,
            a.appointment_time,
            a.appointment_status,
            a.service_type,
            a.reason,
            a.total_price,
            p.id_patient,
            p.full_name  AS patient_name,
            p.phone AS patient_phone,
            p.email  AS patient_email
         FROM appointment a
         JOIN patient p ON a.id_patient = p.id_patient
         WHERE {$where}
         ORDER BY a.appointment_date ASC, a.appointment_time ASC 
        ");

        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    //getStats()
    // Used by: the 3 stat cards at the top of the appointments page
    public function getStats($id_dentist){
        $stmt = $this->pdo->prepare("
            SELECT
                COUNT(CASE WHEN appointment_status = 'en_attente'
                   THEN 1 END) AS new_appointments,
 
                COUNT(CASE WHEN MONTH(appointment_date) = MONTH(CURDATE())
                AND YEAR(appointment_date)  = YEAR(CURDATE())
                   THEN 1 END) AS total_this_month,
 
                COALESCE(SUM(
                    CASE WHEN MONTH(appointment_date) = MONTH(CURDATE())
                   AND YEAR(appointment_date)  = YEAR(CURDATE())
                   AND appointment_status IN ('confirme', 'termine')
                    THEN total_price ELSE 0 END
                ), 0) AS revenue_this_month
 
            FROM appointment
            WHERE id_dentist = ?
        ");
        $stmt->execute([$id_dentist]);
        return $stmt->fetch();
    }

    //getOneWithPatient()
    // Used by: "View Details" button — opens the Patient Details panel
    public function getOneWithPatient($id_appointment, $id_dentist) {
        $stmt = $this->pdo->prepare("
            SELECT
                a.id_appointment,
                a.appointment_date,
                a.appointment_time,
                a.appointment_status,
                a.service_type,
                a.reason,
                a.total_price,
                p.id_patient,
                p.full_name   AS patient_name,
                p.phone  AS patient_phone,
                p.email  AS patient_email,
                p.birth_date   AS patient_birth_date,
                p.address      AS patient_address
            FROM appointment a
            JOIN patient p ON a.id_patient = p.id_patient
            WHERE a.id_appointment = ?
            AND   a.id_dentist     = ?
            LIMIT 1
        ");
        $stmt->execute([$id_appointment, $id_dentist]);
        return $stmt->fetch();
    }


    //updatePrice()
    // Used by: the edit (pencil) icon in the Actions column
    public function updatePrice($id_appointment, $id_dentist, $total_price) {
        $stmt = $this->pdo->prepare("
            UPDATE appointment
            SET    total_price = ?
            WHERE  id_appointment = ?
            AND    id_dentist     = ?
        ");
        $stmt->execute([$total_price, $id_appointment, $id_dentist]);
        return $stmt->rowCount();
    }

}