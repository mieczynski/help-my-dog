# REST API Plan - Help My Dog

## 1. Resources

| Resource | Database Table | Description |
|----------|---------------|-------------|
| Authentication | user | User registration and login operations |
| Users | user | User account management |
| Dogs | dog | Dog profile management (CRUD operations) |
| Advice Cards | advice_card | AI-generated training advice and 7-day plans |
| Categories | problem_category | Training problem categories (read-only lookup) |

## 2. Endpoints

### 2.1 Authentication

#### POST /api/auth/register

**Description:** Register a new user account

**Authentication:** None required

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "securePassword123"
}
```

**Success Response (201 Created):**
```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "email": "user@example.com",
    "isActive": true,
    "createdAt": "2025-10-18T10:30:00Z"
  }
}
```

**Error Responses:**
- **400 Bad Request** - Validation errors
  ```json
  {
    "error": "validation_failed",
    "message": "Invalid input data",
    "violations": [
      {
        "field": "email",
        "message": "This value is not a valid email address."
      },
      {
        "field": "password",
        "message": "Password must be at least 8 characters long."
      }
    ]
  }
  ```
- **409 Conflict** - Email already exists
  ```json
  {
    "error": "email_exists",
    "message": "An account with this email already exists."
  }
  ```

**Validation Rules:**
- email: valid email format, max 255 characters
- password: minimum 8 characters, will be hashed with bcrypt

---

#### POST /api/auth/login

**Description:** Authenticate user and receive JWT token

**Authentication:** None required

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "securePassword123"
}
```

**Success Response (200 OK):**
```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "email": "user@example.com",
    "isActive": true
  }
}
```

**Error Responses:**
- **401 Unauthorized** - Invalid credentials
  ```json
  {
    "error": "invalid_credentials",
    "message": "Invalid email or password."
  }
  ```
- **403 Forbidden** - Account inactive
  ```json
  {
    "error": "account_inactive",
    "message": "Your account has been deactivated. Please contact support."
  }
  ```

---

### 2.2 Dogs

#### GET /api/dogs

**Description:** List all dogs belonging to the authenticated user

**Authentication:** JWT required

**Query Parameters:**
- `includeDeleted` (optional, boolean): Include soft-deleted dogs (default: false)

**Success Response (200 OK):**
```json
{
  "data": [
    {
      "id": "650e8400-e29b-41d4-a716-446655440001",
      "name": "Rex",
      "breed": "German Shepherd",
      "ageMonths": 24,
      "gender": "male",
      "weightKg": 35.5,
      "energyLevel": "high",
      "createdAt": "2025-10-10T14:20:00Z",
      "updatedAt": "2025-10-15T09:15:00Z"
    },
    {
      "id": "650e8400-e29b-41d4-a716-446655440002",
      "name": "Luna",
      "breed": "Mixed breed",
      "ageMonths": 6,
      "gender": "female",
      "weightKg": 8.3,
      "energyLevel": "very_high",
      "createdAt": "2025-10-12T11:00:00Z",
      "updatedAt": "2025-10-12T11:00:00Z"
    }
  ]
}
```

**Error Responses:**
- **401 Unauthorized** - Missing or invalid token

---

#### GET /api/dogs/{id}

**Description:** Get details of a specific dog

**Authentication:** JWT required

**Path Parameters:**
- `id` (UUID): Dog identifier

**Success Response (200 OK):**
```json
{
  "id": "650e8400-e29b-41d4-a716-446655440001",
  "name": "Rex",
  "breed": "German Shepherd",
  "ageMonths": 24,
  "gender": "male",
  "weightKg": 35.5,
  "energyLevel": "high",
  "createdAt": "2025-10-10T14:20:00Z",
  "updatedAt": "2025-10-15T09:15:00Z"
}
```

**Error Responses:**
- **401 Unauthorized** - Missing or invalid token
- **403 Forbidden** - Dog does not belong to authenticated user
  ```json
  {
    "error": "access_denied",
    "message": "You do not have permission to access this resource."
  }
  ```
- **404 Not Found** - Dog not found or deleted
  ```json
  {
    "error": "not_found",
    "message": "Dog not found."
  }
  ```

---

#### POST /api/dogs

