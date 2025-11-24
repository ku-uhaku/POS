# API Documentation

## Base URL
```
http://127.0.0.1:8000/api/v1
```

## Authentication
All protected endpoints require authentication using Bearer token in the Authorization header:
```
Authorization: Bearer {token}
```

## Store Context
For multi-store users, you can specify which store context to use by including the `X-Store-ID` header:
```
X-Store-ID: {store_id}
```

If the header is not provided, the system will automatically use the user's default store. All data queries will be filtered by the active store context. Users can only access stores they are assigned to.

## Response Format
All API responses follow a consistent format:

### Success Response
```json
{
    "success": true,
    "message": "Success message",
    "data": {
        // Response data
    }
}
```

### Error Response
```json
{
    "success": false,
    "message": "Error message",
    "errors": {
        // Validation errors (if applicable)
    }
}
```

---

## Authentication Endpoints

### Register User
**POST** `/auth/register`

Register a new user account.

**Request Body:**
```json
{
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "password": "password123",
    "age": 30,
    "cin": "12345678",
    "gender": "male",
    "phone": "+1234567890",
    "address": "123 Main St",
    "city": "New York",
    "state": "NY",
    "country": "USA",
    "postal_code": "10001",
    "employee_id": "EMP-001",
    "hire_date": "2024-01-01",
    "salary": 50000.00,
    "status": "active",
    "store_id": 1
}
```

**Required Fields:**
- `first_name` (string, max:255)
- `last_name` (string, max:255)
- `email` (string, email, unique)
- `password` (string)

**Optional Fields:**
- `age` (integer, min:1, max:150)
- `cin` (string, max:255)
- `gender` (enum: male, female, other)
- `avatar` (string, max:255)
- `phone` (string, max:255)
- `address` (string, max:255)
- `city` (string, max:255)
- `state` (string, max:255)
- `country` (string, max:255)
- `postal_code` (string, max:255)
- `employee_id` (string, max:255, unique)
- `hire_date` (date)
- `salary` (numeric, min:0)
- `status` (enum: active, inactive, suspended)
- `store_id` (exists:stores,id)

**Response:** `201 Created`
```json
{
    "success": true,
    "message": "User registered successfully",
    "data": {
        "user": {
            "id": 1,
            "first_name": "John",
            "last_name": "Doe",
            "full_name": "John Doe",
            "email": "john@example.com",
            // ... other user fields
        },
        "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
    }
}
```

---

### Login
**POST** `/auth/login`

Authenticate user and receive access token.

**Request Body:**
```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

**Required Fields:**
- `email` (string, email)
- `password` (string)

**Response:** `200 OK`
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "first_name": "John",
            "last_name": "Doe",
            "full_name": "John Doe",
            "email": "john@example.com",
            // ... other user fields
        },
        "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
    }
}
```

---

### Logout
**POST** `/auth/logout`

**Authentication:** Required

Revoke the current access token.

**Response:** `200 OK`
```json
{
    "success": true,
    "message": "Logged out successfully"
}
```

---

### Get Profile
**GET** `/auth/profile`

**Authentication:** Required

Get the authenticated user's profile.

**Response:** `200 OK`
```json
{
    "success": true,
    "message": "Profile retrieved successfully",
    "data": {
        "user": {
            "id": 1,
            "first_name": "John",
            "last_name": "Doe",
            "full_name": "John Doe",
            "email": "john@example.com",
            "roles": [
                {
                    "id": 1,
                    "name": "admin"
                }
            ],
            "permissions": [
                "view users",
                "create users",
                // ... other permissions
            ],
            // ... other user fields
        }
    }
}
```

---

### Update Profile
**PUT/PATCH** `/auth/profile`

**Authentication:** Required

Update the authenticated user's profile.

**Request Body:**
```json
{
    "first_name": "Jane",
    "last_name": "Doe",
    "email": "jane@example.com",
    "password": "newpassword123",
    "password_confirmation": "newpassword123",
    "age": 25,
    "phone": "+1234567890"
}
```

**All fields are optional** (use `sometimes` validation)

**Response:** `200 OK`
```json
{
    "success": true,
    "message": "Profile updated successfully",
    "data": {
        "user": {
            "id": 1,
            "first_name": "Jane",
            "last_name": "Doe",
            // ... updated user fields
        }
    }
}
```

---

## User Management Endpoints

### List Users
**GET** `/users`

**Authentication:** Required  
**Permission:** `view users`

Get a paginated list of users.

