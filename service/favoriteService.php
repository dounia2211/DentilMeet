<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/favoriteModel.php';

class FavoriteService {

    private $favoriteModel;

    public function __construct($pdo) {
        $this->favoriteModel = new FavoriteModel($pdo);
    }

    // ── add() ─────────────────────────────────────────────────────────
    // POST /api/favorites/add
    // Body: { "id_dentist": 3 }
    public function add(int $patientId, array $data): array {
        $dentistId = (int)($data['id_dentist'] ?? 0);

        if ($dentistId <= 0) {
            return ['code' => 400, 'body' => ['message' => 'id_dentist is required.']];
        }

        if ($this->favoriteModel->exists($patientId, $dentistId)) {
            return ['code' => 409, 'body' => ['message' => 'Already in favorites.']];
        }

        $this->favoriteModel->add($patientId, $dentistId);

        return ['code' => 201, 'body' => ['message' => 'Dentist added to favorites.']];
    }

    // ── remove() ──────────────────────────────────────────────────────
    // DELETE /api/favorites/remove
    // Body: { "id_dentist": 3 }
    public function remove(int $patientId, array $data): array {
        $dentistId = (int)($data['id_dentist'] ?? 0);

        if ($dentistId <= 0) {
            return ['code' => 400, 'body' => ['message' => 'id_dentist is required.']];
        }

        if (!$this->favoriteModel->exists($patientId, $dentistId)) {
            return ['code' => 404, 'body' => ['message' => 'Favorite not found.']];
        }

        $this->favoriteModel->remove($patientId, $dentistId);

        return ['code' => 200, 'body' => ['message' => 'Dentist removed from favorites.']];
    }

    // ── getAll() ──────────────────────────────────────────────────────
    // GET /api/favorites
    public function getAll(int $patientId): array {
        $favorites = $this->favoriteModel->getAll($patientId);

        return [
            'code' => 200,
            'body' => [
                'favorites' => $favorites,
                'count'     => count($favorites)
            ]
        ];
    }
}