**Description:** Create a new dog profile

**Authentication:** JWT required

**Request Body:**
```json
{
  "name": "Rex",
  "breed": "German Shepherd",
  "ageMonths": 24,
  "gender": "male",
  "weightKg": 35.5,
  "energyLevel": "high"
}
```

**Success Response (201 Created):**
```json
{
  "id": "650e8400-e29b-41d4-a716-446655440001",
  "name": "Rex",
  "breed": "German Shepherd",
  "ageMonths": 24,
  "gender": "male",
  "weightKg": 35.5,
  "energyLevel": "high",
  "createdAt": "2025-10-18T10:30:00Z",
  "updatedAt": "2025-10-18T10:30:00Z"
}
```

**Error Responses:**
- **400 Bad Request** - Validation errors
  ```json
  {
    "error": "validation_failed",
    "message": "Invalid input data",
    "violations": [
      {
        "field": "name",
        "message": "Name cannot be blank."
      },
      {
        "field": "ageMonths",
        "message": "Age must be between 0 and 300 months."
      },
      {
        "field": "weightKg",
        "message": "Weight must be between 0.01 and 200 kg."
      },
      {
        "field": "gender",
        "message": "Gender must be either 'male' or 'female'."
      },
      {
        "field": "energyLevel",
        "message": "Energy level must be one of: very_low, low, medium, high, very_high."
      }
    ]
  }
  ```
- **401 Unauthorized** - Missing or invalid token

**Validation Rules:**
- name: required, 1-100 characters
- breed: required, max 100 characters
- ageMonths: required, integer, 0-300
- gender: required, enum ('male', 'female')
- weightKg: required, decimal(5,2), 0.01-200.00
- energyLevel: required, enum ('very_low', 'low', 'medium', 'high', 'very_high')

---

#### PUT /api/dogs/{id}

**Description:** Update an existing dog profile

**Authentication:** JWT required

**Path Parameters:**
- `id` (UUID): Dog identifier

**Request Body:**
```json
{
  "name": "Rex",
  "breed": "German Shepherd",
  "ageMonths": 25,
  "gender": "male",
  "weightKg": 36.0,
  "energyLevel": "medium"
}
```

**Success Response (200 OK):**
```json
{
  "id": "650e8400-e29b-41d4-a716-446655440001",
  "name": "Rex",
  "breed": "German Shepherd",
  "ageMonths": 25,
  "gender": "male",
  "weightKg": 36.0,
  "energyLevel": "medium",
  "createdAt": "2025-10-10T14:20:00Z",
  "updatedAt": "2025-10-18T11:00:00Z"
}
```

**Error Responses:**
- **400 Bad Request** - Validation errors (same as POST)
- **401 Unauthorized** - Missing or invalid token
- **403 Forbidden** - Dog does not belong to authenticated user
- **404 Not Found** - Dog not found

---

#### DELETE /api/dogs/{id}

**Description:** Soft delete a dog profile (sets deleted_at timestamp)

**Authentication:** JWT required

**Path Parameters:**
- `id` (UUID): Dog identifier

**Success Response (204 No Content)**

**Error Responses:**
- **401 Unauthorized** - Missing or invalid token
- **403 Forbidden** - Dog does not belong to authenticated user
- **404 Not Found** - Dog not found

**Note:** This is a soft delete operation. The dog record remains in the database with `deleted_at` set. All associated advice cards are also soft-deleted (cascade).

---

### 2.3 Categories

#### GET /api/categories

**Description:** List all active problem categories for training advice

**Authentication:** JWT required

