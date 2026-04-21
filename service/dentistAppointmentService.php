<?php

require_once __DIR__ . '/../models/dentistAppointmentModel.php';

class dentistAppointmentService {

    private $dentistAppointmentModel;
    public function __construct($pdo){
        $this->dentistAppointmentModel= new dentistAppointmentModel($pdo);
    }

    //getAll
    public function getAll($id_dentist, $filters = []) {
 
        $appointments = $this->dentistAppointmentModel->getAll($id_dentist, $filters);
 
        // Format each appointment for the frontend table
        $formatted = [];
        foreach ($appointments as $a) {
 
            // Convert "09:00:00" to "9:00 AM"
            $timeFormatted = date('g:i A', strtotime($a['appointment_time']));
 
            // Calculate age from birth_date if available
            // (not in this query but available in getOneWithPatient)
 
            $formatted[] = [
                'id_appointment'     => (int) $a['id_appointment'],
                'patient_name'       => $a['patient_name'],
                'patient_phone'      => $a['patient_phone'],
                'patient_email'      => $a['patient_email'],
                'id_patient'         => (int) $a['id_patient'],
                'appointment_date'   => $a['appointment_date'],
                'appointment_time'   => $timeFormatted,       // "9:00 AM"
                'appointment_status' => $a['appointment_status'], // "en_attente"
                'service_type'       => $a['service_type'],   // "Check-up"
                'reason'             => $a['reason'],
                'total_price'        => $a['total_price'],
            ];
        }
 
        return [
            'code' => 200,
            'body' => [
                'appointments' => $formatted,
                'total'        => count($formatted)
            ]
        ];
    }

    //getStats
    public function getStats($id_dentist) {
 
        $stats = $this->dentistAppointmentModel->getStats($id_dentist);
 
        return [
            'code' => 200,
            'body' => [
                // String with leading zeros for "021" display style
                'new_appointments'  => (int) $stats['new_appointments'],
                'total_this_month'  => (int) $stats['total_this_month'],
                // Format as "18,350 DA" for display
                'revenue_this_month'=> number_format((float) $stats['revenue_this_month'], 2),
                'revenue_raw'       => (float) $stats['revenue_this_month'],
            ]
        ];
    }

    //getDetails()
    public function getDetails($id_appointment, $id_dentist) {
 
        if (!$id_appointment || !is_numeric($id_appointment)) {
            return ['code' => 400, 'body' => ['message' => 'Invalid appointment ID.']];
        }
 
        $appointment = $this->dentistAppointmentModel->getOneWithPatient(
            $id_appointment,
            $id_dentist
        );
 
        if (!$appointment) {
            return ['code' => 404, 'body' => ['message' => 'Appointment not found.']];
        }
 
        // Calculate patient age from birth_date
        $age = null;
        if (!empty($appointment['patient_birth_date'])) {
            $birthDate = new DateTime($appointment['patient_birth_date']);
            $today     = new DateTime();
            $age       = $today->diff($birthDate)->y;
        }
 
        $timeFormatted = date('g:i A', strtotime($appointment['appointment_time']));
 
        return [
            'code' => 200,
            'body' => [
                'id_appointment'     => (int) $appointment['id_appointment'],
                'appointment_date'   => $appointment['appointment_date'],
                'appointment_time'   => $timeFormatted,
                'appointment_status' => $appointment['appointment_status'],
                'service_type'       => $appointment['service_type'],
                'reason'             => $appointment['reason'],
                'total_price'        => $appointment['total_price'],
                'patient' => [
                    'id_patient'   => (int) $appointment['id_patient'],
                    'full_name'    => $appointment['patient_name'],
                    'phone'        => $appointment['patient_phone'],
                    'email'        => $appointment['patient_email'],
                    'age'          => $age,
                    'address'      => $appointment['patient_address'],
                ]
            ]
        ];
    }


    //updatePrice()
    public function updatePrice($id_appointment, $id_dentist, $data) {
 
        $total_price = $data['total_price'] ?? null;
 
        if ($total_price === null || !is_numeric($total_price)) {
            return ['code' => 400, 'body' => ['message' => 'Valid price is required.']];
        }
 
        if ((float) $total_price < 0) {
            return ['code' => 400, 'body' => ['message' => 'Price cannot be negative.']];
        }
 
        $updated = $this->dentistAppointmentModel->updatePrice(
            $id_appointment,
            $id_dentist,
            (float) $total_price
        );
 
        if (!$updated) {
            return ['code' => 404, 'body' => ['message' => 'Appointment not found.']];
        }
 
        return [
            'code' => 200,
            'body' => ['message' => 'Price updated successfully.', 'total_price' => (float) $total_price]
        ];
    }

}
