<?php

class notificationModel{
    private $pdo;
    public function __construct($pdo) {
        $this->pdo= $pdo;
    }

    //getall returns ALL notifications for the logged-in patient
    // grouped into today, this_week, earlier.
    //used by: GET /api/notifications
    public function getAllByPatient ($id_patient) {
        $today= $this->pdo->prepare("
            SELECT
            id_notification,
            message,
            type,
            is_read,
            created_at
            FROM notification
            WHERE id_patient = ?
            AND DATE(created_at) = CURDATE()
            ORDER BY created_at DESC
        ");
        $today->execute([$id_patient]);
        $todayRows = $today-> fetchAll();

        $week = $this->pdo->prepare("
           SELECT 
           id_notification,
           message,
           type,
           is_read,
           created_at
           FROM notification
           WHERE id_patient = ?
           AND DATE(created_at) != CURDATE()
           AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
           ORDER BY created_at DESC
        ");
        $week-> execute([$id_patient]);
        $weekRows = $week-> fetchAll();

        $earlier = $this->pdo->prepare("
            SELECT
             id_notification,
             message,
             type,
             is_read,
             created_at
            FROM notification
            WHERE id_patient = ?
            AND   created_at < DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            ORDER BY created_at DESC
        ");
        $earlier->execute([$id_patient]);
        $earlierRows = $earlier->fetchAll();

        return [
            'today' =>$todayRows,
            'this_week' => $weekRows,
            'earlier' => $earlierRows
        ];
    }

    // getunreadcount Used by: bell icon red dot on every page
    //Returns how many unread notifications the patient has.
    public function getUnreadCount($id_patient){
       $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as total
            FROM notification
            WHERE id_patient = ?
            AND   is_read    = 0
        ");
        $stmt->execute([$id_patient]);
        $row = $stmt->fetch();
        return $row ? (int) $row['total'] : 0; 
    }

    //markasread
    // Used by: clicking X on a single notification
    // Changes is_read from 0 to 1 for one notification.
    public function markAsRead($id_notification, $id_patient) {
        $stmt = $this->pdo->prepare("
            UPDATE notification
            SET    is_read = 1
            WHERE  id_notification = ?
            AND    id_patient      = ?
        ");
        $stmt->execute([$id_notification, $id_patient]);
        return $stmt->rowCount();
    }

    //markallsread
    // Used by: "Clear all" button
    public function markAllAsRead($id_patient) {
        $stmt = $this->pdo->prepare("
            UPDATE notification
            SET    is_read = 1
            WHERE  id_patient = ?
            AND    is_read    = 0
        ");
        $stmt->execute([$id_patient]);
        return $stmt->rowCount();
    }

    //create
    // Used by: AppointmentService — called automatically after events
    public function create($id_patient, $type, $message, $id_dentist = null) {
        $stmt = $this->pdo->prepare("
            INSERT INTO notification
                (id_patient, id_dentist, type, message, is_read, created_at)
            VALUES
                (?, ?, ?, ?, 0, NOW())
        ");
        $stmt->execute([$id_patient, $id_dentist, $type, $message]);
        return $this->pdo->lastInsertId();
    }

    //delete
    // Used by: X button on a notification (dismiss/delete)
    // Different from markAsRead — this removes it from the list.
   public function delete($id_notification, $id_patient) {
        $stmt = $this->pdo->prepare("
            DELETE FROM notification
            WHERE id_notification = ?
            AND   id_patient      = ?
        ");
        $stmt->execute([$id_notification, $id_patient]);
        return $stmt->rowCount();
    }

}