**Success Response (200 OK):**
```json
{
  "data": [
    {
      "id": "750e8400-e29b-41d4-a716-446655440001",
      "code": "behavior",
      "name": "Zachowanie",
      "priority": 1,
      "isActive": true
    },
    {
      "id": "750e8400-e29b-41d4-a716-446655440002",
      "code": "obedience",
      "name": "Posłuszeństwo",
      "priority": 2,
      "isActive": true
    },
    {
      "id": "750e8400-e29b-41d4-a716-446655440003",
      "code": "tricks",
      "name": "Nauka sztuczek",
      "priority": 3,
      "isActive": true
    },
    {
      "id": "750e8400-e29b-41d4-a716-446655440004",
      "code": "free_shaping",
      "name": "Free-shaping",
      "priority": 4,
      "isActive": true
    },
    {
      "id": "750e8400-e29b-41d4-a716-446655440005",
      "code": "socialization",
      "name": "Socjalizacja",
      "priority": 5,
      "isActive": true
    },
    {
      "id": "750e8400-e29b-41d4-a716-446655440006",
      "code": "anxiety",
      "name": "Lęki i fobie",
      "priority": 6,
      "isActive": true
    },
    {
      "id": "750e8400-e29b-41d4-a716-446655440007",
      "code": "leash_walking",
      "name": "Chodzenie na smyczy",
      "priority": 7,
      "isActive": true
    },
    {
      "id": "750e8400-e29b-41d4-a716-446655440008",
      "code": "other",
      "name": "Inne",
      "priority": 99,
      "isActive": true
    }
  ]
}
```

**Error Responses:**
- **401 Unauthorized** - Missing or invalid token

**Note:** Categories are sorted by priority in ascending order. Only active categories (isActive: true) are returned.

---

#### GET /api/categories/{id}

**Description:** Get details of a specific category

**Authentication:** JWT required

**Path Parameters:**
- `id` (UUID): Category identifier

**Success Response (200 OK):**
```json
{
  "id": "750e8400-e29b-41d4-a716-446655440001",
  "code": "behavior",
  "name": "Zachowanie",
  "priority": 1,
  "isActive": true
}
```

**Error Responses:**
- **401 Unauthorized** - Missing or invalid token
- **404 Not Found** - Category not found

---

### 2.4 Advice Cards

#### POST /api/advice-cards

**Description:** Generate AI-powered training advice or 7-day plan for a dog

**Authentication:** JWT required

**Request Body:**
```json
{
  "dogId": "650e8400-e29b-41d4-a716-446655440001",
  "categoryId": "750e8400-e29b-41d4-a716-446655440007",
  "problemDescription": "My dog pulls on the leash during walks and doesn't respond to commands.",
  "adviceType": "quick"
}
```

Or for 7-day plan:
```json
{
  "dogId": "650e8400-e29b-41d4-a716-446655440001",
  "categoryId": "750e8400-e29b-41d4-a716-446655440007",
  "problemDescription": "My dog pulls on the leash during walks and doesn't respond to commands.",
  "adviceType": "plan_7_days"
}
```

**Success Response (201 Created) - Quick Advice:**
```json
{
  "id": "850e8400-e29b-41d4-a716-446655440001",
  "dogId": "650e8400-e29b-41d4-a716-446655440001",
  "categoryId": "750e8400-e29b-41d4-a716-446655440007",
  "problemDescription": "My dog pulls on the leash during walks and doesn't respond to commands.",
  "aiResponse": "Based on Rex's profile (German Shepherd, 24 months, high energy), here are specific steps to address leash pulling:\n\n1. Start training in a calm environment...\n2. Use high-value treats...\n3. Practice the 'stop and wait' technique...\n\nRemember that German Shepherds are intelligent and respond well to consistency.",
  "planContent": null,
  "adviceType": "quick",
  "rating": null,
  "createdAt": "2025-10-18T12:00:00Z",
  "updatedAt": "2025-10-18T12:00:00Z"
}
```

