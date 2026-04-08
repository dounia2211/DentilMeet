<?php
class validateMiddleware{
    public static function validateSignup ($data) {
        $errors = [];


        // ── 1. FULL NAME ─────────────────────────────────────────────────
        if (empty(trim($data['full_name'] ?? ''))) {
            $errors[] = "Full name is required.";

        } elseif (strlen(trim($data['full_name'])) < 3) {
            $errors[] = "The full name must contain at least 3 characters.";

        } elseif (strlen(trim($data['full_name'])) > 200) {
            $errors[] = "The full name must not exceed 200 characters.";

        } elseif (!preg_match('/^[\p{L}\s\-]+$/u', trim($data['full_name']))) {
            $errors[] = "The full name can only contain letters and spaces.";
        }
 
        // ── 2. EMAIL ─────────────────────────────────────────────────
        // Check 1: email field must exist and not be empty
        if (empty(trim($data['email'] ?? ''))) {
            $errors[] = "Email address is required.";
 
        // Check 2: email must be a valid format (e.g. user@domain.com)
        // filter_var with FILTER_VALIDATE_EMAIL is PHP's built-in email checker
        } elseif (!filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL)) {
            $errors[] = "The email address is invalid.";
 
        // Check 3: email must not exceed 150 characters (matches your VARCHAR(150))
        } elseif (strlen(trim($data['email'])) > 150) {
            $errors[] = "The email address must not exceed 150 characters.";
        }
 
        // ── 3. PASSWORD ──────────────────────────────────────────────
        // Check 1: password field must exist and not be empty
        if (empty($data['password'] ?? '')) {
            $errors[] = "Password is required";
 
        // Check 2: password must be at least 8 characters
        } elseif (strlen($data['password']) < 8) {
            $errors[] = "The password must contain at least 8 characters.";

        // Check 3: password must not exceed 255 characters
        } elseif (strlen($data['password']) > 255) {
            $errors[] = "The password is too long.";
        }
 
        // ── 4. PHONE ─────────────────────────────────────────────────
        // Phone is optional — we only validate it if the patient provided one.
        // If empty, we skip validation and store NULL in the DB.
        if (!empty(trim($data['phone'] ?? ''))) {
 
            $phone = trim($data['phone']);
 
            // Check: phone must match international format
            // Accepts: 0555123456 / +213555123456 / +1 800 555 0100
            // Must be between 7 and 15 digits (ignoring spaces, dashes, +)
            if (!preg_match('/^\+?[\d\s\-]{7,20}$/', $phone)) {
                $errors[] = "The phone number is invalid.";
            }
        }
 
