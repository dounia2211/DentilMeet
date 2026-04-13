<?php

class dentistauthModel {
    private $pdo;
    public function __construct($pdo){
        $this->pdo= $pdo;
    }

    //emailExists()
    // Used by: signup step 1 validation
    // Checks if this email is already registered as a dentist
    public function emailExists($email){
        $stmt = $this->pdo->prepare("
         SELECT COUNT(*) as total 
         FROM dentist
         WHERE email = ?
        ");

        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return (int) $row['total'] > 0;
    }

    //createClinic
    // Used by: signup step 3
    // Creates a new clinic row and returns its ID.
    // This ID is then linked to the dentist in createDentist()
    public function createClinic ($name, $address){
        $stmt = $this->pdo->prepare("
         INSERT INTO clinic (name, address)
         VALUES (?, ?)
        ");

        $stmt->execute([$name, $address]);
        return $this->pdo-> lastInsertId();
    }

    //createDentist
    // Creates the dentist row with all data from all 4 steps.
    public function createDentist (
        $full_name,
        $email,
        $password_hashed,
        $phone,
        $speciality,
        $license_number,
        $id_clinic,
        $diploma_document
    ){
        $stmt = $this->pdo->prepare("
         INSERT INTO dentist
             (full_name, email, password, phone, speciality, license_number, id_clinic,
             diploma_document, verification_status, created_at)
            VALUES (?,?,?,?,?,?,?,?, 'en_attente' , NOW())
        ");

        $stmt-> execute([
            $full_name,
            $email,
            $password_hashed,
            $phone,
            $speciality,
            $license_number,
            $id_clinic,
            $diploma_document
        ]);
        return $this->pdo->lastInsertId();
    }

    //findByEmail
    // Used by: login
    // Finds a dentist by their email.
    public function findByEmail($email) {
        $stmt = $this->pdo->prepare("
            SELECT
                id_dentist,
                full_name,
                email,
                password,
                phone,
                speciality,
                verification_status,
                id_clinic
            FROM dentist
            WHERE email = ?
            LIMIT 1
        ");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
}