**Query Parameters:**
- `page` (integer, optional) - Page number for pagination

**Response:** `200 OK`
```json
{
    "success": true,
    "message": "Users retrieved successfully",
    "data": {
        "users": [
            {
                "id": 1,
                "first_name": "John",
                "last_name": "Doe",
                "full_name": "John Doe",
                "email": "john@example.com",
                "roles": [
                    {
                        "id": 1,
                        "name": "admin"
                    }
                ],
                // ... other user fields
            }
        ],
        "pagination": {
            "current_page": 1,
            "last_page": 5,
            "per_page": 15,
            "total": 75
        }
    }
}
```

---

### Get User
**GET** `/users/{id}`

**Authentication:** Required  
**Permission:** `view users`

Get a specific user by ID.

**Response:** `200 OK`
```json
{
    "success": true,
    "message": "User retrieved successfully",
    "data": {
        "user": {
            "id": 1,
            "first_name": "John",
            "last_name": "Doe",
            "full_name": "John Doe",
            "email": "john@example.com",
            "roles": [
                {
                    "id": 1,
                    "name": "admin"
                }
            ],
            // ... other user fields
        }
    }
}
```

---

### Delete User
**DELETE** `/users/{id}`

**Authentication:** Required  
**Permission:** `delete users`

Soft delete a user.

**Response:** `200 OK`
```json
{
    "success": true,
    "message": "User deleted successfully"
}
```

---

## Role Management Endpoints

### List Roles
**GET** `/roles`

**Authentication:** Required  
**Permission:** `view roles`

Get all roles with their permissions.

**Response:** `200 OK`
```json
{
    "success": true,
    "message": "Roles retrieved successfully",
    "data": {
        "roles": [
            {
                "id": 1,
                "name": "admin",
                "permissions": [
                    "view users",
                    "create users",
                    // ... other permissions
                ],
                "created_at": "2024-01-01T00:00:00+00:00"
            }
        ]
    }
}
```

---

### Create Role
**POST** `/roles`

**Authentication:** Required  
**Permission:** `create roles`

Create a new role.

**Request Body:**
```json
{
    "name": "manager",
    "permissions": [
        "view users",
        "view roles"
    ]
}
```

**Required Fields:**
- `name` (string, max:255, unique)

**Optional Fields:**
- `permissions` (array) - Array of permission names

**Response:** `201 Created`
```json
{
    "success": true,
    "message": "Role created successfully",
    "data": {
        "role": {
            "id": 2,
            "name": "manager",
            "permissions": [
                "view users",
                "view roles"
            ]
        }
    }
}
```

---

### Update Role
**PUT/PATCH** `/roles/{id}`

**Authentication:** Required  
**Permission:** `edit roles`

Update a role.

**Request Body:**
```json
{
    "name": "updated-manager",
    "permissions": [
        "view users",
        "create users"
    ]
}
```

**All fields are optional**

**Response:** `200 OK`
```json
{
    "success": true,
    "message": "Role updated successfully",
    "data": {
        "role": {
            "id": 2,
            "name": "updated-manager",
            "permissions": [
                "view users",
                "create users"
            ]
        }
    }
}
```

---

### Delete Role
**DELETE** `/roles/{id}`

**Authentication:** Required  
**Permission:** `delete roles`

Delete a role.

**Response:** `200 OK`
```json
{
    "success": true,
    "message": "Role deleted successfully"
}
```

---

## Store Management Endpoints

### List Stores
**GET** `/stores`

**Authentication:** Required  
**Permission:** `view stores`

Get a paginated list of stores.

**Query Parameters:**
- `page` (integer, optional) - Page number for pagination

**Response:** `200 OK`
```json
{
    "success": true,
    "message": "Stores retrieved successfully",
    "data": {
        "stores": [
            {
                "id": 1,
                "name": "Main Store",
                "code": "STORE-001",
                "address": "123 Main St",
                "city": "New York",
                "state": "NY",
                "country": "USA",
                "postal_code": "10001",
                "phone": "+1234567890",
                "email": "store@example.com",
                "status": "active",
                "users_count": 5,
                "created_at": "2024-01-01T00:00:00+00:00",
                "updated_at": "2024-01-01T00:00:00+00:00"
            }
        ],
        "pagination": {
            "current_page": 1,
            "last_page": 3,
            "per_page": 15,
            "total": 45
        }
    }
}
```

---

### Create Store
**POST** `/stores`

**Authentication:** Required  
**Permission:** `create stores`

Create a new store.

