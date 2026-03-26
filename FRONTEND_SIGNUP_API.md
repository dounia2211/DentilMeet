# Dentilmeet — API Documentation

> For the frontend team only.

---

## Base URL

```
http://localhost/signup/index.php
```

---

## Signup

**Method:** `POST`  
**URL:** `http://localhost/signup/index.php/api/auth/signup`

### What to send

```json
{
  "full_name": "Youcef Benali",
  "email":     "youcef@gmail.com",
  "password":  "motdepasse123",
  "phone":     "0555123456"
}
```

> `phone` is optional. `full_name`, `email`, and `password` are required.

---

### Responses

**Success `201` — account created:**
```json
{
  "message": "Account created successfully.",
  "token": "eyJ0eXAiOiJKV1Qi...",
  "patient": {
    "id_patient": 1,
    "full_name":  "Youcef Benali",
    "email":      "youcef@gmail.com",
    "phone":      "0555123456"
  }
}
```

**Validation error `400` — empty or invalid field:**
```json
{
  "errors": [
    "Full name is required",
    "Email address is required",
    "The password must contain at least 8 characters."
  ]
}
```

**Duplicate email `409` — email already registered:**
```json
{
  "message": "This email alrady taken."
}
```

**Server error `500`:**
```json
{
  "message": "Error while creating the account. Please try again."
}
```

---

## HTTP Status Codes

| Code | Meaning |
|------|---------|
| 201  | Signup successful |
| 400  | Invalid or missing field — loop through `errors` array |
| 409  | Email already exists |
| 500  | Server error — contact backend team |

---

## What to Do With the Token

After a successful signup save the token in localStorage:

```javascript
localStorage.setItem('token', data.token);
```

Send it with every future protected request:

```javascript
const token = localStorage.getItem('token');

fetch('http://localhost/signup/signup.php/api/...', {
  method: 'GET',
  headers: {
    'Content-Type':  'application/json',
    'Authorization': `Bearer ${token}`
  }
});
```

---

## Important

Always include this header in every request:

```javascript
'Content-Type': 'application/json'
```

Errors come in two formats — always check for both:

```javascript
if (response.errors) {
  // 400 — show each error under the correct field
  response.errors.forEach(err => console.log(err));
}

if (response.message) {
  // 409 or 500 — show the message to the user
  console.log(response.message);
}
```

---

## Field Validation Rules

| Field | Required | Rules |
|-------|----------|-------|
| full_name | Yes | Min 3 characters, max 200, letters and spaces only |
| email | Yes | Valid email format, max 150 characters |
| password | Yes | Min 8 characters, max 255 |
| phone | No | Numbers only, 7 to 20 digits |