**Success Response (201 Created) - 7-Day Plan:**
```json
{
  "id": "850e8400-e29b-41d4-a716-446655440002",
  "dogId": "650e8400-e29b-41d4-a716-446655440001",
  "categoryId": "750e8400-e29b-41d4-a716-446655440007",
  "problemDescription": "My dog pulls on the leash during walks and doesn't respond to commands.",
  "aiResponse": "7-day training plan for Rex to address leash pulling...",
  "planContent": {
    "days": [
      {
        "day": 1,
        "content": "**Goal:** Introduce loose leash walking concept\n\n**Steps:**\n1. Gather high-value treats\n2. Practice in a quiet area\n3. Walk a few steps, reward when leash is loose\n\n**Success Criteria:** Dog walks 10 steps with loose leash\n\n**Tips:** Keep sessions short (5-10 minutes)"
      },
      {
        "day": 2,
        "content": "**Goal:** Reinforce loose leash behavior\n\n**Steps:**\n1. Continue in quiet environment\n2. Increase distance to 20 steps\n3. Introduce verbal cue 'easy'\n\n**Success Criteria:** Dog responds to 'easy' cue 3 out of 5 times\n\n**Tips:** Be consistent with timing of rewards"
      },
      {
        "day": 3,
        "content": "..."
      },
      {
        "day": 4,
        "content": "..."
      },
      {
        "day": 5,
        "content": "..."
      },
      {
        "day": 6,
        "content": "..."
      },
      {
        "day": 7,
        "content": "**Goal:** Practice in real-world environment\n\n**Steps:**\n1. Take walk in usual route\n2. Apply all techniques learned\n3. Be patient with distractions\n\n**Success Criteria:** Complete full walk with 80% loose leash time\n\n**Tips:** Continue practicing daily for long-term results"
      }
    ]
  },
  "adviceType": "plan_7_days",
  "rating": null,
  "createdAt": "2025-10-18T12:05:00Z",
  "updatedAt": "2025-10-18T12:05:00Z"
}
```

**Error Responses:**
- **400 Bad Request** - Validation errors
  ```json
  {
    "error": "validation_failed",
    "message": "Invalid input data",
    "violations": [
      {
        "field": "dogId",
        "message": "Dog ID is required."
      },
      {
        "field": "categoryId",
        "message": "Category ID is required."
      },
      {
        "field": "problemDescription",
        "message": "Problem description is required."
      },
      {
        "field": "adviceType",
        "message": "Advice type must be either 'quick' or 'plan_7_days'."
      }
    ]
  }
  ```
- **401 Unauthorized** - Missing or invalid token
- **403 Forbidden** - Dog does not belong to authenticated user
  ```json
  {
    "error": "access_denied",
    "message": "The specified dog does not belong to you."
  }
  ```
- **404 Not Found** - Dog or category not found
  ```json
  {
    "error": "not_found",
    "message": "Dog or category not found."
  }
  ```
- **422 Unprocessable Entity** - Risk keywords detected
  ```json
  {
    "error": "risk_detected",
    "message": "Your description contains keywords that suggest a serious issue (aggression, injury, pain). We strongly recommend consulting with a professional dog trainer or veterinary behaviorist instead of relying on automated advice.",
    "riskKeywords": ["aggression", "bite", "blood"]
  }
  ```
- **503 Service Unavailable** - AI service timeout or error
  ```json
  {
    "error": "ai_service_unavailable",
    "message": "The AI service is temporarily unavailable. Please try again later."
  }
  ```

**Validation Rules:**
- dogId: required, valid UUID, must belong to authenticated user
- categoryId: required, valid UUID, must exist and be active
- problemDescription: required, minimum 10 characters, maximum 2000 characters
- adviceType: required, enum ('quick', 'plan_7_days')

**Business Logic:**
1. Verify dog ownership
2. Check for risk keywords in problemDescription (keywords: agresja, atak, pogryzienie, krew, rana, silny ból, krwawienie, ugryzł człowieka)
3. Fetch dog profile data and category information
4. Build personalized AI prompt including:
   - Dog's name, breed, age, gender, weight, energy level
   - Selected category
   - Problem description
   - Advice type (quick vs 7-day plan)
5. Call OpenAI API (GPT-4 or GPT-3.5-turbo) with 10-second timeout
6. Parse AI response:
   - For quick advice: store full response in ai_response, plan_content = null
   - For 7-day plan: store full response in ai_response, parse structured plan into plan_content JSONB
7. Save advice_card to database
8. Return created advice card

**Expected Response Time:** < 8 seconds (target), timeout at 10 seconds

---

#### GET /api/advice-cards

**Description:** List advice cards with filtering and pagination

**Authentication:** JWT required

**Query Parameters:**
- `dogId` (optional, UUID): Filter by specific dog
- `categoryId` (optional, UUID): Filter by category
- `adviceType` (optional, string): Filter by type ('quick' or 'plan_7_days')
- `page` (optional, integer, default: 1): Page number
- `limit` (optional, integer, default: 20, max: 100): Items per page