**Request Body:**
```json
{
    "name": "Main Store",
    "code": "STORE-001",
    "address": "123 Main St",
    "city": "New York",
    "state": "NY",
    "country": "USA",
    "postal_code": "10001",
    "phone": "+1234567890",
    "email": "store@example.com",
    "status": "active"
}
```

**Required Fields:**
- `name` (string, max:255)

**Optional Fields:**
- `code` (string, max:255, unique)
- `address` (string, max:255)
- `city` (string, max:255)
- `state` (string, max:255)
- `country` (string, max:255)
- `postal_code` (string, max:255)
- `phone` (string, max:255)
- `email` (string, email, max:255)
- `status` (enum: active, inactive)

**Response:** `201 Created`
```json
{
    "success": true,
    "message": "Store created successfully",
    "data": {
        "store": {
            "id": 1,
            "name": "Main Store",
            "code": "STORE-001",
            "status": "active",
            // ... other store fields
        }
    }
}
```

---

### Get Store
**GET** `/stores/{id}`

**Authentication:** Required  
**Permission:** `view stores`

Get a specific store by ID with associated users.

**Response:** `200 OK`
```json
{
    "success": true,
    "message": "Store retrieved successfully",
    "data": {
        "store": {
            "id": 1,
            "name": "Main Store",
            "code": "STORE-001",
            "address": "123 Main St",
            "city": "New York",
            "state": "NY",
            "country": "USA",
            "postal_code": "10001",
            "phone": "+1234567890",
            "email": "store@example.com",
            "status": "active",
            "users": [
                {
                    "id": 1,
                    "first_name": "John",
                    "last_name": "Doe",
                    "full_name": "John Doe",
                    "email": "john@example.com"
                }
            ],
            // ... audit trail fields
        }
    }
}
```

---

### Update Store
**PUT/PATCH** `/stores/{id}`

**Authentication:** Required  
**Permission:** `edit stores`

Update a store.

**Request Body:**
```json
{
    "name": "Updated Store Name",
    "status": "inactive",
    "phone": "+9876543210"
}
```

**All fields are optional** (use `sometimes` validation)

**Response:** `200 OK`
```json
{
    "success": true,
    "message": "Store updated successfully",
    "data": {
        "store": {
            "id": 1,
            "name": "Updated Store Name",
            "status": "inactive",
            // ... updated store fields
        }
    }
}
```

---

### Delete Store
**DELETE** `/stores/{id}`

**Authentication:** Required  
**Permission:** `delete stores`

Soft delete a store.

**Response:** `200 OK`
```json
{
    "success": true,
    "message": "Store deleted successfully"
}
```

---

## Error Codes

### 401 Unauthorized
Returned when authentication is required but not provided or invalid.

```json
{
    "success": false,
    "message": "Unauthenticated"
}
```

### 403 Forbidden
Returned when the user doesn't have the required permission.

```json
{
    "success": false,
    "message": "You do not have the required permission to perform this action."
}
```

### 422 Unprocessable Entity
Returned when validation fails.

```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "email": [
            "The email field is required."
        ],
        "password": [
            "The password field is required."
        ]
    }
}
```

### 404 Not Found
Returned when a resource is not found.

```json
{
    "success": false,
    "message": "Resource not found"
}
```

---

## Notes

- All timestamps are returned in ISO 8601 format
- All dates are returned in `YYYY-MM-DD` format
- Pagination defaults to 15 items per page
- Soft deleted resources are excluded from listings by default
- Audit trail information (created_by, updated_by, deleted_by) is included in resources when available
- The `full_name` field is a computed attribute combining `first_name` and `last_name`

## Store Context Notes

- Users can be assigned to multiple stores via the many-to-many relationship
- Each user has a default store that is used when `X-Store-ID` header is not provided
- The active store context automatically filters all data queries for models using the `BelongsToStore` trait
- Users can only access stores they are assigned to
- Store switching is done via the `X-Store-ID` header on each request, or by using the `/stores/switch` endpoint
- The `/stores` endpoint returns only stores the user has access to
- When viewing a specific store, the system verifies the user has access to that store

---

**Last Updated:** 2024-11-22

## Store Context Notes

- Users can be assigned to multiple stores via the many-to-many relationship
- Each user has a default store that is used when `X-Store-ID` header is not provided
- The active store context automatically filters all data queries for models using the `BelongsToStore` trait
- Users can only access stores they are assigned to
- Store switching is done via the `X-Store-ID` header on each request, or by using the `/stores/switch` endpoint

