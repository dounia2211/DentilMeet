<?php

class dentistPatientProfileModel {
    private $pdo;
    public function __construct($pdo){
        $this->pdo = $pdo;
    }

    //getPatientInfo()
    // Gets patient basic info from your patient table.
    public function getPatientInfo($id_patient, $id_dentist){
        $stmt=$this->pdo->prepare("
          SELECT
            p.id_patient,
            p.full_name,
            p.phone,
            p.email,
            p.birth_date,
            p.gender,
            p.address,
            p.medical_conditions,
            p.medications,
            p.allergies,
            p.surgical_history
            FROM patient p
            WHERE p.id_patient = ?
            AND EXISTS (
                SELECT 1 FROM appointment a
                WHERE a.id_patient = p.id_patient
                AND   a.id_dentist = ?
            )
            LIMIT 1 
       ");
       $stmt->execute([$id_patient, $id_dentist]);
       return $stmt->fetch();
    }

    //  getMyVisits()
    // Used by: "My Patients Visits" tab
    public function getMyVisits($id_patient, $id_dentist) {
        $stmt = $this->pdo->prepare("
            SELECT
                a.id_appointment,
                a.appointment_date,
                a.appointment_time,
                a.service_type,
                a.reason,
                a.appointment_status,
                a.total_price,
                a.deposit_amount
            FROM appointment a
            WHERE a.id_patient = ?
            AND   a.id_dentist = ?
            ORDER BY a.appointment_date DESC, a.appointment_time DESC
        ");
        $stmt->execute([$id_patient, $id_dentist]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    //getAllVisits()
    // Used by: "All Visits" tab
    public function getAllVisits($id_patient) {
        $stmt = $this->pdo->prepare("
            SELECT
                a.id_appointment,
                a.appointment_date,
                a.appointment_time,
                a.service_type,
                a.reason,
                a.appointment_status,
                a.total_price,
                d.full_name AS dentist_name
            FROM appointment a
            JOIN dentist d ON a.id_dentist = d.id_dentist
            WHERE a.id_patient = ?
            ORDER BY a.appointment_date DESC, a.appointment_time DESC
        ");
        $stmt->execute([$id_patient]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // getPayments()
    // Used by: "Payments" tab
    public function getPayments($id_patient, $id_dentist) {
        $stmt = $this->pdo->prepare("
            SELECT
                p.id_payment,
                p.amount,
                p.method_payment,
                p.payment_status,
                p.paid_at,
                p.invoice_number,
                p.invoice_path,
                a.appointment_date,
                a.appointment_time,
                a.service_type      AS related_visit,
                a.id_appointment
            FROM payment p
            JOIN appointment a ON p.id_appointment = a.id_appointment
            WHERE a.id_patient = ?
            AND   a.id_dentist = ?
            ORDER BY a.appointment_date DESC, a.appointment_time DESC
        ");
        $stmt->execute([$id_patient, $id_dentist]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    //getDocuments()
    // Used by: Files/Documents section
    public function getDocuments($id_patient, $id_dentist) {
        $stmt = $this->pdo->prepare("
            SELECT
                d.id_document,
                d.file_name,
                d.file_type,
                d.document_type,
                d.upload_date,
                d.file_path,
                d.notes
            FROM document d
            WHERE d.id_patient = ?
            AND   d.id_dentist = ?
            ORDER BY d.upload_date DESC
        ");
        $stmt->execute([$id_patient, $id_dentist]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    //  uploadDocument()
    // Used by: upload icon in Files/Documents section
    public function uploadDocument(
        $id_patient,
        $id_dentist,
        $file_name,
        $file_type,
        $document_type,
        $file_path,
        $notes = null,
        $id_appointment = null
      ) {
        $stmt = $this->pdo->prepare("
            INSERT INTO document
                (file_name, file_type, document_type, file_path,
                 notes, id_patient, id_dentist, id_appointment)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $file_name,
            $file_type,
            $document_type,
            $file_path,
            $notes,
            $id_patient,
            $id_dentist,
            $id_appointment
        ]);
        return $this->pdo->lastInsertId();
    }

    // updateAppointmentPrice()
    // Used by: Price field + Save button at bottom
    public function updateAppointmentPrice($id_appointment, $id_dentist, $price) {
        $stmt = $this->pdo->prepare("
            UPDATE appointment
            SET    total_price = ?
            WHERE  id_appointment = ?
            AND    id_dentist     = ?
        ");
        $stmt->execute([$price, $id_appointment, $id_dentist]);
        return $stmt->rowCount();
    }

    public function updateAnamnesis($id_patient, $id_dentist, $data) {
        // Verify patient belongs to this dentist first
        $check = $this->pdo->prepare("
            SELECT COUNT(*) as total FROM appointment
            WHERE id_patient = ? AND id_dentist = ?
        ");
        $check->execute([$id_patient, $id_dentist]);
        if (!(int)$check->fetch()['total']) return 0;

        $stmt = $this->pdo->prepare("
            UPDATE patient
            SET medical_conditions = ?,
                medications        = ?,
                allergies          = ?,
                surgical_history   = ?
            WHERE id_patient = ?
        ");
        $stmt->execute([
            $data['medical_conditions'] ?? null,
            $data['medications']        ?? null,
            $data['allergies']          ?? 'No known allergies',
            $data['surgical_history']   ?? null,
            $id_patient
        ]);
        return $stmt->rowCount();
    }
}
