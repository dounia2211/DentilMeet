<?php

require_once __DIR__ . '/../models/patientModels.php';
require_once __DIR__ . '/../utils/tokenUtil.php';

class AuthService {
    private $patientModel;
 
    public function __construct($pdo) { // runs automatically when you write new AuthService($pdo).
        $this->patientModel = new PatientModel($pdo);
    }
 
    // ════════════════════════════════════════════════════════════════
    //  SIGNUP
    // ════════════════════════════════════════════════════════════════
    // Steps:
    //   1. Check if username is already taken
    //   2. Check if email is already registered
    //   3. Hash the password (never store plain text!)
    //   4. Insert the new patient into the DB
    //   5. Generate a JWT token
    //   6. Return 201 Created with the token and patient info
    public function signup($data) {
 
        $full_name = trim($data['full_name']); 
        $email    = strtolower(trim($data['email'])); // normalize email to lowercase
        $password = $data['password'];
        $phone    = !empty(trim($data['phone'] ?? '')) ? trim($data['phone']) : null;
        // phone is optional — if empty we store NULL in the DB
 
 
        // ── Step 1: Check if email is already registered ─────────────
        $existingEmail = $this->patientModel->findByEmail($email);
        if ($existingEmail) {
            return [
                'code' => 409,
                'body' => ['message' => "This email alrady taken"]
            ];
        }
 
        // ── Step 2: Hash the password ─────────────────────────────────
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
 
        // ── Step 3: Insert the new patient into the DB ────────────────
        // create() returns the new patient's auto-generated id_patient
        $id_patient = $this->patientModel->create(
            $full_name,
            $email,
            $passwordHash,
            $phone
        );
 
        // If insert failed for some reason (shouldn't happen, but just in case)
        if (!$id_patient) {
            return [
                'code' => 500,
                'body' => ['message' => "Error while creating the account. Please try again."]
            ];
        }
 
        // ── Step 4: Generate JWT token ────────────────────────────────
        // We generate the token right after signup so the patient
        // is automatically logged in — no need to login again after registering.
        
        $token = TokenUtil::generate($id_patient, $email);
 
        // ── Step 5: Return success ────────────────────────────────────
        // We return the token and basic patient info.
        // NEVER return the password or its hash in the response!
        return [
            'code' => 201, // 201 Created = resource successfully created
            'body' => [
                'message' => "Account created successfully.",
                'token'   => $token,
                'patient' => [
                    'id_patient' => (int) $id_patient,
                    'full_name'  => $full_name,
                    'email'      => $email,
                    'phone'      => $phone
                ]
            ]
        ];
    }
}