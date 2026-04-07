<?php

class appointmentModel{
    private $pdo;

    public function __construct($pdo){
        $this->pdo = $pdo;
    }

    //get all appointments for a patient
    public function getAllByPatient ($id_patient){
        $stmt= $this->pdo->prepare("
          SELECT
            a.id_appointment,
            a.appointment_date,
            a.appointment_time,
            a.appointment_status,
            a.service_type,
            a.reason,
            d.full_name       AS dentist_name,
            d.speciality AS dentist_speciality
            FROM appointment a
            JOIN dentist d ON a.id_dentist = d.id_dentist
            WHERE a.id_patient = ?
            ORDER BY a.appointment_date DESC
        ");
        $stmt->execute([$id_patient]);
        return $stmt->fetchAll();
    }

    //get one appointment by ID
    public function findById($id_appointment, $id_patient){
        $stmt = $this->pdo->prepare("
            SELECT
            a.id_appointment,
            a.appointment_date,
            a.appointment_time,
            a.appointment_status,
            a.service_type,
            a.reason,
            a.total_price,
            d.full_name AS dentist_name,
            d.speciality AS dentist_speciality
            FROM appointment a
            JOIN dentist d ON a.id_dentist = d.id_dentist
            WHERE a.id_appointment = ?
            AND   a.id_patient     = ?
            LIMIT 1
        ");
        $stmt->execute([$id_appointment, $id_patient]);
        return $stmt->fetch();
    }

    //get upcoming appointments count (for dashboard card)
    public function countUpcoming($id_patient){
        $stmt = $this->pdo->prepare("
           SELECT COUNT(*) as total
           FROM appointment
           WHERE id_patient = ?
           AND appointment_date >= CURDATE()
           AND appointment_status = 'confirme'
       ");
        $stmt->execute([$id_patient]);
        return (int) $stmt->fetch()['total'];
    }

    //book a new appointment
    public function create($id_patient, $id_dentist, $date, $time, $service_type, $reason) {
       $stmt = $this->pdo->prepare("
            INSERT INTO appointment
            (id_patient, id_dentist, appointment_date, appointment_time,
            appointment_status, service_type, reason)
            VALUES (?, ?, ?, ?, 'en_attente', ?, ?)"
        );
      $stmt->execute([$id_patient, $id_dentist, $date, $time, $service_type, $reason]);
      return $this->pdo->lastInsertId();
    }

    //cancel an appointment
    public function cancel ($id_appointment, $id_patient){
        $stmt= $this->pdo->prepare("
            UPDATE appointment 
            SET appointment_status = 'annule'
            WHERE id_appointment = ? AND id_patient= ?
        ");
        $stmt->execute([$id_appointment, $id_patient]);
        return $stmt->rowCount(); //return 1 if updated, 0 if not found
    }

   //get booked days so it can grey them out
    public function getBookedDays ($id_dentist, $year, $month){
        $stmt = $this->pdo->prepare("
            SELECT DAY(appointment_date) as day FROM appointment 
            WHERE id_dentist = ? 
            AND YEAR(appointment_date) = ?
            AND MONTH(appointment_date) = ? 
            AND appointment_status != 'annule'
       ");

       $stmt-> execute([$id_dentist, $year, $month]);
       return array_column($stmt->fetchAll(), 'day');
    } 

    //get available slots
    public function getBookedSlots ($id_dentist, $date){
        $stmt =$this->pdo->prepare("
          SELECT TIME_FORMAT (appointment_time, '%l:%i %p') as time
          FROM appointment 
          WHERE id_dentist = ?
          AND appointment_date = ?
          AND appointment_status != 'annule'
       ");
        $stmt->execute([$id_dentist, $date]);
        return array_column($stmt->fetchAll(), 'time');
    }

    
} 
