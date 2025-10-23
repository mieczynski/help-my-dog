# API Endpoint Implementation Plan: POST /api/dogs

## 1. Przegląd punktu końcowego

**Cel:** Utworzenie nowego profilu psa przypisanego do uwierzytelnionego użytkownika.

**Funkcjonalność:**
- Przyjmuje dane profilu psa z żądania HTTP
- Waliduje wszystkie pola zgodnie z regułami biznesowymi
- Sprawdza uwierzytelnienie użytkownika (JWT)
- Tworzy nową encję Dog w bazie danych
- Automatycznie przypisuje psa do zalogowanego użytkownika
- Zwraca pełny profil psa z wygenerowanym UUID i timestampami

**Wzorzec architektury:** CQRS (Command Query Responsibility Segregation)
- Request → DTO → Command → CommandHandler → Factory → Entity → Response DTO

---

## 2. Szczegóły żądania

### HTTP
- **Metoda:** POST
- **Struktura URL:** `/api/dogs`
- **Content-Type:** `application/json`
- **Uwierzytelnienie:** Bearer JWT Token (header: `Authorization: Bearer {token}`)

### Parametry

**Wymagane (wszystkie pola):**

| Parametr | Typ | Walidacja | Opis |
|----------|-----|-----------|------|
| `name` | string | min: 1, max: 100 | Imię psa |
| `breed` | string | max: 100, not blank | Rasa (lub "mieszaniec") |
| `ageMonths` | integer | 0-300 | Wiek w miesiącach |
| `gender` | string | enum: male, female | Płeć psa |
| `weightKg` | float | 0.01-200.00 | Waga w kg |
| `energyLevel` | string | enum: very_low, low, medium, high, very_high | Poziom energii |

**Opcjonalne:** Brak

### Request Body Example
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

---

## 3. Wykorzystywane typy

### Request DTO
**Klasa:** `App\DTO\Request\Dog\CreateDogRequestDTO`

**Lokalizacja:** `api/src/DTO/Request/Dog/CreateDogRequestDTO.php`

**Charakterystyka:**
- Readonly class (immutable)
- Constructor property promotion
- Pełna walidacja za pomocą atrybutów Symfony Validator
- Deserializowany automatycznie przez Symfony Serializer

**Właściwości:**
```php
public function __construct(
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 100)]
    public string $name,

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $breed,

    #[Assert\NotNull]
    #[Assert\Type('integer')]
    #[Assert\Range(min: 0, max: 300)]
    public int $ageMonths,

    #[Assert\NotBlank]
    #[Assert\Choice(['male', 'female'])]
    public string $gender,

    #[Assert\NotNull]
    #[Assert\Type('numeric')]
    #[Assert\Range(min: 0.01, max: 200.00)]
    public float $weightKg,

    #[Assert\NotBlank]
    #[Assert\Choice(['very_low', 'low', 'medium', 'high', 'very_high'])]
    public string $energyLevel,
) {}
```

### Response DTO
**Klasa:** `App\DTO\Response\Dog\DogResponseDTO`

**Lokalizacja:** `api/src/DTO/Response/Dog/DogResponseDTO.php`

**Charakterystyka:**
- Zwykła klasa (nie readonly)
- Static factory method: `fromEntity(Dog $dog): self`
- Serializowany do JSON przez Symfony Serializer

**Właściwości:**
```php
public function __construct(
    public string $id,
    public string $name,
    public string $breed,
    public int $ageMonths,
    public string $gender,
    public float $weightKg,
    public string $energyLevel,
    public \DateTimeImmutable $createdAt,
    public \DateTimeImmutable $updatedAt,
) {}
```

### CQRS Command
**Klasa:** `App\Command\Dog\CreateDogCommand`

**Lokalizacja:** `api/src/Command/Dog/CreateDogCommand.php`

**Charakterystyka:**
- Readonly class (immutable)
- Reprezentuje intencję utworzenia psa
- Zawiera dane z Request DTO + userId z JWT

**Właściwości:**
```php
final readonly class CreateDogCommand
{
    public function __construct(
        public string $userId,
        public string $name,
        public string $breed,
        public int $ageMonths,
        public string $gender,
        public string $weightKg,  // string dla DECIMAL precision
        public string $energyLevel,
    ) {}
}
```

### CQRS CommandHandler
**Klasa:** `App\Handler\Dog\CreateDogCommandHandler`

**Lokalizacja:** `api/src/Handler/Dog/CreateDogCommandHandler.php`

**Charakterystyka:**
- Implementuje `MessageHandlerInterface`
- Atrybut `#[AsMessageHandler]` dla auto-rejestracji
- Deleguje tworzenie encji do `DogFactory`
- Persystuje encję poprzez `EntityManagerInterface`
- Zwraca utworzoną encję `Dog`

**Metoda:**
```php
public function __invoke(CreateDogCommand $command): Dog
```

### Factory
**Klasa:** `App\Factory\DogFactory`

**Lokalizacja:** `api/src/Factory/DogFactory.php`

**Charakterystyka:**
- Service autowired
- Odpowiedzialny za logikę tworzenia encji Dog
- Wstrzykuje UserRepository (do pobrania User entity)
- Ustawia wszystkie właściwości encji

**Metoda:**
```php
public function createFromCommand(CreateDogCommand $command): Dog
```

---

## 4. Szczegóły odpowiedzi

### Success Response (201 Created)

**Status Code:** `201 Created`

**Headers:**
```
Content-Type: application/json
Location: /api/dogs/{id}
```

**Body:**
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

### Error Responses

