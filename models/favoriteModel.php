<?php
declare(strict_types=1);

// ⚠️  TABLE MANQUANTE — Ajoutez ce SQL dans phpMyAdmin avant de tester :
//
//  CREATE TABLE `favorite` (
//    `id_favorite`  int(11)  NOT NULL AUTO_INCREMENT,
//    `id_patient`   int(11)  NOT NULL,
//    `id_dentist`   int(11)  NOT NULL,
//    `created_at`   datetime NOT NULL DEFAULT current_timestamp(),
//    PRIMARY KEY (`id_favorite`),
//    UNIQUE KEY `uq_favorite` (`id_patient`,`id_dentist`),
//    FOREIGN KEY (`id_patient`) REFERENCES `patient`(`id_patient`) ON DELETE CASCADE,
//    FOREIGN KEY (`id_dentist`) REFERENCES `dentist`(`id_dentist`) ON DELETE CASCADE
//  );

class FavoriteModel {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // ── add() ─────────────────────────────────────────────────────────
    public function add(int $patientId, int $dentistId): bool {
        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO favorite (id_patient, id_dentist, created_at)
            VALUES (?, ?, NOW())
        ");
        return $stmt->execute([$patientId, $dentistId]);
    }

    // ── remove() ──────────────────────────────────────────────────────
    public function remove(int $patientId, int $dentistId): bool {
        $stmt = $this->pdo->prepare("
            DELETE FROM favorite
            WHERE id_patient = ? AND id_dentist = ?
        ");
        return $stmt->execute([$patientId, $dentistId]);
    }

    // ── exists() ──────────────────────────────────────────────────────
    public function exists(int $patientId, int $dentistId): bool {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM favorite
            WHERE id_patient = ? AND id_dentist = ?
        ");
        $stmt->execute([$patientId, $dentistId]);
        return (bool) $stmt->fetchColumn();
    }

    // ── getAll() ──────────────────────────────────────────────────────
    // JOIN avec `dentist` (votre vraie table SQL)
    // Colonnes utilisées : full_name, speciality, photo, year_of_experience
    public function getAll(int $patientId): array {
        $stmt = $this->pdo->prepare("
            SELECT
                f.id_dentist,
                f.created_at,
                d.full_name          AS dentist_name,
                d.speciality         AS dentist_speciality,
                d.photo              AS dentist_photo,
                d.year_of_experience AS dentist_experience
            FROM favorite f
            JOIN dentist d ON f.id_dentist = d.id_dentist
            WHERE f.id_patient = ?
            ORDER BY f.created_at DESC
        ");
        $stmt->execute([$patientId]);
        return $stmt->fetchAll();
    }
}