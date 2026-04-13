<?php

require_once __DIR__ . '/../service/dentistauthService.php';

class dentistauthController {
    private $dentistauthSevice;
    public function __construct($pdo){
        $this->dentistauthService = new dentistauthService($pdo);
    } 

    //signup()
    // Reads from $_POST (text fields) and $_FILES (documents).
    // NOT from php://input — because this is multipart/form-data.
    public function signup() {
 
        // $_POST contains all text fields from the form
        // $_FILES contains the uploaded documents
        $result = $this->dentistauthService->signup($_POST, $_FILES);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }

    //login()
    // Login has no file uploads so we use json_decode normally
    public function login() {
 
        $data = json_decode(file_get_contents('php://input'), true);
 
        if (!$data) {
            http_response_code(400);
            echo json_encode(['message' => 'Invalid request body.']);
            return;
        }
 
        $result = $this->dentistauthService->login($data);
        http_response_code($result['code']);
        echo json_encode($result['body']);
    }
}