#### 400 Bad Request - Walidacja nie powiodła się

**Struktura:**
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
    }
  ]
}
```

**Przykładowe scenariusze:**
- `name` puste lub > 100 znaków
- `breed` puste lub > 100 znaków
- `ageMonths` nie jest liczbą całkowitą lub poza zakresem 0-300
- `gender` nie jest 'male' ani 'female'
- `weightKg` nie jest liczbą lub poza zakresem 0.01-200.00
- `energyLevel` nie jest jedną z dozwolonych wartości

#### 401 Unauthorized - Brak lub nieprawidłowy token

**Struktura:**
```json
{
  "error": "unauthorized",
  "message": "Missing or invalid authentication token"
}
```

**Scenariusze:**
- Brak nagłówka `Authorization`
- Token JWT wygasł
- Token JWT nieprawidłowy (zły podpis)
- Użytkownik nieaktywny (`is_active = false`)

#### 500 Internal Server Error - Błąd serwera

**Struktura:**
```json
{
  "error": "internal_server_error",
  "message": "An unexpected error occurred"
}
```

**Scenariusze:**
- Błąd połączenia z bazą danych
- Błąd podczas `flush()` encji
- Nieoczekiwany wyjątek w Handler/Factory

---

## 5. Przepływ danych

### Diagram sekwencji

```
Client
  |
  | POST /api/dogs + JWT
  v
Controller (DogController::create)
  |
  | 1. Deserializacja JSON → CreateDogRequestDTO
  | 2. Walidacja automatyczna (ValidatorInterface)
  |    - Błąd → 400 Bad Request
  |
  | 3. Pobranie userId z JWT (Security::getUser())
  |    - Brak użytkownika → 401 Unauthorized
  |
  | 4. Utworzenie CreateDogCommand
  v
Symfony Messenger (command.bus)
  |
  v
CreateDogCommandHandler
  |
  | 5. Dispatch command do handlera
  v
DogFactory
  |
  | 6. createFromCommand(CreateDogCommand)
  |    a. Pobranie User entity z UserRepository
  |    b. Utworzenie nowej instancji Dog
  |    c. Ustawienie wszystkich właściwości
  |    d. Przypisanie User do Dog
  |    e. Zwrot Dog entity
  v
CreateDogCommandHandler
  |
  | 7. $entityManager->persist($dog)
  | 8. $entityManager->flush()
  |    - Błąd DB → Exception → 500
  |
  | 9. Zwrot Dog entity
  v
Controller
  |
  | 10. DogResponseDTO::fromEntity($dog)
  | 11. JsonResponse z kodem 201
  v
Client (201 Created + JSON)
```

### Szczegółowy przepływ kroków

1. **Request Processing** (Controller):
   - Symfony deserializuje JSON do `CreateDogRequestDTO`
   - Automatyczna walidacja przez Symfony Validator
   - W razie błędów walidacji → 400 Bad Request

2. **Authentication** (Security Component):
   - Weryfikacja JWT tokena
   - Pobranie obiektu `User` z `Security::getUser()`
   - Sprawdzenie czy użytkownik istnieje i jest aktywny

3. **Command Creation** (Controller):
   - Utworzenie `CreateDogCommand` z danych DTO
   - Dodanie `userId` z uwierzytelnionego użytkownika

4. **Command Dispatching** (Messenger):
   - Dispatch command do `command.bus`
   - Messenger automatycznie wywołuje odpowiedni handler

5. **Business Logic** (CommandHandler + Factory):
   - Handler wywołuje `DogFactory::createFromCommand()`
   - Factory pobiera `User` entity z repository
   - Factory tworzy nową instancję `Dog`
   - Ustawia wszystkie właściwości z Command
   - Przypisuje User do Dog (`$dog->setUser($user)`)

6. **Persistence** (CommandHandler):
   - `$entityManager->persist($dog)` - dodaje Dog do jednostki pracy
   - `$entityManager->flush()` - wykonuje INSERT do bazy danych
   - Doctrine automatycznie generuje UUID i ustawia timestampy

7. **Response Creation** (Controller):
   - Utworzenie `DogResponseDTO::fromEntity($dog)`
   - Konwersja `weightKg` z string na float w DTO
   - Serializacja do JSON
   - Zwrot z kodem 201 Created

### Interakcje z bazą danych

**SELECT Queries:**
1. Pobranie User entity przez UserRepository:
   ```sql
   SELECT * FROM "user" WHERE id = ? AND deleted_at IS NULL
   ```

**INSERT Queries:**
2. Wstawienie Dog entity:
   ```sql
   INSERT INTO dog (
     id, user_id, name, breed, age_months, gender,
     weight_kg, energy_level, created_at, updated_at, deleted_at
   ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL)
   ```

**Transakcje:**
- Doctrine automatycznie opakowuje `flush()` w transakcję
- W razie błędu SQL → rollback automatyczny
- Exception propagowany do Controller → 500 error

---

## 6. Względy bezpieczeństwa

### 1. Uwierzytelnienie (Authentication)

**Mechanizm:** JWT (JSON Web Tokens) via LexikJWTAuthenticationBundle

**Implementacja w Controller:**
```php
#[Route('/api/dogs', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
public function create(/* ... */): JsonResponse
```

**Sprawdzanie:**
- Firewall Symfony automatycznie weryfikuje token JWT
- Token musi być w header: `Authorization: Bearer {token}`
- Token musi być poprawnie podpisany kluczem prywatnym
- Token nie może być wygasły (claim `exp`)
- User musi istnieć w bazie i być aktywny (`is_active = true`)

**Obsługa błędów:**
- Brak tokenu → 401 Unauthorized
- Token nieprawidłowy → 401 Unauthorized
- Token wygasły → 401 Unauthorized

### 2. Autoryzacja (Authorization)

**Zasada:** Każdy użytkownik może tworzyć tylko własne profile psów

**Implementacja:**
- Controller pobiera `userId` z JWT: `$security->getUser()->getId()`
- `CreateDogCommand` zawiera `userId` z tokena
- Factory automatycznie przypisuje `User` do `Dog` na podstawie `userId`
- BRAK możliwości podstawienia innego `userId` z request body

**Zabezpieczenie:**
```php
// BEZPIECZNE - userId pochodzi z JWT, nie z request body
$command = new CreateDogCommand(
    userId: $security->getUser()->getId(),  // Z tokena
    // ... reszta danych z DTO
);
```

**Zapobieganie:**
- ❌ Mass assignment attack - userId nie pochodzi z request body
- ✅ Użytkownik może tworzyć tylko swoje psy

### 3. Walidacja danych wejściowych

**Poziomy walidacji:**

**a) Request DTO (walidacja podstawowa):**
- Typy danych (string, integer, float)
- Długość stringów (min/max)
- Zakresy liczb (min/max)
- Enums (dozwolone wartości)
- Formaty (email, UUID - dla przyszłych endpointów)

**b) Entity (walidacja biznesowa):**
- Dodatkowe ograniczenia na poziomie encji
- Doctrine lifecycle callbacks

**Korzyści:**
- ✅ Zapobieganie SQL Injection (Doctrine używa prepared statements)
- ✅ Zapobieganie XSS (dane nie są renderowane bez escapowania)
- ✅ Zapobieganie Type Juggling (strict types w PHP 8.3)

### 4. SQL Injection Prevention

**Mechanizm:** Doctrine ORM z Parameterized Queries

**Przykład generowanego SQL:**
```sql
-- Doctrine automatycznie używa prepared statements
INSERT INTO dog (id, user_id, name, ...)
VALUES (:id, :user_id, :name, ...)