**Success Response (200 OK):**
```json
{
  "data": [
    {
      "id": "850e8400-e29b-41d4-a716-446655440001",
      "dogId": "650e8400-e29b-41d4-a716-446655440001",
      "dogName": "Rex",
      "categoryId": "750e8400-e29b-41d4-a716-446655440007",
      "categoryName": "Chodzenie na smyczy",
      "problemDescription": "My dog pulls on the leash...",
      "adviceType": "quick",
      "rating": "helpful",
      "createdAt": "2025-10-18T12:00:00Z"
    },
    {
      "id": "850e8400-e29b-41d4-a716-446655440002",
      "dogId": "650e8400-e29b-41d4-a716-446655440001",
      "dogName": "Rex",
      "categoryId": "750e8400-e29b-41d4-a716-446655440001",
      "categoryName": "Zachowanie",
      "problemDescription": "Barking at strangers...",
      "adviceType": "plan_7_days",
      "rating": null,
      "createdAt": "2025-10-15T09:30:00Z"
    }
  ],
  "meta": {
    "total": 15,
    "page": 1,
    "limit": 20,
    "totalPages": 1
  }
}
```

**Error Responses:**
- **401 Unauthorized** - Missing or invalid token
- **400 Bad Request** - Invalid query parameters
  ```json
  {
    "error": "invalid_parameters",
    "message": "Invalid query parameters provided.",
    "violations": [
      {
        "field": "page",
        "message": "Page must be a positive integer."
      },
      {
        "field": "limit",
        "message": "Limit must be between 1 and 100."
      }
    ]
  }
  ```

**Note:** Only advice cards for dogs owned by the authenticated user are returned. Results are sorted by createdAt DESC by default.

---

#### GET /api/advice-cards/{id}

**Description:** Get full details of a specific advice card including AI response and plan content

**Authentication:** JWT required

**Path Parameters:**
- `id` (UUID): Advice card identifier

**Success Response (200 OK) - Quick Advice:**
```json
{
  "id": "850e8400-e29b-41d4-a716-446655440001",
  "dog": {
    "id": "650e8400-e29b-41d4-a716-446655440001",
    "name": "Rex",
    "breed": "German Shepherd",
    "ageMonths": 24,
    "gender": "male",
    "weightKg": 35.5,
    "energyLevel": "high"
  },
  "category": {
    "id": "750e8400-e29b-41d4-a716-446655440007",
    "code": "leash_walking",
    "name": "Chodzenie na smyczy"
  },
  "problemDescription": "My dog pulls on the leash during walks and doesn't respond to commands.",
  "aiResponse": "Based on Rex's profile (German Shepherd, 24 months, high energy), here are specific steps...",
  "planContent": null,
  "adviceType": "quick",
  "rating": "helpful",
  "createdAt": "2025-10-18T12:00:00Z",
  "updatedAt": "2025-10-18T12:30:00Z"
}
```

**Success Response (200 OK) - 7-Day Plan:**
```json
{
  "id": "850e8400-e29b-41d4-a716-446655440002",
  "dog": {
    "id": "650e8400-e29b-41d4-a716-446655440001",
    "name": "Rex",
    "breed": "German Shepherd",
    "ageMonths": 24,
    "gender": "male",
    "weightKg": 35.5,
    "energyLevel": "high"
  },
  "category": {
    "id": "750e8400-e29b-41d4-a716-446655440007",
    "code": "leash_walking",
    "name": "Chodzenie na smyczy"
  },
  "problemDescription": "My dog pulls on the leash during walks and doesn't respond to commands.",
  "aiResponse": "7-day training plan for Rex to address leash pulling...",
  "planContent": {
    "days": [
      {
        "day": 1,
        "content": "**Goal:** Introduce loose leash walking concept..."
      },
      {
        "day": 2,
        "content": "**Goal:** Reinforce loose leash behavior..."
      },
      ...
    ]
  },
  "adviceType": "plan_7_days",
  "rating": null,
  "createdAt": "2025-10-18T12:05:00Z",
  "updatedAt": "2025-10-18T12:05:00Z"
}
```

**Error Responses:**
- **401 Unauthorized** - Missing or invalid token
- **403 Forbidden** - Advice card's dog does not belong to authenticated user
  ```json
  {
    "error": "access_denied",
    "message": "You do not have permission to access this resource."
  }
  ```
