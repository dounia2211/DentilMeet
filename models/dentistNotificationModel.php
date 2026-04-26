<?php
declare(strict_types=1);

class DentistNotificationModel {
    private $pdo;
    public function __construct($pdo) { $this->pdo = $pdo; }

    // GET /api/dentist/notifications
    // Groupées : today / this_week / earlier
    public function getAllByDentist(int $dentistId): array {
        $today = $this->pdo->prepare("
            SELECT id_notification, message, type, is_read, created_at
            FROM notification
            WHERE id_dentist = ? AND DATE(created_at) = CURDATE()
            ORDER BY created_at DESC
        ");
        $today->execute([$dentistId]);

        $week = $this->pdo->prepare("
            SELECT id_notification, message, type, is_read, created_at
            FROM notification
            WHERE id_dentist = ?
              AND DATE(created_at) != CURDATE()
              AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            ORDER BY created_at DESC
        ");
        $week->execute([$dentistId]);

        $earlier = $this->pdo->prepare("
            SELECT id_notification, message, type, is_read, created_at
            FROM notification
            WHERE id_dentist = ?
              AND created_at < DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            ORDER BY created_at DESC
        ");
        $earlier->execute([$dentistId]);

        return [
            'today'     => $today->fetchAll(),
            'this_week' => $week->fetchAll(),
            'earlier'   => $earlier->fetchAll(),
        ];
    }

    public function getUnreadCount(int $dentistId): int {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM notification
            WHERE id_dentist = ? AND is_read = 0
        ");
        $stmt->execute([$dentistId]);
        return (int) $stmt->fetchColumn();
    }

    public function markAsRead(int $notifId, int $dentistId): bool {
        $stmt = $this->pdo->prepare("
            UPDATE notification SET is_read = 1
            WHERE id_notification = ? AND id_dentist = ?
        ");
        $stmt->execute([$notifId, $dentistId]);
        return $stmt->rowCount() > 0;
    }

    public function markAllAsRead(int $dentistId): int {
        $stmt = $this->pdo->prepare("
            UPDATE notification SET is_read = 1
            WHERE id_dentist = ? AND is_read = 0
        ");
        $stmt->execute([$dentistId]);
        return $stmt->rowCount();
    }

    public function delete(int $notifId, int $dentistId): bool {
        $stmt = $this->pdo->prepare("
            DELETE FROM notification
            WHERE id_notification = ? AND id_dentist = ?
        ");
        $stmt->execute([$notifId, $dentistId]);
        return $stmt->rowCount() > 0;
    }

    // Appelé automatiquement par DentistDashboardService::acceptRequest()
    // → "New Appointment Request" — Sara requested an appointment for April 8 at 2:00 pm
    public function createAppointmentRequestNotif(int $dentistId, string $patientName, string $date, string $time): void {
        $formattedDate = date('F j', strtotime($date));
        $formattedTime = date('g:i A', strtotime($time));
        $message = "{$patientName} requested an appointment for {$formattedDate} at {$formattedTime}";
        $this->create($dentistId, 'rappel', $message);
    }

    // → "Appointment Confirmed" — You confirmed an appointment with Ali for April 7 at 11:00 AM
    public function createConfirmedNotif(int $dentistId, string $patientName, string $date, string $time): void {
        $formattedDate = date('F j', strtotime($date));
        $formattedTime = date('g:i A', strtotime($time));
        $message = "You confirmed an appointment with {$patientName} for {$formattedDate} at {$formattedTime}";
        $this->create($dentistId, 'confirmation', $message);
    }

    // → "Appointment Cancelled" — Patient Sara cancelled her appointment
    public function createCancelledNotif(int $dentistId, string $patientName): void {
        $message = "Patient {$patientName} cancelled their appointment";
        $this->create($dentistId, 'annulation', $message);
    }

    // → "New Patient Message" — You received a new message from a patient
    public function createNewMessageNotif(int $dentistId, string $patientName): void {
        $message = "You received a new message from {$patientName}";
        $this->create($dentistId, 'alerte', $message);
    }

    private function create(int $dentistId, string $type, string $message): void {
        $stmt = $this->pdo->prepare("
            INSERT INTO notification (id_dentist, type, message, is_read, created_at)
            VALUES (?, ?, ?, 0, NOW())
        ");
        $stmt->execute([$dentistId, $type, $message]);
    }
}