-- Parametry bindowane bezpiecznie:
-- :name => "Rex'; DROP TABLE dog; --"  (traktowane jako string)
```

**Zabezpieczenia:**
- ✅ Wszystkie wartości są bindowane jako parametry
- ✅ Brak konkatenacji stringów w SQL
- ✅ Doctrine automatycznie escapuje wartości

### 5. CORS (Cross-Origin Resource Sharing)

**Konfiguracja:** NelmioCorsBundle

**Dozwolone źródła:**
- Frontend development: `http://localhost:5173`
- Frontend production: konfiguracja w `nelmio_cors.yaml`

**Dozwolone metody:**
- POST, GET, PUT, PATCH, DELETE, OPTIONS

**Dozwolone headery:**
- `Authorization`, `Content-Type`, `Accept`

### 6. Rate Limiting

**Rekomendacja:** Implementacja rate limiting dla endpointu (opcjonalne w MVP)

**Możliwe rozwiązania:**
- Symfony Rate Limiter Component
- Redis-based rate limiting
- Nginx/API Gateway level limiting

**Sugerowane limity:**
- 10 requests/minute per user dla POST /api/dogs
- 100 requests/hour per user globalnie

### 7. Sensitive Data Handling

**Dane w Request:**
- ✅ Brak wrażliwych danych (imię psa, rasa - publiczne dla właściciela)

**Dane w Response:**
- ✅ Zwracane tylko dane profilu psa
- ❌ BRAK: password_hash, internal IDs, deleted_at

**Logging:**
- ✅ Logowanie żądań bez wrażliwych danych
- ✅ Użycie Monolog z kontekstem (userId, dogId)
- ❌ BRAK logowania: tokenów JWT, pełnych request bodies

### 8. Error Handling Security

**Zasada:** Nie ujawniaj szczegółów implementacji w błędach produkcyjnych

**Development:**
```json
{
  "error": "internal_server_error",
  "message": "SQLSTATE[23000]: Integrity constraint violation",
  "trace": "..."
}
```

**Production:**
```json
{
  "error": "internal_server_error",
  "message": "An unexpected error occurred"
}
```

**Implementacja:**
- Environment-aware error responses
- Szczegółowe błędy tylko w dev/test
- Generyczne komunikaty w production
- Full stack traces w logach (Monolog)

---

## 7. Obsługa błędów

### 1. Validation Errors (400 Bad Request)

**Trigger:** Symfony Validator wykrywa błędy w `CreateDogRequestDTO`

