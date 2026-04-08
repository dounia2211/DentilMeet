<?php
declare(strict_types=1);
require_once __DIR__ . '/../models/profileModel.php';

class ProfileService {

    private $profileModel;

    public function __construct($pdo) {
        $this->profileModel = new ProfileModel($pdo);
    }

    // ─────────────────────────────────────────────────────────────────
    // GET /api/profile
    // Retourne TOUT ce que la page profil affiche :
    //   - infos patient (gauche)
    //   - anamnesis (haut droite)
    //   - documents (bas droite)
    //   - visit history (tableau bas)
    //   - remaining payment (bas droite)
    // ─────────────────────────────────────────────────────────────────
    public function getProfile(int $patientId): array {

        // 1. Infos de base — si patient inexistant → 404
        $patient = $this->profileModel->getById($patientId);
        if (!$patient) {
            return ['code' => 404, 'body' => ['message' => 'Patient not found.']];
        }

        // 2. Anamnesis (remplie par le dentiste)
        $anamnesis = $this->profileModel->getAnamnesis($patientId);

        // 3. Documents uploadés par le dentiste
        $documents = $this->profileModel->getDocuments($patientId);

        // 4. Historique des visites
        $visitHistory = $this->profileModel->getVisitHistory($patientId);

        // 5. Paiement restant
        $remainingPayment = $this->profileModel->getRemainingPayment($patientId);

        return [
            'code' => 200,
            'body' => [

                // ── Carte gauche (photo + nom + number + email + patient id)
                'id_patient' => $patient['id_patient'],
                'full_name'  => $patient['full_name'],
                'email'      => $patient['email'],
                'phone'      => $patient['phone'],       // affiché comme "Number" dans le design
                'photo'      => $patient['photo'],

                // ── Bloc Anamnesis (haut droite)
                'anamnesis' => [
                    'medical_conditions' => $anamnesis['medical_conditions'],
                    'medications'        => $anamnesis['medications'],
                    'allergies'          => $anamnesis['allergies'],
                    'surgical_history'   => $anamnesis['surgical_history'],
                ],

                // ── Bloc Files/Documents (bas droite)
                // Chaque item : { id_document, file_name, file_path, file_size_kb, uploaded_at }
                'documents' => $documents,

                // ── Visit History (tableau du bas)
                // Chaque item : { id_appointment, appointment_date, treatment, doctor_name,
                //                 payment_amount, payment_status, method_payment }
                'visit_history' => $visitHistory,

                // ── Remaining Payment (bas droite — "3500 DA  Pay Now")
                'remaining_payment' => $remainingPayment,
            ]
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // POST /api/profile/photo
    // Le patient clique sur sa photo pour la changer
    // Reçoit $_FILES['photo'] depuis le frontend (multipart/form-data)
    // ─────────────────────────────────────────────────────────────────
    public function uploadPhoto(int $patientId, array $file): array {

        // Vérifier qu'un fichier a bien été envoyé sans erreur
        if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['code' => 400, 'body' => ['message' => 'No valid file uploaded.']];
        }

        // Vérifier le type MIME (sécurité : pas de .php déguisé en .jpg)
        $allowed  = ['image/jpeg', 'image/png', 'image/webp'];
        $mimeType = mime_content_type($file['tmp_name']);
        if (!in_array($mimeType, $allowed)) {
            return ['code' => 400, 'body' => ['message' => 'Only JPG, PNG, WEBP allowed.']];
        }

        // Construire un nom unique pour éviter les collisions
        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'patient_' . $patientId . '_' . time() . '.' . $ext;
        $dest     = __DIR__ . '/../uploads/photos/' . $filename;

        // Déplacer le fichier vers le dossier uploads/photos/
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return ['code' => 500, 'body' => ['message' => 'Failed to save photo.']];
        }

        // Sauvegarder le chemin en base de données
        $photoPath = '/uploads/photos/' . $filename;
        $this->profileModel->updatePhoto($patientId, $photoPath);

        return [
            'code' => 200,
            'body' => [
                'message' => 'Photo updated successfully.',
                'photo'   => $photoPath,  // le frontend met à jour l'image avec ce chemin
            ]
        ];
    }
}