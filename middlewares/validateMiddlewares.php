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

}