**Response Structure:**
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
    }
  ]
}
```

**Implementacja w Controller:**
```php
$errors = $validator->validate($requestDTO);
if (count($errors) > 0) {
    return new JsonResponse([
        'error' => 'validation_failed',
        'message' => 'Invalid input data',
        'violations' => $this->formatValidationErrors($errors),
    ], Response::HTTP_BAD_REQUEST);
}
```

**Szczegółowe błędy walidacji:**

| Pole | Błąd | Message |
|------|------|---------|
| name | Puste | "Name cannot be blank." |
| name | > 100 znaków | "Name cannot be longer than 100 characters." |
| breed | Puste | "Breed cannot be blank." |
| breed | > 100 znaków | "Breed cannot be longer than 100 characters." |
| ageMonths | Nie integer | "Age must be an integer." |
| ageMonths | < 0 lub > 300 | "Age must be between 0 and 300 months." |
| gender | Nie 'male'/'female' | "Gender must be either 'male' or 'female'." |
| weightKg | Nie numeric | "Weight must be a number." |
| weightKg | < 0.01 lub > 200 | "Weight must be between 0.01 and 200 kg." |
| energyLevel | Nieprawidłowa wartość | "Energy level must be one of: very_low, low, medium, high, very_high." |

### 2. Authentication Errors (401 Unauthorized)

**Trigger:** JWT token missing, invalid, or expired

**Response Structure:**
```json
{
  "error": "unauthorized",
  "message": "Missing or invalid authentication token"
}
```

**Scenariusze:**
- Brak nagłówka `Authorization`
- Token w nieprawidłowym formacie (nie "Bearer {token}")
- Token wygasł (exp claim < current time)
- Token z nieprawidłowym podpisem
- User z tokena nie istnieje w bazie
- User ma `is_active = false`

**Implementacja:**
- Automatyczne sprawdzanie przez Symfony Security Firewall
- Custom Exception Listener dla JWTAuthenticationException

### 3. User Not Found (500 Internal Server Error)

**Trigger:** User z tokena JWT nie istnieje w bazie (edge case)

**Scenariusz:**
- Token poprawny, ale użytkownik został usunięty z bazy
- Race condition między tokenem a usunięciem użytkownika

**Response:**
```json
{
  "error": "internal_server_error",
  "message": "An unexpected error occurred"
}
```

**Logging:**
```php
$logger->error('User from JWT not found in database', [
    'userId' => $command->userId,
    'endpoint' => 'POST /api/dogs',
]);
```

**Obsługa w Factory:**
```php
public function createFromCommand(CreateDogCommand $command): Dog
{
    $user = $this->userRepository->find($command->userId);

    if (!$user) {
        throw new \RuntimeException(sprintf(
            'User with ID %s not found',
            $command->userId
        ));
    }

    // ... rest of logic
}
```

### 4. Database Errors (500 Internal Server Error)

**Trigger:** Błędy podczas komunikacji z bazą danych

**Scenariusze:**
- Connection timeout
- Unique constraint violation (duplikat UUID - bardzo rzadkie)
- Foreign key constraint violation
- Insufficient database permissions
- Disk full

**Response:**
```json
{
  "error": "internal_server_error",
  "message": "An unexpected error occurred"
}
```

**Implementacja:**
```php
try {
    $dog = $commandBus->dispatch($command);
    $entityManager->flush();
} catch (UniqueConstraintViolationException $e) {
    $logger->error('Unique constraint violation', [
        'exception' => $e->getMessage(),
    ]);
    throw new \RuntimeException('Failed to create dog profile');
} catch (\Exception $e) {
    $logger->critical('Unexpected database error', [
        'exception' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    throw $e;
}
```

### 5. Unexpected Exceptions (500 Internal Server Error)

**Trigger:** Nieoczekiwane wyjątki w aplikacji

**Scenariusze:**
- Memory limit exceeded
- PHP fatal errors
- Unhandled exceptions w Factory/Handler
- Third-party library errors

**Obsługa:**
- Global Exception Listener
- Monolog logging z pełnym stack trace
- Environment-aware error messages

**Przykład Exception Listener:**
```php
class ExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $this->logger->error('Uncaught exception', [
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        $response = new JsonResponse([
            'error' => 'internal_server_error',
            'message' => $this->getEnvironmentMessage($exception),
        ], Response::HTTP_INTERNAL_SERVER_ERROR);

        $event->setResponse($response);
    }

    private function getEnvironmentMessage(\Throwable $e): string
    {
        if ($this->environment === 'dev') {
            return $e->getMessage();
        }

        return 'An unexpected error occurred';
    }
}
```

### 6. Logging Strategy

**Levels:**
- `ERROR` - błędy wymagające uwagi (user not found, DB errors)
- `WARNING` - błędy walidacji, 400 errors
- `INFO` - successful requests (opcjonalnie)
- `CRITICAL` - błędy krytyczne (DB down, out of memory)

**Context:**
```php
$logger->error('Failed to create dog profile', [
    'userId' => $userId,
    'dogData' => [
        'name' => $command->name,
        'breed' => $command->breed,
    ],
    'error' => $exception->getMessage(),
]);
```

**Kanały:**
- `app` - główny kanał aplikacji
- `doctrine` - błędy Doctrine ORM
- `security` - błędy uwierzytelniania

---

## 8. Rozważania dotyczące wydajności

### 1. Database Query Optimization

**Optymalizacje:**

**a) Indeksy na tabeli `dog`:**
```sql
-- Już zdefiniowane w encji
CREATE INDEX idx_dog_user_id ON dog(user_id);
CREATE INDEX idx_dog_user_active ON dog(user_id, deleted_at);
```

**Korzyści:**
- ✅ Szybkie wyszukiwanie psów użytkownika (GET /api/dogs)
- ✅ Szybkie filtrowanie aktywnych psów (deleted_at IS NULL)

**b) Single Query dla User:**
```php
// DogFactory - 1 SELECT query
$user = $this->userRepository->find($command->userId);
```

**c) Single INSERT dla Dog:**
```php
// CreateDogCommandHandler - 1 INSERT query
$entityManager->persist($dog);
$entityManager->flush();
```

**Całkowita liczba zapytań:** 2 (1 SELECT + 1 INSERT)

### 2. Caching Strategy

**Opcje cachingu:**

**a) User entity caching (opcjonalnie):**
- Cache User entity w Redis po pierwszym pobraniu
- TTL: 15 minut
- Invalidacja przy update/delete użytkownika

**b) Doctrine Second Level Cache (opcjonalnie):**
- Cache encji User dla częstych odczytów
- Konfiguracja w `doctrine.yaml`

**MVP Recommendation:**
- ❌ BRAK cachingu dla MVP
- Endpointy tworzące dane są z natury non-cacheable
- User SELECT jest szybki dzięki indeksom

### 3. Validation Performance

**Optymalizacja:**
- Symfony Validator cache validation metadata
- Walidacja działa w pamięci (brak DB queries)
- Early return przy pierwszym błędzie (opcjonalnie)

**Benchmark:**
- Walidacja DTO: ~1-2ms
- Deserializacja JSON: ~0.5ms

### 4. JSON Serialization

**Optymalizacja:**
- Symfony Serializer kompiluje metadane do cache
- Response DTO jest prosty (8 pól, brak zagnieżdżeń)
- Brak circular references

**Benchmark:**
- Serializacja DogResponseDTO: ~0.5-1ms

### 5. Connection Pooling

**Database Connection:**
- Doctrine używa persistent connections
- Connection pooling w PostgreSQL (pgBouncer - opcjonalnie)

**Redis Connection:**
- Redis client z connection pooling
- Dla przyszłych feature'ów (cache, sessions)

### 6. Potential Bottlenecks

**Zidentyfikowane wąskie gardła:**

| Bottleneck | Impact | Mitigation |
|------------|--------|------------|
| Database INSERT | ~10-20ms | Indeksy, connection pooling |
| User SELECT query | ~5-10ms | Indeksy, cache (future) |
| JWT verification | ~2-5ms | Symfony Security cache |
| JSON deserialization | ~0.5ms | Minimalne - brak działania |
| Validation | ~1-2ms | Metadata caching |

**Łączny czas odpowiedzi (expected):**
- Best case: ~50-80ms
- Average case: ~80-120ms
- Worst case (DB slow): ~200-500ms

### 7. Scalability Considerations

**Horizontal Scaling:**
- ✅ Stateless API (JWT, brak sesji)
- ✅ Możliwość load balancingu między instancjami
- ✅ Brak shared state poza bazą danych

**Database Scaling:**
- Read replicas dla GET endpoints (future)
- Write to master dla POST/PUT/DELETE
- Connection pooling (pgBouncer)

**Monitoring:**
- Symfony Profiler (dev environment)
- APM tools (Blackfire, New Relic) - opcjonalnie
- Database slow query log

### 8. Optimization Checklist

**Przed wdrożeniem:**
- ✅ OPcache enabled w PHP
- ✅ Doctrine metadata cache (production)
- ✅ Symfony cache warmed up
- ✅ Database indeksy utworzone
- ✅ Composer autoloader optimized (`--optimize-autoloader`)

**Monitorowanie:**
- Database query time (Doctrine Profiler)
- Response time metrics
- Error rate (4xx, 5xx)
- Throughput (requests/second)

---

## 9. Etapy wdrożenia

### Krok 1: Utworzenie Request DTO

**Plik:** `api/src/DTO/Request/Dog/CreateDogRequestDTO.php`

**Zadanie:**
- Utworzenie readonly class z constructor property promotion
- Dodanie pełnej walidacji za pomocą atrybutów Symfony Validator
- Wszystkie pola zgodne z API specification

**Walidacja:**
```php
#[Assert\NotBlank(message: 'Name cannot be blank.')]
#[Assert\Length(min: 1, max: 100, /* ... */)]
```

**Kryteria akceptacji:**
- ✅ Wszystkie pola mają odpowiednie typy (string, int, float)
- ✅ Wszystkie constrainty zgodne z tabelą w sekcji 2
- ✅ Messages w języku angielskim, zgodne z API spec

**Czas:** ~30 minut

### Krok 2: Utworzenie Response DTO

**Plik:** `api/src/DTO/Response/Dog/DogResponseDTO.php`

**Zadanie:**
- Utworzenie klasy z public properties
- Implementacja static method `fromEntity(Dog $dog): self`
- Konwersja weightKg z string na float
- Wszystkie pola zgodne z API specification

**Static factory method:**
```php
public static function fromEntity(Dog $dog): self
{
    return new self(
        id: $dog->getId(),
        name: $dog->getName(),
        // ... reszta pól
        weightKg: (float) $dog->getWeightKg(),  // string → float
        createdAt: $dog->getCreatedAt(),
        updatedAt: $dog->getUpdatedAt(),
    );
}
```

**Kryteria akceptacji:**
- ✅ Wszystkie pola z entity są zmapowane
- ✅ weightKg poprawnie konwertowany na float
- ✅ Timestamps w formacie DateTimeImmutable
- ✅ Brak pola `deletedAt` w response (tylko aktywne psy)

**Czas:** ~20 minut

### Krok 3: Utworzenie CQRS Command

**Plik:** `api/src/Command/Dog/CreateDogCommand.php`

**Zadanie:**
- Utworzenie readonly class
- Dodanie właściwości zgodnych z Request DTO + userId
- weightKg jako string (dla dokładności DECIMAL)
- Dodanie docblock z opisem przepływu

**Implementacja:**
```php
final readonly class CreateDogCommand
{
    public function __construct(
        public string $userId,      // Z JWT
        public string $name,        // Z DTO
        public string $breed,
        public int $ageMonths,
        public string $gender,
        public string $weightKg,    // STRING dla DECIMAL precision
        public string $energyLevel,
    ) {}
}
```

**Kryteria akceptacji:**
- ✅ Command jest readonly i final
- ✅ Wszystkie pola typowane
- ✅ weightKg jako string (nie float)
- ✅ Docblock opisuje cel Command

**Czas:** ~15 minut

### Krok 4: Utworzenie DogFactory

**Plik:** `api/src/Factory/DogFactory.php`

**Zadanie:**
- Utworzenie service class z DI
- Wstrzyknięcie UserRepository
- Implementacja metody `createFromCommand(CreateDogCommand $command): Dog`
- Logika tworzenia encji Dog z pełnym ustawieniem właściwości

**Implementacja:**
```php
class DogFactory
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function createFromCommand(CreateDogCommand $command): Dog
    {
        // 1. Pobranie User entity
        $user = $this->userRepository->find($command->userId);
        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        // 2. Utworzenie Dog entity
        $dog = new Dog();
        $dog->setUser($user);
        $dog->setName($command->name);
        $dog->setBreed($command->breed);
        $dog->setAgeMonths($command->ageMonths);
        $dog->setGender($command->gender);
        $dog->setWeightKg($command->weightKg);
        $dog->setEnergyLevel($command->energyLevel);

        return $dog;
    }
}
```

**Kryteria akceptacji:**
- ✅ Factory jest autowired service
- ✅ UserRepository wstrzyknięty przez DI
- ✅ Wszystkie pola Dog ustawione
- ✅ User przypisany do Dog
- ✅ Exception jeśli User nie istnieje

**Czas:** ~30 minut

### Krok 5: Utworzenie CommandHandler

**Plik:** `api/src/Handler/Dog/CreateDogCommandHandler.php`

**Zadanie:**
- Implementacja MessageHandlerInterface
- Atrybut `#[AsMessageHandler]`
- Wstrzyknięcie DogFactory i EntityManagerInterface
- Delegacja tworzenia do Factory
- Persistence encji

**Implementacja:**
```php
#[AsMessageHandler]
class CreateDogCommandHandler
{
    public function __construct(
        private readonly DogFactory $dogFactory,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function __invoke(CreateDogCommand $command): Dog
    {
        // 1. Delegacja do Factory
        $dog = $this->dogFactory->createFromCommand($command);

        // 2. Persistence
        $this->entityManager->persist($dog);
        $this->entityManager->flush();

        // 3. Zwrot encji
        return $dog;
    }
}
```

**Kryteria akceptacji:**
- ✅ Handler zarejestrowany przez `#[AsMessageHandler]`
- ✅ Logika delegowana do Factory (cienki handler)
- ✅ Flush wykonany poprawnie
- ✅ Handler zwraca Dog entity

**Czas:** ~20 minut

### Krok 6: Utworzenie Controller

**Plik:** `api/src/Controller/DogController.php`

**Zadanie:**
- Utworzenie metody `create()`
- Routing: `#[Route('/api/dogs', methods: ['POST'])]`
- Security: `#[IsGranted('ROLE_USER')]`
- Deserializacja Request DTO
- Walidacja automatyczna
- Utworzenie Command
- Dispatch do command.bus
- Utworzenie Response DTO
- Zwrot JsonResponse z kodem 201

**Implementacja:**
```php
#[Route('/api/dogs', name: 'api_dog_create', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
public function create(
    Request $request,
    SerializerInterface $serializer,
    ValidatorInterface $validator,
    MessageBusInterface $commandBus,
    Security $security,
): JsonResponse {
    // 1. Deserializacja
    $requestDTO = $serializer->deserialize(
        $request->getContent(),
        CreateDogRequestDTO::class,
        'json'
    );

    // 2. Walidacja
    $errors = $validator->validate($requestDTO);
    if (count($errors) > 0) {
        return $this->json([
            'error' => 'validation_failed',
            'message' => 'Invalid input data',
            'violations' => $this->formatValidationErrors($errors),
        ], Response::HTTP_BAD_REQUEST);
    }

    // 3. Utworzenie Command
    $command = new CreateDogCommand(
        userId: $security->getUser()->getId(),
        name: $requestDTO->name,
        breed: $requestDTO->breed,
        ageMonths: $requestDTO->ageMonths,
        gender: $requestDTO->gender,
        weightKg: (string) $requestDTO->weightKg,
        energyLevel: $requestDTO->energyLevel,
    );

    // 4. Dispatch
    $dog = $commandBus->dispatch($command);

    // 5. Response
    $responseDTO = DogResponseDTO::fromEntity($dog);

    return $this->json($responseDTO, Response::HTTP_CREATED);
}
```

**Kryteria akceptacji:**
- ✅ Routing poprawny
- ✅ Autoryzacja włączona (ROLE_USER)
- ✅ Deserializacja i walidacja
- ✅ Command tworzony z userId z JWT
- ✅ Response 201 Created
- ✅ Obsługa błędów walidacji

**Czas:** ~45 minut

### Krok 7: Konfiguracja Messenger Buses

**Plik:** `api/config/packages/messenger.yaml`

**Zadanie:**
- Sprawdzenie konfiguracji `command.bus`
- Upewnienie się, że `CreateDogCommand` jest routowany do odpowiedniego busa
- Weryfikacja, że handler jest zarejestrowany

**Konfiguracja (już powinna istnieć):**
```yaml
framework:
    messenger:
        default_bus: command.bus
        buses:
            command.bus:
                middleware:
                    - doctrine_transaction
            query.bus:
                middleware:
                    - doctrine_transaction
            event.bus:
                default_middleware: allow_no_handlers
```

**Kryteria akceptacji:**
- ✅ `command.bus` jest skonfigurowany
- ✅ `doctrine_transaction` middleware dodany
- ✅ Handlers auto-discovered z atrybutu `#[AsMessageHandler]`

**Czas:** ~10 minut (weryfikacja)

### Krok 8: Exception Listener (opcjonalnie)

**Plik:** `api/src/EventListener/ExceptionListener.php`

**Zadanie:**
- Utworzenie listenera dla nieobsłużonych wyjątków
- Logowanie błędów przez Monolog
- Zwrot odpowiedzi JSON z odpowiednim kodem błędu
- Environment-aware messages

**Implementacja:**
```php
class ExceptionListener
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $environment,
    ) {}

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $this->logger->error('Uncaught exception', [
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        $statusCode = $this->getStatusCode($exception);

        $response = new JsonResponse([
            'error' => $this->getErrorCode($statusCode),
            'message' => $this->getErrorMessage($exception),
        ], $statusCode);

        $event->setResponse($response);
    }

    private function getErrorMessage(\Throwable $e): string
    {
        return $this->environment === 'dev'
            ? $e->getMessage()
            : 'An unexpected error occurred';
    }
}
```

**Kryteria akceptacji:**
- ✅ Wszystkie wyjątki logowane
- ✅ Response JSON zgodny z API spec
- ✅ Dev environment pokazuje szczegóły
- ✅ Production ukrywa szczegóły

**Czas:** ~30 minut

### Krok 9: Testy jednostkowe

**Pliki:**
- `api/tests/Unit/Factory/DogFactoryTest.php`
- `api/tests/Unit/Handler/CreateDogCommandHandlerTest.php`
- `api/tests/Unit/DTO/DogResponseDTOTest.php`

**Zadanie:**
- Test DogFactory::createFromCommand()
- Test CreateDogCommandHandler::__invoke()
- Test DogResponseDTO::fromEntity()
- Mockowanie zależności (UserRepository, EntityManager)

**Przykład test:**
```php
class DogFactoryTest extends TestCase
{
    public function testCreateFromCommand(): void
    {
        // Arrange
        $userRepository = $this->createMock(UserRepository::class);
        $user = new User();
        $userRepository->method('find')->willReturn($user);

        $factory = new DogFactory($userRepository);

        $command = new CreateDogCommand(
            userId: 'user-uuid',
            name: 'Rex',
            breed: 'German Shepherd',
            ageMonths: 24,
            gender: 'male',
            weightKg: '35.50',
            energyLevel: 'high',
        );

        // Act
        $dog = $factory->createFromCommand($command);

        // Assert
        $this->assertInstanceOf(Dog::class, $dog);
        $this->assertSame('Rex', $dog->getName());
        $this->assertSame($user, $dog->getUser());
    }
}
```

**Kryteria akceptacji:**
- ✅ Code coverage > 80% dla Factory i Handler
- ✅ Wszystkie testy przechodzą
- ✅ Mockowanie poprawne

**Czas:** ~1 godzina

### Krok 10: Testy funkcjonalne

**Plik:** `api/tests/Functional/Controller/DogControllerTest.php`

**Zadanie:**
- Test pełnego flow POST /api/dogs
- Test z prawidłowymi danymi → 201
- Test z błędnymi danymi → 400
- Test bez tokena JWT → 401
- Test z nieprawidłowym tokenem → 401

**Przykład test:**
```php
class DogControllerTest extends WebTestCase
{
    public function testCreateDogSuccess(): void
    {
        $client = static::createClient();
        $token = $this->getAuthToken($client); // Helper method

        $client->request('POST', '/api/dogs', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Rex',
            'breed' => 'German Shepherd',
            'ageMonths' => 24,
            'gender' => 'male',
            'weightKg' => 35.5,
            'energyLevel' => 'high',
        ]));

        $this->assertResponseStatusCodeSame(201);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertSame('Rex', $responseData['name']);
    }

    public function testCreateDogValidationError(): void
    {
        $client = static::createClient();
        $token = $this->getAuthToken($client);

        $client->request('POST', '/api/dogs', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => '', // Błąd: puste
            'breed' => 'German Shepherd',
        ]));

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('validation_failed', $responseData['error']);
    }
}
```

**Kryteria akceptacji:**
- ✅ Test success case (201)
- ✅ Test validation errors (400)
- ✅ Test unauthorized (401)
- ✅ Test z prawdziwą bazą testową (SQLite lub PostgreSQL)

**Czas:** ~1 godzina

### Krok 11: Manualne testy API

**Narzędzia:** Postman, curl, HTTPie

**Test scenarios:**

**1. Success case:**
```bash
curl -X POST http://localhost:8080/api/dogs \
  -H "Authorization: Bearer {JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Rex",
    "breed": "German Shepherd",
    "ageMonths": 24,
    "gender": "male",
    "weightKg": 35.5,
    "energyLevel": "high"
  }'

# Expected: 201 Created
```

**2. Validation error:**
```bash
curl -X POST http://localhost:8080/api/dogs \
  -H "Authorization: Bearer {JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "",
    "ageMonths": 500
  }'

# Expected: 400 Bad Request z violations
```

**3. No authentication:**
```bash
curl -X POST http://localhost:8080/api/dogs \
  -H "Content-Type: application/json" \
  -d '{...}'

# Expected: 401 Unauthorized
```

**Kryteria akceptacji:**
- ✅ Wszystkie scenariusze działają zgodnie z API spec
- ✅ Response times < 200ms
- ✅ Dane poprawnie zapisane w bazie

**Czas:** ~30 minut

### Krok 12: Code Quality Checks

**Zadania:**

**a) PHP CS Fixer:**
```bash
docker compose exec php composer fix
```