- **404 Not Found** - Advice card not found
  ```json
  {
    "error": "not_found",
    "message": "Advice card not found."
  }
  ```

---

#### PATCH /api/advice-cards/{id}/rating

**Description:** Rate an advice card (one-time rating)

**Authentication:** JWT required

**Path Parameters:**
- `id` (UUID): Advice card identifier

**Request Body:**
```json
{
  "rating": "helpful"
}
```

**Success Response (200 OK):**
```json
{
  "id": "850e8400-e29b-41d4-a716-446655440001",
  "rating": "helpful",
  "updatedAt": "2025-10-18T14:30:00Z"
}
```

**Error Responses:**
- **400 Bad Request** - Validation errors
  ```json
  {
    "error": "validation_failed",
    "message": "Invalid input data",
    "violations": [
      {
        "field": "rating",
        "message": "Rating must be either 'helpful' or 'not_helpful'."
      }
    ]
  }
  ```
- **401 Unauthorized** - Missing or invalid token
- **403 Forbidden** - Advice card's dog does not belong to authenticated user
- **404 Not Found** - Advice card not found
- **409 Conflict** - Advice card already rated
  ```json
  {
    "error": "already_rated",
    "message": "This advice card has already been rated. Ratings cannot be changed."
  }
  ```

**Validation Rules:**
- rating: required, enum ('helpful', 'not_helpful')

**Business Logic:**
- Rating can only be set once (immutable after first rating)
- Rating is used for success metrics (target: >80% helpful ratings)

---

## 3. Authentication and Authorization

### Authentication Mechanism

**JWT (JSON Web Tokens) - Stateless Authentication**

- All API endpoints require JWT authentication except:
  - POST /api/auth/register
  - POST /api/auth/login
- JWT token is passed in the Authorization header: `Authorization: Bearer {token}`
- Token is generated by LexikJWTAuthenticationBundle
- Token payload contains:
  - `user_id` (UUID)
  - `email` (string)
  - `roles` (array, e.g., ["ROLE_USER"])
  - `exp` (expiration timestamp)
  - `iat` (issued at timestamp)

### Token Lifecycle

**Token Generation:**
- Tokens are generated upon successful registration or login
- Token expiration: 1 hour (3600 seconds) - configurable in Symfony
- Token signing algorithm: RS256 (RSA with SHA-256)

**Token Validation:**
- Performed automatically by Symfony Security Component on every request
- Validates signature, expiration, and issuer
- Extracts user_id from payload to load User entity from database

**Token Refresh:**
- Not implemented in MVP (stateless approach)
- Users must re-login after token expiration
- Future enhancement: implement refresh token mechanism

### Authorization Rules

**Resource Ownership Validation:**

1. **Dogs:**
   - Users can only access, modify, or delete their own dogs
   - Ownership verified via `dog.user_id === authenticated_user.id`

2. **Advice Cards:**
   - Users can only access advice cards for dogs they own
   - Ownership verified via `advice_card.dog.user_id === authenticated_user.id`

3. **Categories:**
   - Read-only access for all authenticated users
   - No ownership restrictions

**Implementation Approach:**
- Use Symfony Voters for complex authorization logic
- Implement `DogVoter` and `AdviceCardVoter` to check ownership
- Return 403 Forbidden if ownership check fails

### Password Security

- Passwords hashed using Symfony's auto algorithm (bcrypt with cost factor 12)
- Minimum password length: 8 characters (enforced at application level)
- Password validation rules (recommended):
  - At least one uppercase letter
  - At least one lowercase letter
  - At least one number
  - Special characters optional for MVP


## 4. Validation and Business Logic

### 4.1 Validation Rules by Resource

#### User (Registration/Login)

**Email Validation:**
- Required field
- Valid email format (RFC 5322)
- Maximum length: 255 characters
- Must be unique in the database
- CHECK constraint regex: `^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}$`

**Password Validation:**
- Required field
- Minimum length: 8 characters (application level)
- Hashed password length: minimum 60 characters (database level, bcrypt requirement)
- Complexity rules (recommended): mix of uppercase, lowercase, numbers

#### Dog Profile

