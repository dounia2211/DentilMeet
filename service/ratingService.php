<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/ratingModel.php';

class RatingService {

    private $ratingModel;

    public function __construct($pdo) {
        $this->ratingModel = new RatingModel($pdo);
    }

    // ── submit() ──────────────────────────────────────────────────────
    // Patient submits a rating for a dentist
    // Rules:
    //   - stars must be between 1 and 5
    //   - comment is optional (max 500 chars)
    //   - a patient can only rate the same dentist once
    public function submit(int $patientId, array $data): array {
        $dentistId = (int)($data['id_dentist'] ?? 0);
        $stars     = (int)($data['stars']      ?? 0);
        $comment   = trim($data['comment']     ?? '');

        // ── Validate ──────────────────────────────────────────────────
        if ($dentistId <= 0) {
            return ['code' => 400, 'body' => ['message' => 'id_dentist is required.']];
        }

        if ($stars < 1 || $stars > 5) {
            return ['code' => 400, 'body' => ['message' => 'stars must be between 1 and 5.']];
        }

        if (!empty($comment) && strlen($comment) > 500) {
            return ['code' => 400, 'body' => ['message' => 'Comment must not exceed 500 characters.']];
        }

        // ── Check duplicate ───────────────────────────────────────────
        if ($this->ratingModel->hasRated($patientId, $dentistId)) {
            return ['code' => 409, 'body' => ['message' => 'You already rated this dentist.']];
        }

        // ── Insert ────────────────────────────────────────────────────
        $id = $this->ratingModel->create(
            $patientId,
            $dentistId,
            $stars,
            $comment === '' ? null : $comment
        );

        return [
            'code' => 201,
            'body' => [
                'message'   => 'Rating submitted successfully.',
                'id_rating' => (int) $id
            ]
        ];
    }

    // ── getForDentist() ───────────────────────────────────────────────
    // Returns all ratings + average for a dentist
    // Public route — no auth required
    public function getForDentist(int $dentistId): array {
        if ($dentistId <= 0) {
            return ['code' => 400, 'body' => ['message' => 'id_dentist is required.']];
        }

        $ratings = $this->ratingModel->getByDentist($dentistId);
        $average = $this->ratingModel->getAverage($dentistId);

        return [
            'code' => 200,
            'body' => [
                'id_dentist' => $dentistId,
                'average'    => (float)  ($average['average'] ?? 0),
                'total'      => (int)    ($average['total']   ?? 0),
                'ratings'    => $ratings
            ]
        ];
    }
}
