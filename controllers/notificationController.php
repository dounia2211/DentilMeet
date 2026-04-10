<?php

require_once __DIR__ . '/../service/notificationService.php';

class notificationController{
    private $notificationService;
    public function __construct ($pdo){
        $this->notificationService = new notificationService($pdo);
    }

    // GET /api/notifications
    // Returns all notifications grouped by Today / This Week / Earlier
    public function getAll($patient) {
        $result = $this->notificationService->getAll($patient['id_patient']);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    // GET /api/notifications/unread-count
    // Returns unread count for the bell icon red dot
    public function getUnreadCount($patient) {
        $result = $this->notificationService->getUnreadCount($patient['id_patient']);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    // PUT /api/notifications/:id/read
    // Marks one notification as read
    // $id_notification comes from preg_match in signup.php
    public function markAsRead($patient, $id_notification) {
        $result = $this->notificationService->markAsRead(
            $patient['id_patient'],
            $id_notification
        );
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    // PUT /api/notifications/read-all
    // Marks ALL notifications as read — Clear all button
    public function markAllAsRead($patient) {
        $result = $this->notificationService->markAllAsRead($patient['id_patient']);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    // DELETE /api/notifications/:id
    // Deletes one notification — X button
    public function delete($patient, $id_notification) {
        $result = $this->notificationService->delete(
            $patient['id_patient'],
            $id_notification
        );
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }
}