        return $errors; // empty array = all good, filled array = stop and send errors
    }

    public static function validateLogin($data) {
        $errors = [];

        if (empty(trim($data['email'] ?? ''))) {
            $errors[] = "Email address is required.";
        } elseif (!filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL)) {
            $errors[] = "The email address is invalid.";
        }

        if (empty($data['password'] ?? '')) {
            $errors[] = "Password is required.";
        }

        return $errors;
    }

    public static function validateBooking($data) {
        $errors = [];
 
        // ── id_dentist ───────────────────────────────────────────────
        // Must be present and must be a number greater than 0
        // empty() catches null, "", 0, "0" — all invalid for an ID
        if (empty($data['id_dentist'])) {
            $errors[] = 'Dentist is required.';
 
        // is_numeric() checks it is actually a number, not random text
        } elseif (!is_numeric($data['id_dentist'])) {
            $errors[] = 'Dentist ID must be a number.';
        }
 
        // ── appointment_date ─────────────────────────────────────────
        // Must be present and must match YYYY-MM-DD format
        // React should send it as: "2026-03-20"
        if (empty($data['appointment_date'])) {
            $errors[] = 'Appointment date is required.';
 
        // preg_match checks exact format: 4 digits - 2 digits - 2 digits
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['appointment_date'])) {
            $errors[] = 'Date format must be YYYY-MM-DD (example: 2026-03-20).';
        }
 
        // ── appointment_time ─────────────────────────────────────────
        // Must be present and must be one of the valid time slots
        // that match exactly what your TIMES array in React has
        if (empty($data['appointment_time'])) {
            $errors[] = 'Appointment time is required.';
        } else {
            // These must match your frontend TIMES array EXACTLY
            // including spacing and AM/PM format
            $validTimes = [
                '9:00 AM',  '9:30 AM',
                '10:00 AM', '10:30 AM',
                '11:00 AM', '11:30 AM',
                '12:00 PM', '12:30 PM',
                '1:00 PM',  '1:30 PM',
                '2:00 PM',  '2:30 PM'
            ];
 
            // in_array() checks if the sent time exists in the valid list
            if (!in_array($data['appointment_time'], $validTimes)) {
                $errors[] = 'Invalid time slot selected.';
            }
        }
 
        // ── service_type ─────────────────────────────────────────────
        // Must be one of the 3 visit types shown in your frontend:
        //   Check-up, Cleaning, Emergency
        // These match exactly your visitTypes array in Booking.jsx
        if (empty(trim($data['service_type'] ?? ''))) {
            $errors[] = 'Visit type is required.';
        } else {
            $validServices = ['Check-up', 'Cleaning', 'Emergency'];
            if (!in_array($data['service_type'], $validServices)) {
                $errors[] = 'Visit type must be Check-up, Cleaning or Emergency.';
            }
        }
 
        // ── reason ───────────────────────────────────────────────────
        // Reason is optional — patient might not type anything in the textarea
        // But if they do, it must not be too long
        if (!empty($data['reason']) && strlen($data['reason']) > 500) {
            $errors[] = 'Reason must not exceed 500 characters.';
        }

         // FAVORITES
         public static function validateFavorite($data) {
        $errors = [];
 
        // id_dentist est obligatoire pour add et remove
        if (empty($data['id_dentist'])) {
            $errors[] = 'Dentist ID is required.';
        } elseif (!is_numeric($data['id_dentist']) || (int)$data['id_dentist'] <= 0) {
            $errors[] = 'Dentist ID must be a valid number.';
        }
 
        return $errors;
    }
 
        // REVIEWS (RATINGS)
          public static function validateReview($data) {
        $errors = [];
 
        // ── id_dentist ────────────────────────────────────────────────────
        if (empty($data['id_dentist'])) {
            $errors[] = 'Dentist ID is required.';
        } elseif (!is_numeric($data['id_dentist']) || (int)$data['id_dentist'] <= 0) {
            $errors[] = 'Dentist ID must be a valid number.';
        }
 
        // ── id_appointment ────────────────────────────────────────────────
        // Obligatoire car UNIQUE KEY (id_patient, id_appointment) dans votre SQL
        if (empty($data['id_appointment'])) {
            $errors[] = 'Appointment ID is required.';
        } elseif (!is_numeric($data['id_appointment']) || (int)$data['id_appointment'] <= 0) {
            $errors[] = 'Appointment ID must be a valid number.';
        }
 
        // ── rating ────────────────────────────────────────────────────────
        // Doit être un entier entre 1 et 5
        if (!isset($data['rating']) || $data['rating'] === '') {
            $errors[] = 'Rating is required.';
        } elseif (!is_numeric($data['rating']) || (int)$data['rating'] < 1 || (int)$data['rating'] > 5) {
            $errors[] = 'Rating must be a number between 1 and 5.';
        }
 
        // ── comment (optionnel) ───────────────────────────────────────────
        if (!empty($data['comment']) && strlen($data['comment']) > 1000) {
            $errors[] = 'Comment must not exceed 1000 characters.';
        }
 
        return $errors;
    }
    
        // MESSAGES
        public static function validateMessage($data) {
        $errors = [];
 
        // ── id_dentist ────────────────────────────────────────────────────
        if (empty($data['id_dentist'])) {
            $errors[] = 'Dentist ID is required.';
        } elseif (!is_numeric($data['id_dentist']) || (int)$data['id_dentist'] <= 0) {
            $errors[] = 'Dentist ID must be a valid number.';
        }
 
        // ── message_text ──────────────────────────────────────────────────
        if (empty(trim($data['message_text'] ?? ''))) {
            $errors[] = 'Message text is required.';
        } elseif (strlen(trim($data['message_text'])) > 2000) {
            $errors[] = 'Message must not exceed 2000 characters.';
        }
 
        // ── id_appointment (optionnel) ────────────────────────────────────
        // Nullable dans votre SQL — le patient peut envoyer un message
        // sans forcément le lier à un appointment
        if (!empty($data['id_appointment']) && !is_numeric($data['id_appointment'])) {
            $errors[] = 'Appointment ID must be a valid number.';
        }
 
            return $errors;
       }

        // PAYMENTS
        public static function validatePayment($data) {
        $errors = [];
 
        // ── id_appointment ────────────────────────────────────────────────
        // Obligatoire car payment est lié à un appointment dans votre SQL
        if (empty($data['id_appointment'])) {
            $errors[] = 'Appointment ID is required.';
        } elseif (!is_numeric($data['id_appointment']) || (int)$data['id_appointment'] <= 0) {
            $errors[] = 'Appointment ID must be a valid number.';
        }
 
        // ── amount ────────────────────────────────────────────────────────
        if (!isset($data['amount']) || $data['amount'] === '') {
            $errors[] = 'Amount is required.';
        } elseif (!is_numeric($data['amount']) || (float)$data['amount'] <= 0) {
            $errors[] = 'Amount must be a positive number.';
        }
 
        // ── method_payment ────────────────────────────────────────────────
        // Doit correspondre exactement à votre ENUM SQL :
        // 'especes' | 'carte' | 'virement'
        if (empty($data['method_payment'])) {
            $errors[] = 'Payment method is required.';
        } else {
            $validMethods = ['especes', 'carte', 'virement'];
            if (!in_array($data['method_payment'], $validMethods)) {
                $errors[] = 'Payment method must be: especes, carte or virement.';
            }
        }

        
        return $errors; // [] = all good, [...] = stop and return errors
    }



}
