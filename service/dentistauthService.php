<?php

require_once __DIR__ . '/../models/dentistauthModel.php';
require_once __DIR__ .'/../utils/tokenUtil.php';

class dentistauthService {
    private $dentistauthModel;
    public function __construct($pdo){
        $this->dentistauthModel = new dentistauthModel($pdo);
    }

    // signup()
    // Called when: dentist clicks "Sign up" button on step 4
    // Route: POST /api/dentist/auth/signup
    public function signup($data, $files){

        // ── Step 1: Extract and clean inputs


        // Step 1 data personal information
        $full_name  = trim($data['full_name']   ?? '');
        $phone  = trim($data['phone']  ?? '');
        $email  = trim($data['email']   ?? '');
        $speciality = trim($data['speciality']   ?? '');

        // step 2 data account information
        $password = $data['password'] ?? '';
        $confirm_password = $data['confirm_password'] ?? '';

        //step 3 clinic information
        $clinic_name    = trim($data['clinic_name']    ?? '');
        $clinic_address = trim($data['clinic_address'] ?? '');
        $license_number = trim($data['license_number'] ?? '');
        $working_hours  = trim($data['working_hours']  ?? '');

        // Step 4 data  documents are files (handled separately below)

        // step 2: Validates
        $errors = [];
 
        // Personal info validation
        if (empty($full_name))
            $errors[] = 'Full name is required.';
        elseif (strlen($full_name) < 3)
            $errors[] = 'Full name must be at least 3 characters.';
 
        if (empty($phone))
            $errors[] = 'Phone is required.';
        elseif (!preg_match('/^\+?[\d\s\-]{7,20}$/', $phone))
            $errors[] = 'Phone number is invalid.';
 
        if (empty($email))
            $errors[] = 'Email is required.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
            $errors[] = 'Email format is invalid.';
 
        if (empty($speciality))
            $errors[] = 'Specialization is required.';

        //acount info validation
        if (empty($password))
            $errors[] = 'Password is required.';
        elseif (strlen($password) < 8)
            $errors[] = 'Password must be at least 8 characters.';
 
        if ($password !== $confirm_password)
            $errors[] = 'Passwords do not match.';

        // Clinic info validation
        if (empty($clinic_name))
            $errors[] = 'Clinic name is required.';
        if (empty($clinic_address))
            $errors[] = 'Clinic address is required.';
        if (empty($license_number))
            $errors[] = 'License number is required.';
 

        if (!empty($errors)) {
            return ['code' => 400, 'body' => ['errors' => $errors]];
        }

        // step 3: check email not already taken
        if ($this->dentistauthModel->emailExists($email)) {
            return [
                'code' => 409,
                'body' => ['message' => 'This email is already registered.']
            ];
        }

        // step 4: hash the password
        $password_hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        // step 5 handle uploaded documents
        $diploma_path = null;
        if(!empty($files['diploma_document']['name'])){
            $diploma_path = $this-> saveFile($files['diploma_document'], 'diplomas');
        } 

        //step 6: create clinic
        $id_clinic = $this->dentistauthModel->createClinic($clinic_name, $clinic_address);

        // step 7: create dentist
        $id_dentist = $this->dentistauthModel->createDentist(
            $full_name,
            $email,
            $password_hashed,
            $phone,
            $speciality,
            $license_number,
            $id_clinic,
            $diploma_path
        );

        if (!$id_dentist) {
            return [
                'code' => 500,
                'body' => ['message' => 'Registration failed. Please try again.']
            ];
        }

        return [
            'code' => 201,
            'body' => [
                'message' => 'Registration successful. Your account is pending verification by an administrator.
                 You will be notified once approved.',
                'dentist' => [
                    'id_dentist' => (int) $id_dentist,
                    'full_name'  => $full_name,
                    'email'      => $email,
                    'status'     => 'en_attente'
                ]
            ]
        ];

    }

    //login()
    // Called when: dentist clicks Login button
    // Route: POST /api/dentist/auth/login
    public function login($data){
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '' ;

        // Validate 
        if (empty($email) || empty($password)) {
            return [
                'code' => 400,
                'body' => ['message' => 'email and password are required.']
            ];
        }

        // Find dentist by email 
        $dentist = $this->dentistauthModel->findByEmail($email);
 
        // If not found — do not say "email not found"
        // Say "incorrect credentials" to not reveal which part is wrong.
        // This is a security best practice.
        if (!$dentist) {
            return [
                'code' => 401,
                'body' => ['message' => 'Incorrect email or password.']
            ];
        }

        //verify password
        if (!password_verify($password, $dentist['password'])) {
            return [
                'code' => 401,
                'body' => ['message' => 'Incorrect email or password.']
            ];
        }

        //check verification status
        if ($dentist['verification_status'] === 'en_attente'){
            return [
                'code' => 403,
                'body' => [ 'message' => 'Your account is pending verification. Please wait 
                fpr admon approval.']
            ];
        }

        if ($dentist['verification_status'] === 'annule') {
            return [
                'code' => 403,
                'body' => ['message' => 'Your account application was rejected. Please contact support.']
            ];
        }

        //generate JWT token
        // The token contains id_dentist and email.
        // The dentist sends this token in every protected request.
        $token = TokenUtil::generate([
            'id_dentist' => $dentist['id_dentist'],
            'email'      => $dentist['email'],
            'role'       => 'dentist'  // identifies this as a dentist token
        ]);

        return [
            'code' => 200,
            'body' => [
                'message' => 'Login successful.',
                'token'   => $token,
                'dentist' => [
                    'id_dentist'  => (int) $dentist['id_dentist'],
                    'full_name'   => $dentist['full_name'],
                    'email'       => $dentist['email'],
                    'speciality'  => $dentist['speciality'],
                    'id_clinic'   => $dentist['id_clinic'],
                    'status'      => $dentist['verification_status']
                ]
            ]
        ];
    }

    // ── PRIVATE HELPER — saveFile() 
    // Saves an uploaded file to the server and returns the file path.
    // The path is stored in the DB so we can find the file later.
    private function saveFile ($file, $subfolder){
       // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        // Only allow safe file types for documents
        $allowed = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($file['type'], $allowed)) {
            return null;
        }

        // Max 5MB per file
        if ($file['size'] > 5 * 1024 * 1024) {
            return null;
        }

        // Create the upload directory if it does not exist
        $uploadDir = __DIR__ . '/../../uploads/' . $subfolder . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate a unique filename to avoid conflicts
        // uniqid() generates a unique ID based on current time
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename  = uniqid('doc_', true) . '.' . $extension;
        $fullPath  = $uploadDir . $filename;

        // Move the file from temp location to our upload folder
        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            return null;
        }

        // Return the relative path (stored in DB)
        return 'uploads/' . $subfolder . '/' . $filename;
    }
}