**Name:**
- Required field
- Minimum length: 1 character
- Maximum length: 100 characters
- CHECK constraint: `LENGTH(name) >= 1 AND LENGTH(name) <= 100`

**Breed:**
- Required field
- Maximum length: 100 characters
- Accepts "mieszaniec" (mixed breed) or specific breed names

**Age (ageMonths):**
- Required field
- Data type: integer
- Minimum value: 0 (newborn)
- Maximum value: 300 months (25 years)
- CHECK constraint: `age_months >= 0 AND age_months <= 300`

**Gender:**
- Required field
- Enum values: 'male', 'female'
- CHECK constraint: `gender IN ('male', 'female')`

**Weight (weightKg):**
- Required field
- Data type: decimal(5,2)
- Minimum value: 0.01 kg
- Maximum value: 200.00 kg
- CHECK constraint: `weight_kg > 0 AND weight_kg <= 200.00`

**Energy Level:**
- Required field
- Enum values: 'very_low', 'low', 'medium', 'high', 'very_high'
- CHECK constraint: `energy_level IN ('very_low', 'low', 'medium', 'high', 'very_high')`

#### Advice Card

**Dog ID:**
- Required field
- Must be valid UUID
- Must reference existing, non-deleted dog
- Dog must belong to authenticated user (authorization check)

**Category ID:**
- Required field
- Must be valid UUID
- Must reference existing, active category

**Problem Description:**
- Required field
- Minimum length: 10 characters (application level)
- Maximum length: 2000 characters (application level)
- Data type: TEXT (database supports unlimited length)

**Advice Type:**
- Required field
- Enum values: 'quick', 'plan_7_days'
- CHECK constraint: `advice_type IN ('quick', 'plan_7_days')`

**Plan Content:**
- Conditional requirement based on advice_type
- Must be NOT NULL when advice_type = 'plan_7_days'
- Must be NULL when advice_type = 'quick'
- Data type: JSONB with specific structure
- CHECK constraint: `(advice_type = 'plan_7_days' AND plan_content IS NOT NULL) OR (advice_type = 'quick' AND plan_content IS NULL)`

**Plan Content JSONB Structure:**
```json
{
  "days": [
    {
      "day": 1,
      "content": "string (markdown formatted)"
    },
    {
      "day": 2,
      "content": "string"
    },
    ...
    {
      "day": 7,
      "content": "string"
    }
  ]
}
```

**Rating:**
- Optional field (nullable)
- Enum values when set: 'helpful', 'not_helpful'
- CHECK constraint: `rating IS NULL OR rating IN ('helpful', 'not_helpful')`
- Immutable after first rating (business logic validation)

#### Problem Category

**Code:**
- Required field
- Maximum length: 50 characters
- Must be unique
- Format: lowercase letters and underscores only
- CHECK constraint: `code ~ '^[a-z_]+$'`

**Name:**
- Required field
- Minimum length: 1 character
- Maximum length: 100 characters
- CHECK constraint: `LENGTH(name) >= 1 AND LENGTH(name) <= 100`

**Priority:**
- Required field
- Data type: integer
- Default value: 0
- Used for sorting categories in UI

**Is Active:**
- Required field
- Data type: boolean
- Default value: true
- Controls category visibility in API responses


#### Pagination

**Purpose:** Optimize performance and user experience for large result sets

**Implementation:**
- Applies to: GET /api/advice-cards
- Query parameters:
  - `page` (integer, default: 1, min: 1)
  - `limit` (integer, default: 20, min: 1, max: 100)
- Calculation:
  - Offset: `(page - 1) * limit`
  - Total pages: `CEIL(total_count / limit)`
- Response format:
  ```json
  {
    "data": [...],
    "meta": {
      "total": 150,
      "page": 1,
      "limit": 20,
      "totalPages": 8
    }
  }
  ```
- Use indexed queries for performance (see db-plan.md indexes)

### 4.3 Error Handling Standards

**HTTP Status Codes:**

