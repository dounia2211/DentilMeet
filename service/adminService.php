<?php

require_once __DIR__ . '/../models/AdminModel.php';
require_once __DIR__ . '/../utils/TokenUtil.php';

class adminService {
    private $adminModel;
    public function __construct($pdo){
        $this->adminModel= new adminModel($pdo);
    }

    // login()
    // Called when: admin enters email + password and clicks Login
    // Route: POST /api/admin/auth/login
    public function login($data){
        $email = trim($data['email'] ?? '');
        $password= $data['password'] ?? '';

        //basic validation
        if(empty($email) || empty($password)){
            return [
                'code' => 400,
                'body' => ['message' => 'Email and password are required.']
            ];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'code' => 400,
                'body' => ['message' => 'Invalid email format.']
            ];
        }

        //find admin by email
        $admin = $this->adminModel->findByEmail($email);

         if (!$admin) {
            return [
                'code' => 401,
                'body' => ['message' => 'Incorrect email or password.']
            ];
        }

        // Verify password against bcrypt hash
        if (!password_verify($password, $admin['password'])) {
            return [
                'code' => 401,
                'body' => ['message' => 'Incorrect email or password.']
            ];
        }

        // Generate JWT token
        // Contains id_admin, email, role = 'admin', and permissions
        $token = TokenUtil::generate([
            'id_admin'            => $admin['id_admin'],
            'email'               => $admin['email'],
            'role'                => 'admin',
            // Include permissions in token so frontend can show/hide features
            'can_validate_dentist'=> (bool) $admin['can_validate_dentist'],
            'can_suspend'         => (bool) $admin['can_suspend'],
        ]);

        return [
            'code' => 200,
            'body' => [
                'message' => 'Login successful.',
                'token'   => $token,
                'admin'   => [
                    'id_admin'             => (int) $admin['id_admin'],
                    'full_name'            => $admin['nom'] . ' ' . $admin['prenom'],
                    'nom'                  => $admin['nom'],
                    'prenom'               => $admin['prenom'],
                    'email'                => $admin['email'],
                    // Permissions — used to show/hide features in admin sidebar
                    'can_validate_dentist' => (bool) $admin['can_validate_dentist'],
                    'can_suspend'          => (bool) $admin['can_suspend'],
                ]
            ]
        ];
 
    }

}