**b) PHPStan:**
```bash
docker compose exec php composer analyse
```

**c) Prettier:**
```bash
docker compose exec php npm run format
```

**d) PHPUnit:**
```bash
docker compose exec php composer test
```

**Kryteria akceptacji:**
- ✅ Brak błędów w PHP CS Fixer
- ✅ PHPStan Level 8 passes
- ✅ Prettier formatting OK
- ✅ Wszystkie testy PHPUnit przechodzą

**Czas:** ~20 minut

### Krok 13: Dokumentacja

**Pliki:**
- Aktualizacja `readme` z przykładem użycia POST /api/dogs
- Opcjonalnie: Swagger/OpenAPI specification

**Zadanie:**
- Dodanie przykładu request/response do README
- Aktualizacja listy endpointów
- Dodanie informacji o authentication

**Kryteria akceptacji:**
- ✅ README zawiera przykład curl
- ✅ Dokumentacja zgodna z implementacją

**Czas:** ~20 minut

### Krok 14: Git Commit

**Zadanie:**
- Staging wszystkich zmian
- Commit z opisowym message
- Push do remote repository (opcjonalnie)

**Commit message:**
```
feat: implement POST /api/dogs endpoint

- Add CreateDogRequestDTO with full validation
- Add DogResponseDTO with fromEntity factory method
- Add CreateDogCommand and CreateDogCommandHandler
- Add DogFactory for entity creation logic
- Add DogController::create() action with JWT auth
- Add unit and functional tests
- Update README with API examples

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude <noreply@anthropic.com>
```

