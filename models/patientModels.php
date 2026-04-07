<?php
declare(strict_types=1);

class PatientModel {
 
    // $pdo is the database connection passed in from index.php
    private $pdo;
 
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
 
    // ── findByEmail() ────────────────────────────────────────────────
    // Searches for a patient by email address.
    // Returns the patient row (array) if found, or FALSE if not found.
    // Used to check if an email is already registered before inserting.
    public function findByEmail($email) {
        $stmt = $this->pdo->prepare("
            SELECT id_patient,  email, password, is_suspended
            FROM patient
            WHERE email = ?
            LIMIT 1
        ");
        $stmt->execute([$email]);
        return $stmt->fetch(); // returns array or FALSE
    }
 
    // ── create() ─────────────────────────────────────────────────────
    // Inserts a new patient into the database.
    // Returns the auto-generated id_patient of the newly created row.
    // Note: created_at and is_suspended are NOT passed here —
    //       MySQL sets them automatically via DEFAULT NOW() and DEFAULT FALSE.
    public function create($full_name, $email, $passwordHash, $phone) {
        $stmt = $this->pdo->prepare("
            INSERT INTO patient (full_name, email, password, phone)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$full_name, $email, $passwordHash, $phone]);
 
        // lastInsertId() returns the id_patient of the row we just inserted
        return $this->pdo->lastInsertId();
    }
}