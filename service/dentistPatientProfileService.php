<?php

require_once __DIR__ .'/../models/dentistPatientProfileModel.php';

class dentistPatientProfileService {
    private $model;
    public function __construct ($pdo){
        $this->model = new dentistPatientProfileModel($pdo);
    }

    //getProfile()
    // Route: GET /api/dentist/patients/:id/profile
    // Called when: dentist opens a patient's profile page
    // Returns everything in ONE call:
    //   patient info, anamnesis (hardcoded), my visits, documents
    public function getProfile ($id_patient, $id_dentist){
        
        if (!$id_patient || !is_numeric($id_patient)){
            return ['code' => 400 , 'body' => ['messaage' => 'Invalid patient ID.']];
        }

        $patient = $this->model->getPatientInfo($id_patient, $id_dentist);

        if (!$patient) {
            return [
                'code' => 404,
                'body' => ['message' => 'Patient not found or no appointment with this dentist']
            ];
        }

         // Calculate age from birth_date
        // birth_date format in DB: "1988-03-15"
        $age = null;
        if (!empty($patient['birth_date']) && $patient['birth_date'] !== '0000-00-00') {
            $birth = new DateTime($patient['birth_date']);
            $today = new DateTime();
            $age   = $today->diff($birth)->y;
        }
 
        // Get my visits (this dentist only)
        $myVisits = $this->model->getMyVisits($id_patient, $id_dentist);
        $myVisitsFormatted = array_map(function($v) {
            return [
                'id_appointment'     => (int) $v['id_appointment'],
                'date'               => $v['appointment_date'],
                'time'               => date('g:i A', strtotime($v['appointment_time'])),
                'treatment'          => $v['service_type'],   // "Dental Cleaning"
                'note'               => $v['reason'],          // tooth / note column
                'status'             => $v['appointment_status'],
                'total_price'        => (float) $v['total_price'],
            ];
        }, $myVisits);
 
        // Get documents from your document table
        $documents = $this->model->getDocuments($id_patient, $id_dentist);
        $documentsFormatted = array_map(function($d) {
            return [
                'id_document'   => (int) $d['id_document'],
                'file_name'     => $d['file_name'],      // "xxxx.pdf"
                'file_type'     => $d['file_type'],      // MIME type
                'document_type' => $d['document_type'],  // "radio" "analyse" etc
                'file_path'     => $d['file_path'],      // for download
                'notes'         => $d['notes'],           // note written by dentist
                'upload_date'   => $d['upload_date'],
            ];
        }, $documents);
 
        return [
            'code' => 200,
            'body' => [
                'patient' => [
                    'id_patient' => (int) $patient['id_patient'],
                    'full_name'  => $patient['full_name'],
                    'phone'      => $patient['phone'],
                    'email'      => $patient['email'],
                    'address'    => $patient['address'],
                    'birth_date' => $patient['birth_date'],
                    'age'        => $age,
                    'gender'     => $patient['gender']
                ],
                // Anamnesis not in your DB 
                'anamnesis' => [
                    'medical_conditions' => $patient['medical_conditions'] ?? 'None',
                    'medications'        => $patient['medications']        ?? 'None',
                    'allergies'          => $patient['allergies']          ?? 'No known allergies',
                    'surgical_history'   => $patient['surgical_history']   ?? 'None',
                ],
                'my_visits' => $myVisitsFormatted,
                'documents' => $documentsFormatted,
            ]
        ];
    }

    // getAllVisits()
    // Route: GET /api/dentist/patients/:id/visits/all
    // Called when: dentist clicks "All Visits" tab
    // Shows ALL dentists visits for this patient
    public function getAllVisits($id_patient, $id_dentist) {
 
        // Security check — patient must belong to this dentist
        $patient = $this->model->getPatientInfo($id_patient, $id_dentist);
        if (!$patient) {
            return ['code' => 404, 'body' => ['message' => 'Patient not found.']];
        }
 
        $visits = $this->model->getAllVisits($id_patient);
 
        $formatted = array_map(function($v) {
            return [
                'id_appointment' => (int) $v['id_appointment'],
                'date'           => $v['appointment_date'],
                'time'           => date('g:i A', strtotime($v['appointment_time'])),
                'treatment'      => $v['service_type'],
                'note'           => $v['reason'],
                'status'         => $v['appointment_status'],
                'dentist_name'   => $v['dentist_name'],   // shows which dentist
                'total_price'    => (float) $v['total_price'],
            ];
        }, $visits);
 
        return [
            'code' => 200,
            'body' => ['visits' => $formatted, 'total' => count($formatted)]
        ];
    
    }

