<?php

class adminModel {
    private $pdo;
    public function __construct($pdo){
        $this->pdo = $pdo;
    }

    // findByEmail()
    // Used by: admin login
    // Returns: one admin row or FALSE
    public function findByEmail($email) {
        $stmt = $this->pdo->prepare("
            SELECT
                id_admin,
                nom,
                prenom,
                email,
                password,
                can_validate_dentist,
                can_suspend
            FROM admin
            WHERE email = ?
            LIMIT 1
        ");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }


    // create()
    // Used by: inserting the 6 admins into the DB
    // This is called ONCE manually or via a setup script.
    // NOT exposed as a public API route.
    // Returns: new id_admin
    public function create($nom, $prenom, $email, $password_hashed, $can_validate, $can_suspend) {
        $stmt = $this->pdo->prepare("
            INSERT INTO admin
                (nom, prenom, email, password, can_validate_dentist, can_suspend, created_at)
            VALUES
                (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$nom, $prenom, $email, $password_hashed, $can_validate, $can_suspend]);
        return $this->pdo->lastInsertId();
    }
}