**Komendy:**
```bash
docker compose exec php php bin/console cache:clear
git add .
git status
git commit -m "..."
```

**Kryteria akceptacji:**
- ✅ Wszystkie pliki staged
- ✅ Commit message zgodny z konwencją
- ✅ Cache wyczyszczony przed commit

**Czas:** ~10 minut

---

## Podsumowanie czasu implementacji

| Krok | Zadanie | Czas |
|------|---------|------|
| 1 | Request DTO | 30 min |
| 2 | Response DTO | 20 min |
| 3 | CQRS Command | 15 min |
| 4 | DogFactory | 30 min |
| 5 | CommandHandler | 20 min |
| 6 | Controller | 45 min |
| 7 | Messenger config | 10 min |
| 8 | Exception Listener | 30 min |
| 9 | Unit tests | 60 min |
| 10 | Functional tests | 60 min |
| 11 | Manual testing | 30 min |
| 12 | Code quality | 20 min |
| 13 | Documentation | 20 min |
| 14 | Git commit | 10 min |
| **TOTAL** | | **~6.5 godziny** |

---

## Checklist przed wdrożeniem

### Functionality
- [ ] Request DTO utworzone z pełną walidacją
- [ ] Response DTO utworzone z factory method
- [ ] CQRS Command i CommandHandler zaimplementowane
- [ ] DogFactory utworzony i przetestowany
- [ ] Controller action zaimplementowany
- [ ] JWT authentication działa poprawnie
- [ ] Wszystkie error cases obsłużone

