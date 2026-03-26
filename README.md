# Dentilmeet — Backend API

PHP + MySQL REST API for the Dentilmeet application.

## Project structure

```
/dentilmeet-backend
  /config
    Database.php          ← DB connection (PDO)
  /controllers
    AuthController.php    ← receives requests, sends responses
  /services
    AuthService.php       ← signup & login business logic
  /models
    PatientModel.php      ← all SQL queries for patient table
  /middlewares
    ValidateMiddleware.php ← input validation
    AuthMiddleware.php    ← JWT verification for protected routes
  /utils
    TokenUtil.php         ← JWT generate & verify
  /vendor                 ← Composer packages (DO NOT commit)
  .env                    ← your local secrets (DO NOT commit)
  .env.example            ← template — copy this to create .env
  .gitignore
  composer.json
  composer.lock
  index.php               ← entry point / router
```

## Team setup (run once after cloning)

### 1. Clone the repo
```bash
git clone https://github.com/dounia2211/DentilMeet
cd DentilMeet
```

### 2. Install PHP dependencies
```bash
composer install
```

### 3. Create your .env file
```bash
cp .env.example .env
```
Then open `.env` and fill in your local DB credentials:
```
DB_HOST=localhost
DB_NAME=dentilmeet
DB_USER=root
DB_PASS=
JWT_SECRET=any_long_random_string_you_invent
JWT_EXPIRY_DAYS=7
```

### 4. Set up the database
- Open phpMyAdmin
- Create a database named `dentilmeet`
- Import / run the patient table SQL (your team already has this)


### 5. Test the API
Open Postman and send:
```
POST http://localhost/signup/index.php/api/auth/signup
Content-Type: application/json

{
  "full_name": "test user",
  "email": "test@email.com",
  "password": "password123",
  "phone": "0555123456"
}
```
You should get back a `201 Created` with a JWT token.

## Available routes

| Method | Route | Auth required | Description |
|--------|-------|---------------|-------------|
| POST | `/api/auth/signup` | No | Create new patient account |

## Adding new routes

In `index.php`, add a new `elseif` block:
```php
} elseif ($uri === '/api/your/route' && $method === 'POST') {
    $controller = new YourController($pdo);
    $controller->yourMethod();
```

## Protected routes (require JWT)

For any route that needs the patient to be logged in, add at the top of the controller method:
```php
$patient = AuthMiddleware::handle();
// $patient['id_patient'] is now available
```

## Validation errors response format

```json
{
  "errors": [
    "The full name is required.",
    "The email adress is invalid."
  ]
}
```

## Success response format (signup)

```json
{
  "message": "Compte créé avec succès.",
  "token": "eyJ0eXAiOiJKV1Q...",
  "patient": {
    "id_patient": 1,
    "full_name": "test user",
    "email": "test@email.com",
    "phone": "0555123456"
  }
}
```