- **200 OK** - Successful GET, PUT, PATCH requests
- **201 Created** - Successful POST request creating new resource
- **204 No Content** - Successful DELETE request
- **400 Bad Request** - Validation errors, malformed request body
- **401 Unauthorized** - Missing, invalid, or expired JWT token
- **403 Forbidden** - Valid authentication but insufficient permissions (ownership check failed)
- **404 Not Found** - Resource does not exist or has been soft-deleted
- **409 Conflict** - Resource conflict (duplicate email, already rated, etc.)
- **422 Unprocessable Entity** - Business logic validation failed (risk detection)
- **500 Internal Server Error** - Unexpected server error
- **503 Service Unavailable** - External service (AI) unavailable or timeout

**Error Response Format:**

All error responses follow consistent JSON structure:
```json
{
  "error": "error_code",
  "message": "Human-readable error message",
  "violations": [
    {
      "field": "fieldName",
      "message": "Specific validation error for this field"
    }
  ]
}
```

**Symfony Integration:**
- Use EventListener/EventSubscriber to catch exceptions
- Transform Symfony validation errors into standardized format
- Log errors with appropriate severity levels (Monolog)
- Never expose sensitive information (stack traces, SQL queries) in production

---

## 5. API Design Principles

### RESTful Conventions

**Resource Naming:**
- Use plural nouns for collections: /api/dogs, /api/categories
- Use singular nouns for specific resources: /api/dogs/{id}
- Use kebab-case for multi-word resources: /api/advice-cards
- Avoid verbs in URLs (use HTTP methods instead)

**HTTP Methods:**
- **GET** - Retrieve resource(s), no side effects, idempotent
- **POST** - Create new resource, non-idempotent
- **PUT** - Update entire resource (replace), idempotent
- **PATCH** - Partial update of resource, idempotent
- **DELETE** - Remove resource, idempotent

**Idempotency:**
- GET, PUT, PATCH, DELETE are idempotent (can be safely retried)
- POST is not idempotent (creates new resource each time)


## 6. OpenAPI/Swagger Documentation

### Documentation Generation

**Recommended Approach:**
- Use NelmioApiDocBundle for Symfony
- Annotations on controller methods with #[OA\] attributes
- Auto-generate OpenAPI 3.0 specification
- Serve Swagger UI at /api/doc

**Benefits:**
- Interactive API testing
- Client SDK generation
- Clear contract for frontend developers
- Up-to-date documentation

### Example Annotation:

```php
#[Route('/api/dogs', methods: ['POST'])]
#[OA\Post(
    summary: 'Create a new dog profile',
    tags: ['Dogs'],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name', 'breed', 'ageMonths', 'gender', 'weightKg', 'energyLevel'],
            properties: [
                new OA\Property(property: 'name', type: 'string', minLength: 1, maxLength: 100),
                new OA\Property(property: 'breed', type: 'string', maxLength: 100),
                new OA\Property(property: 'ageMonths', type: 'integer', minimum: 0, maximum: 300),
                new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female']),
                new OA\Property(property: 'weightKg', type: 'number', format: 'float', minimum: 0.01, maximum: 200),
                new OA\Property(property: 'energyLevel', type: 'string', enum: ['very_low', 'low', 'medium', 'high', 'very_high'])
            ]
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'Dog created successfully'),
        new OA\Response(response: 400, description: 'Validation error'),
        new OA\Response(response: 401, description: 'Unauthorized')
    ]
)]
public function createDog(Request $request): JsonResponse
{
    // Implementation
}
```

## 6. API Endpoint Summary

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | /api/auth/register | Register new user | No |
| POST | /api/auth/login | Login and get JWT token | No |
| GET | /api/dogs | List user's dogs | Yes |
| GET | /api/dogs/{id} | Get dog details | Yes |
| POST | /api/dogs | Create dog profile | Yes |
| PUT | /api/dogs/{id} | Update dog profile | Yes |
| DELETE | /api/dogs/{id} | Soft delete dog | Yes |
| GET | /api/categories | List active categories | Yes |
| GET | /api/categories/{id} | Get category details | Yes |
| POST | /api/advice-cards | Generate AI advice/plan | Yes |
| GET | /api/advice-cards | List advice cards | Yes |
| GET | /api/advice-cards/{id} | Get advice card details | Yes |
| PATCH | /api/advice-cards/{id}/rating | Rate advice card | Yes |

**Total Endpoints:** 13 (MVP)