    //getPayments()
    // Route: GET /api/dentist/patients/:id/payments
    // Called when: dentist clicks "Payments" tab
    public function getPayments($id_patient, $id_dentist) {
 
        $patient = $this->model->getPatientInfo($id_patient, $id_dentist);
        if (!$patient) {
            return ['code' => 404, 'body' => ['message' => 'Patient not found.']];
        }
 
        $payments = $this->model->getPayments($id_patient, $id_dentist);
 
        // Map French DB status values to display labels
        $statusMap = [
            'paye'       => 'Paid',
            'en_attente' => 'Unpaid',
            'rembourse'  => 'Refunded',
        ];
 
        $formatted = array_map(function($p) use ($statusMap) {
            return [
                'id_payment'     => (int) $p['id_payment'],
                'id_appointment' => (int) $p['id_appointment'],
                'date'           => $p['appointment_date'],
                'time'           => date('g:i A', strtotime($p['appointment_time'])),
                // "4000 DA"
                'amount'         => number_format((float)$p['amount'], 0) . ' DA',
                'amount_raw'     => (float) $p['amount'],
                // "carte" — matches your design
                'method'         => $p['method_payment'] ?? '/',
                // "Paid" or "Unpaid" — controls red/green color
                'status'         => $statusMap[$p['payment_status']] ?? 'Unpaid',
                'status_raw'     => $p['payment_status'],
                // "Dental Cleaning" — the Related visit column
                'related_visit'  => $p['related_visit'],
                'paid_at'        => $p['paid_at'],
                'invoice_path'   => $p['invoice_path'],
            ];
        }, $payments);
 
        return [
            'code' => 200,
            'body' => ['payments' => $formatted, 'total' => count($formatted)]
        ];
    }

    //uploadDocument()
    // Route: POST /api/dentist/patients/:id/documents
    // Called when: dentist clicks upload icon in Files/Documents section
    // Saves file to server and inserts into your document table.
    // document_type must be: radio, analyse, ordonnance, compte_rendu
    public function uploadDocument($id_patient, $id_dentist, $file, $data) {
 
        // Verify patient
        $patient = $this->model->getPatientInfo($id_patient, $id_dentist);
        if (!$patient) {
            return ['code' => 404, 'body' => ['message' => 'Patient not found.']];
        }
 
        if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['code' => 400, 'body' => ['message' => 'File upload failed.']];
        }
 
        // Validate file type
        $allowed = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($file['type'], $allowed)) {
            return ['code' => 400, 'body' => ['message' => 'Only PDF and image files allowed.']];
        }
 
        // Max 10MB
        if ($file['size'] > 10 * 1024 * 1024) {
            return ['code' => 400, 'body' => ['message' => 'File must be under 10MB.']];
        }
 
        // Validate document_type matches your DB enum
        $validTypes = ['radio', 'analyse', 'ordonnance', 'compte_rendu'];
        $docType    = $data['document_type'] ?? 'compte_rendu';
        if (!in_array($docType, $validTypes)) {
            $docType = 'compte_rendu'; // default
        }
 
        // Save file to server
        $uploadDir = __DIR__ . '/../../uploads/documents/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
 
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename  = uniqid('doc_', true) . '.' . $extension;
        $fullPath  = $uploadDir . $filename;
 
        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            return ['code' => 500, 'body' => ['message' => 'Failed to save file.']];
        }
 
        $filePath = 'uploads/documents/' . $filename;
 
        $id_document = $this->model->uploadDocument(
            $id_patient,
            $id_dentist,
            $file['name'],
            $file['type'],
            $docType,
            $filePath,
            $data['notes']          ?? null,
            $data['id_appointment'] ?? null
        );
 
        return [
            'code' => 201,
            'body' => [
                'message'       => 'Document uploaded successfully.',
                'id_document'   => (int) $id_document,
                'file_name'     => $file['name'],
                'file_path'     => $filePath,
                'document_type' => $docType,
            ]
        ];
    }

    
    // updatePrice()
    // Route: PUT /api/dentist/patients/:id/price
    // Called when: dentist types price and clicks Save button
    // id_appointment comes in the request body
    // because the price is per appointment not per patient
    public function updatePrice($id_patient, $id_dentist, $data) {
 
        $id_appointment = $data['id_appointment'] ?? null;
        $price          = $data['price']          ?? null;
 
        if (!$id_appointment || !is_numeric($id_appointment)) {
            return ['code' => 400, 'body' => ['message' => 'Appointment ID is required.']];
        }
        if ($price === null || !is_numeric($price) || (float)$price < 0) {
            return ['code' => 400, 'body' => ['message' => 'Valid price is required.']];
        }
 
        $updated = $this->model->updateAppointmentPrice(
            $id_appointment,
            $id_dentist,
            (float) $price
        );
 
        if (!$updated) {
            return ['code' => 404, 'body' => ['message' => 'Appointment not found.']];
        }
 
        return [
            'code' => 200,
            'body' => ['message' => 'Price saved.', 'price' => (float) $price]
        ];
    }

    public function updateAnamnesis($id_patient, $id_dentist, $data) {

        $patient = $this->model->getPatientInfo($id_patient, $id_dentist);
        if (!$patient) {
            return ['code' => 404, 'body' => ['message' => 'Patient not found.']];
        }

        $this->model->updateAnamnesis($id_patient, $id_dentist, $data);

        return ['code' => 200, 'body' => ['message' => 'Anamnesis updated successfully.']];
    }


}