### Testing
- [ ] Unit tests dla Factory (coverage > 80%)
- [ ] Unit tests dla Handler
- [ ] Unit tests dla Response DTO
- [ ] Functional tests dla success case (201)
- [ ] Functional tests dla validation errors (400)
- [ ] Functional tests dla unauthorized (401)
- [ ] Manual testing z Postman/curl

### Code Quality
- [ ] PHP CS Fixer passes
- [ ] PHPStan Level 8 passes
- [ ] Prettier formatting OK
- [ ] Brak błędów w PHPUnit
- [ ] Wszystkie services autowired

### Security
- [ ] JWT authentication wymagane
- [ ] userId pobierany z tokena (nie z request body)
- [ ] Walidacja wszystkich pól request
- [ ] SQL injection prevented (Doctrine)
- [ ] Error messages nie ujawniają szczegółów w production

### Performance
- [ ] Database indeksy utworzone
- [ ] Query count zoptymalizowany (2 queries)
- [ ] Response time < 200ms (average)
- [ ] Brak N+1 query problem

### Documentation
- [ ] README zaktualizowany z przykładami
- [ ] API specification zgodna z implementacją
- [ ] Docblocks w kodzie
- [ ] Commit message zgodny z konwencją

### Database
- [ ] Entity Dog istnieje i ma poprawne mapowanie
- [ ] Migration dla tabeli dog wykonana
- [ ] Foreign key do user poprawnie skonfigurowany
- [ ] Indeksy utworzone

### Configuration
- [ ] Messenger buses skonfigurowane
- [ ] Security firewall skonfigurowany
- [ ] CORS skonfigurowany (NelmioCorsBundle)
- [ ] JWT secret key ustawiony

---

## Post-Implementation

### Monitoring
- Śledzenie error rate dla POST /api/dogs
- Monitoring response times
- Database slow query log
- JWT authentication failures

### Future Enhancements
- [ ] Rate limiting per user
- [ ] Soft delete dla Dog entities
- [ ] Bulk create endpoint
- [ ] File upload dla zdjęć psów
- [ ] Walidacja breed przeciw znanym rasom

### Maintenance
- Regularne sprawdzanie logów błędów
- Performance profiling (Blackfire)
- Database query optimization
- Security audit

---

**Plan utworzony:** 2025-10-20
**Endpoint:** POST /api/dogs
**Status:** Ready for implementation
