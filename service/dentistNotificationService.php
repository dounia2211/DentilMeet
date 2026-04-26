<?php
declare(strict_types=1);
require_once __DIR__ . '/../models/dentistNotificationModel.php';

class DentistNotificationService {
    private $m;
    public function __construct($pdo) {
        $this->m = new DentistNotificationModel($pdo);
    }

    public function getAll(int $dentistId): array {
        $groups = $this->m->getAllByDentist($dentistId);
        return ['code' => 200, 'body' => [
            'today'        => $this->format($groups['today']),
            'this_week'    => $this->format($groups['this_week']),
            'earlier'      => $this->format($groups['earlier']),
            'unread_count' => $this->m->getUnreadCount($dentistId),
        ]];
    }

    public function getUnreadCount(int $dentistId): array {
        $count = $this->m->getUnreadCount($dentistId);
        return ['code' => 200, 'body' => [
            'unread_count' => $count,
            'has_unread'   => $count > 0,
        ]];
    }

    public function markAsRead(int $dentistId, int $notifId): array {
        $ok = $this->m->markAsRead($notifId, $dentistId);
        if (!$ok) return ['code' => 404, 'body' => ['message' => 'Notification not found.']];
        return ['code' => 200, 'body' => ['message' => 'Marked as read.']];
    }

    public function markAllAsRead(int $dentistId): array {
        $count = $this->m->markAllAsRead($dentistId);
        return ['code' => 200, 'body' => ['message' => 'All marked as read.', 'updated' => $count]];
    }

    public function delete(int $dentistId, int $notifId): array {
        $ok = $this->m->delete($notifId, $dentistId);
        if (!$ok) return ['code' => 404, 'body' => ['message' => 'Notification not found.']];
        return ['code' => 200, 'body' => ['message' => 'Notification deleted.']];
    }

    private function format(array $notifications): array {
        $now = time();
        return array_map(function($n) use ($now) {
            $diff = $now - strtotime($n['created_at']);
            if ($diff < 60)        $timeAgo = 'just now';
            elseif ($diff < 3600)  $timeAgo = floor($diff/60) . ' min ago';
            elseif ($diff < 86400) $timeAgo = floor($diff/3600) . ' hour ago';
            else                   $timeAgo = floor($diff/86400) . ' day ago';
            return [
                'id_notification' => (int) $n['id_notification'],
                'message'         => $n['message'],
                'type'            => $n['type'],
                'is_read'         => (bool) $n['is_read'],
                'created_at'      => $n['created_at'],
                'time_ago'        => $timeAgo,
            ];
        }, $notifications);
    }
}