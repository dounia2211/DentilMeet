<?php

require_once __DIR__ . '/../models/notificationModel.php';

class notificationService{
    private $notificationModel;
    public function __construct($pdo){
        $this->notificationModel = new notificationModel($pdo);
    }

    //getAll
    // Called when: patient opens the notification panel (bell icon)
    // Route: GET /api/notifications
    public function getAll($id_patient){
        $groups = $this-> notificationModel-> getAllByPatient($id_patient);
        return [
            'code' => 200,
            'body' => [
                'today'        => $this->formatGroup($groups['today']),
                'this_week'    => $this->formatGroup($groups['this_week']),
                'earlier'      => $this->formatGroup($groups['earlier']),
                'unread_count' => $this->notificationModel->getUnreadCount($id_patient)
            ]
    
        ];
    }

    //private helper: formatGroup
    // Adds a "time_ago" string to each notification.
    // "just now", "2 min ago", "30 min ago", "2 hour ago"
    private function formatGroup($notification) {
        $result = [];
        $now = time();// current Unix timestamp

        foreach($notification as $n) {
            $createdAt = strtotime($n['created_at']);
            // strtotime() converts "2026-04-08 19:30:00" to a Unix timestamp
            $diff= $now - $createdAt; //doff in sec

            if ($diff <60){
                $timeAgo ='just now';
            } elseif ($diff < 3600){
                $minutes = floor($diff/60);
                $timeAgo = $minutes . 'min ago';
            }elseif ($diff < 86400) {
                $hours = floor($diff/ 3600);
                $timeAgo = $hours .' hour ago';
            } else {
                $days = floor($diff /86400);
                $timeAgo = $days .' day ago';
            }

            $result[] = [
                'id_notification' => (int) $n['id_notification'],
                'message'         => $n['message'],
                'type'            => $n['type'],
                'is_read'         => (bool) $n['is_read'],
                'created_at'      => $n['created_at'],
                'time_ago'        => $timeAgo,
            ];
        }
 
        return $result;
    }

    //getUnreadCount
    // Called when: any page loads — to show/hide the red dot on bell
    // Route: GET /api/notifications/unread-count
    public function getUnreadCount($id_patient) {
        $count = $this->notificationModel->getUnreadCount($id_patient);
        return [
            'code' => 200,
            'body' => [
                'unread_count' => $count,
                'has_unread'   => $count > 0 // true or false
            ]
        ];
    }

    //markAsRead
    // Called when: patient clicks on a notification
    // Route: PUT /api/notifications/:id/read
    public function markAsRead($id_patient, $id_notification){
        $updated = $this-> notificationModel-> markAsRead($id_notification, $id_patient);

        if(!$updated){
            return [
                'code' => 404,
                'body' => ['message' => 'Notification not found.']
            ];
        }

        return [
            'code' => 200,
            'body' => ['message' => 'Notification marked as read.']
        ];
    }

    //markAllAsRead
    // Called when: patient clicks "Clear all" button
    // Route: PUT /api/notifications/read-all
    public function markAllAsRead($id_patient) {
        $count = $this->notificationModel->markAllAsRead($id_patient);
        return [
            'code' => 200,
            'body' => [
                'message' => 'All notifications marked as read.',
                'updated' => $count
            ]
        ];
    }

    //delete
    // Called when: patient clicks the X button on a notification
    // Route: DELETE /api/notifications/:id
    public function delete($id_patient, $id_notification) {
 
        $deleted = $this->notificationModel->delete($id_notification, $id_patient);
 
        if (!$deleted) {
            return [
                'code' => 404,
                'body' => ['message' => 'Notification not found.']
            ];
        }
 
        return [
            'code' => 200,
            'body' => ['message' => 'Notification deleted.']
        ];
    }

    //createBookingNotification
    //Called by: AppointmentService::create() after successful booking
    public function createBookingNotification($id_patient, $dentist_name, $date, $time,$id_dentist){
        // Format date from "2026-04-06" to "April 6"
        // strtotime() converts date string to timestamp
        // date('F j') formats as "April 6"
        $formattedDate = date('F j', strtotime($date));
        $formattedTime = date('g:i A' , strtotime($time));

        $message = "Your appointment with {$dentist_name} is confirmed for 
        {$formattedDate} at {$formattedTime}";

        $this->notificationModel->create(
            $id_patient,
            'confirmation',
            $message,
            $id_dentist
        );
  
    }

    //createCancellationNotification
    // Called by: AppointmentService::cancel()
    public function createCancellationNotification($id_patient, $date, $id_dentist = null) {
 
        $formattedDate = date('F j', strtotime($date));
        $message       = "Your appointment scheduled for {$formattedDate} has been cancelled";
 
        $this->notificationModel->create(
            $id_patient,
            'annulation',
            $message,
            $id_dentist
        );
    }

    //createRescheduleNotification
    // Called by: future reschedule feature
    public function createRescheduleNotification($id_patient, $newDate, $newTime, $id_dentist = null){
       $formattedDate = date('F j', strtotime($newDate));
        $formattedTime = date('g:i A', strtotime($newTime));
        $message       = "Your appointment has been moved to {$formattedDate} at {$formattedTime}";
 
        $this->notificationModel->create(
            $id_patient,
            'reschedule',
            $message,
            $id_dentist
        );
     
    }

}