# API Endpoint Implementation Plan: GET /api/dogs

## 1. Przegląd punktu końcowego

**Cel:** Pobranie listy wszystkich psów przypisanych do uwierzytelnionego użytkownika.

**Funkcjonalność:**
- JWT authentication (ROLE_USER required)
- CQRS pattern: Query parameters → Query → QueryHandler → Repository → DogResponseDTO[]
- Auto-filtrowanie po userId z JWT (bezpieczeństwo)
- Opcjonalne uwzględnienie usuniętych psów (soft-deleted)

**Szczegóły API:** Zobacz sekcję `GET /api/dogs` w `.ai/api-plan.md` (linie 122-164)

---

## 2. Wykorzystywane typy

### Query Parameter DTO
**Klasa:** `App\DTO\Request\Dog\ListDogsQueryDTO`
- Readonly class z opcjonalnym parametrem `includeDeleted`
- Wzorzec w `.ai/symfony-dto.md`

### Response DTO
**Klasa:** `App\DTO\Response\Dog\DogResponseDTO`
- Static factory: `fromEntity(Dog $dog): self`
- Ten sam DTO co w POST /api/dogs
- Wzorzec w `.ai/symfony-dto.md`

### CQRS Query
**Klasa:** `App\Action\Query\Dog\ListDogsQuery`
- Readonly class
- Właściwości: userId (z JWT), includeDeleted (z query params)
- Wzorzec w `.ai/symfony-cqrs.md`

### QueryHandler
**Klasa:** `App\Action\Query\Dog\ListDogsQueryHandler`
- `#[AsMessageHandler]`
- Delegacja do DogRepository → findByUser() → return Dog[]
- Wzorzec w `.ai/symfony-cqrs.md`

---

## 3. Przepływ danych

```
Client (GET /api/dogs?includeDeleted=false)
  ↓
Controller::list()
  ↓ query params → DTO
ListDogsQueryDTO
  ↓ + userId z JWT
ListDogsQuery
  ↓ dispatch to query.bus
ListDogsQueryHandler
  ↓
DogRepository::findByUser(userId, includeDeleted)
  ↓ SELECT * FROM dog WHERE user_id = ? AND deleted_at IS NULL
Dog[] (array of entities)
  ↓ array_map(DogResponseDTO::fromEntity)
DogResponseDTO[]
  ↓
JsonResponse (200 OK) {"data": [...]}
```

**Queries:**
1. SELECT Dog WHERE user_id = ? [AND deleted_at IS NULL]

---

## 4. Względy bezpieczeństwa

### Authentication
- JWT token required (LexikJWTAuthenticationBundle)
- `#[IsGranted('ROLE_USER')]` na kontrolerze
- userId **ZAWSZE** z tokena: `$security->getUser()->getId()`

### Authorization
```php
// ✅ POPRAWNIE
$query = new ListDogsQuery(
    userId: $security->getUser()->getId(),  // Z JWT
    includeDeleted: $queryDTO->includeDeleted ?? false
);

// ❌ PODATNOŚĆ NA ATAK
$query = new ListDogsQuery(
    userId: $request->query->get('userId'),  // NIGDY!
    includeDeleted: true
);
```

### Data filtering
- Zwracaj TYLKO psy przypisane do zalogowanego użytkownika
- WHERE user_id = authenticated_user.id (w repository)
- Doctrine automatycznie używa prepared statements (SQL injection prevention)

---

## 5. Obsługa błędów

| Status | Scenariusz | Response |
|--------|-----------|----------|
| 200 | Success | `{"data": [Dog[], ...]}` |
| 401 | Missing/invalid JWT | `{"error": "unauthorized", "message": "..."}` |

**Format odpowiedzi success:**
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
    }
  ]
}
```

**Pusta lista:**
```json
{
  "data": []
}
```

---

## 6. Etapy wdrożenia

### Krok 1: Query Parameter DTO
**Plik:** `api/src/DTO/Request/Dog/ListDogsQueryDTO.php`
- Readonly class
- Opcjonalny parametr: `includeDeleted` (bool, default: false)
- Wzorzec w `.ai/symfony-dto.md`

```php
<?php

declare(strict_types=1);

namespace App\DTO\Request\Dog;

final readonly class ListDogsQueryDTO
{
    public function __construct(
        public ?bool $includeDeleted = false,
    ) {
    }
}
```

### Krok 2: Response DTO
**Plik:** `api/src/DTO/Response/Dog/DogResponseDTO.php`
- **Już istnieje** z POST /api/dogs
- Jeśli nie istnieje, utwórz zgodnie z `.ai/symfony-dto.md`

### Krok 3: CQRS Query
**Plik:** `api/src/Action/Query/Dog/ListDogsQuery.php`
```php
<?php

declare(strict_types=1);

namespace App\Action\Query\Dog;

final readonly class ListDogsQuery
{
    public function __construct(
        public string $userId,
        public bool $includeDeleted = false,
    ) {
    }
}
```

### Krok 4: QueryHandler
**Plik:** `api/src/Action/Query/Dog/ListDogsQueryHandler.php`
```php
<?php

declare(strict_types=1);

namespace App\Action\Query\Dog;

use App\Entity\Dog;
use App\Repository\DogRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ListDogsQueryHandler
{
    public function __construct(
        private DogRepository $dogRepository,
    ) {
    }

    /**
     * @return Dog[]
     */
    public function __invoke(ListDogsQuery $query): array
    {
        return $this->dogRepository->findByUser(
            userId: $query->userId,
            includeDeleted: $query->includeDeleted
        );
    }
}
```

### Krok 5: Repository Method
**Plik:** `api/src/Repository/DogRepository.php`
```php
/**
 * @return Dog[]
 */
public function findByUser(string $userId, bool $includeDeleted = false): array
{
    $qb = $this->createQueryBuilder('d')
        ->where('d.user = :userId')
        ->setParameter('userId', $userId)
        ->orderBy('d.createdAt', 'DESC');

    if (!$includeDeleted) {
        $qb->andWhere('d.deletedAt IS NULL');
    }

    return $qb->getQuery()->getResult();
}
```

### Krok 6: Controller
**Plik:** `api/src/Controller/DogController.php`
```php
#[Route('/api/dogs', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
public function list(
    Request $request,
    MessageBusInterface $queryBus,
    Security $security,
): JsonResponse {
    // 1. Parse query parameters
    $includeDeleted = $request->query->getBoolean('includeDeleted', false);

    // 2. Create query
    $query = new ListDogsQuery(
        userId: $security->getUser()->getId(),
        includeDeleted: $includeDeleted
    );

    // 3. Dispatch query
    /** @var Dog[] $dogs */
    $dogs = $queryBus->dispatch($query);

    // 4. Map to DTOs
    $dogDTOs = array_map(
        fn(Dog $dog) => DogResponseDTO::fromEntity($dog),
        $dogs
    );

    // 5. Response
    return $this->json(['data' => $dogDTOs]);
}
```

---

## 7. Unit Tests

### Test Structure

**Test directories:**
- `api/tests/Unit/Action/Query/Dog/ListDogsQueryHandlerTest.php`
- `api/tests/Unit/Repository/DogRepositoryTest.php` (opcjonalnie)

### 7.1 ListDogsQueryHandler Tests

**File:** `api/tests/Unit/Action/Query/Dog/ListDogsQueryHandlerTest.php`

**Test cases:**

1. **testInvokeReturnsUserDogsExcludingDeleted**
   - Setup:
     - Mock DogRepository::findByUser() to return array of 2 Dog entities
     - includeDeleted = false
   - Execute: Call `__invoke()` with ListDogsQuery
   - Assert:
     - Repository method called with correct userId and includeDeleted=false
     - Returns array with 2 Dog entities
     - Returned dogs match mocked entities

2. **testInvokeReturnsEmptyArrayWhenUserHasNoDogs**
   - Setup: Mock DogRepository::findByUser() to return empty array
   - Execute: Call `__invoke()`
   - Assert: Returns empty array

3. **testInvokeIncludesDeletedDogsWhenRequested**
   - Setup:
     - Mock DogRepository::findByUser() to return 3 dogs (including deleted)
     - includeDeleted = true
   - Execute: Call `__invoke()` with includeDeleted=true
   - Assert:
     - Repository called with includeDeleted=true
     - Returns all 3 dogs

**Example test structure:**
```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Action\Query\Dog;

use App\Action\Query\Dog\ListDogsQuery;
use App\Action\Query\Dog\ListDogsQueryHandler;
use App\Entity\Dog;
use App\Repository\DogRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ListDogsQueryHandlerTest extends TestCase
{
    private ListDogsQueryHandler $handler;
    private DogRepository|MockObject $dogRepository;

    protected function setUp(): void
    {
        $this->dogRepository = $this->createMock(DogRepository::class);
        $this->handler = new ListDogsQueryHandler($this->dogRepository);
    }

    public function testInvokeReturnsUserDogsExcludingDeleted(): void
    {
        $dog1 = new Dog();
        $dog1->setName('Rex');

        $dog2 = new Dog();
        $dog2->setName('Luna');

        $this->dogRepository
            ->expects($this->once())
            ->method('findByUser')
            ->with('user-uuid-123', false)
            ->willReturn([$dog1, $dog2]);

        $query = new ListDogsQuery(
            userId: 'user-uuid-123',
            includeDeleted: false
        );

        $result = ($this->handler)($query);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertSame($dog1, $result[0]);
        $this->assertSame($dog2, $result[1]);
    }

    public function testInvokeReturnsEmptyArrayWhenUserHasNoDogs(): void
    {
        $this->dogRepository
            ->expects($this->once())
            ->method('findByUser')
            ->with('user-uuid-456', false)
            ->willReturn([]);

        $query = new ListDogsQuery(
            userId: 'user-uuid-456',
            includeDeleted: false
        );

        $result = ($this->handler)($query);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testInvokeIncludesDeletedDogsWhenRequested(): void
    {
        $dog1 = new Dog();
        $dog2 = new Dog();
        $dog3 = new Dog();

        $this->dogRepository
            ->expects($this->once())
            ->method('findByUser')
            ->with('user-uuid-789', true)
            ->willReturn([$dog1, $dog2, $dog3]);

        $query = new ListDogsQuery(
            userId: 'user-uuid-789',
            includeDeleted: true
        );

        $result = ($this->handler)($query);

        $this->assertCount(3, $result);
    }
}
```

**Coverage target:** 100%

### 7.2 Integration Test (Optional)

**File:** `api/tests/Functional/Controller/DogControllerListTest.php`

**Test cases:**

1. **testListDogsReturnsUserDogsOnly**
   - Setup: Create 2 users, each with 2 dogs
   - Execute: Authenticate as user1, GET /api/dogs
   - Assert: Returns only user1's dogs (2 dogs)

2. **testListDogsExcludesDeletedByDefault**
   - Setup: User has 2 active dogs and 1 deleted dog
   - Execute: GET /api/dogs
   - Assert: Returns only 2 active dogs

3. **testListDogsIncludesDeletedWhenRequested**
   - Setup: User has 2 active dogs and 1 deleted dog
   - Execute: GET /api/dogs?includeDeleted=true
   - Assert: Returns all 3 dogs

4. **testListDogsReturns401WhenUnauthenticated**
   - Execute: GET /api/dogs without JWT token
   - Assert: 401 status code

### 7.3 Test Execution

**Run all unit tests:**
```bash
docker compose exec php vendor/bin/phpunit tests/Unit/
```

**Run specific test file:**
```bash
docker compose exec php vendor/bin/phpunit tests/Unit/Action/Query/Dog/ListDogsQueryHandlerTest.php
```

**Run with coverage:**
```bash
docker compose exec php vendor/bin/phpunit --coverage-html coverage/ tests/Unit/
```

### 7.4 Testing Best Practices

**Mocking guidelines:**
- Mock DogRepository to control return values
- Use PHPUnit's `createMock()` for simple mocks
- Verify method calls with `expects($this->once())`
- Use `willReturn()` for different scenarios (empty array, multiple dogs, etc.)

**Assertion patterns:**
- Assert return type is array
- Verify array count matches expected number of dogs
- Test edge cases: empty results, deleted dogs
- Verify userId is correctly passed to repository

**Test isolation:**
- Each test should be independent
- Use `setUp()` for common initialization
- Don't rely on test execution order
- Mock all dependencies

**Coverage requirements:**
- QueryHandler: 100% (straightforward logic)
- Overall unit test coverage: minimum 80%

---

## 8. Potencjalne problemy

| Problem | Rozwiązanie |
|---------|-------------|
| User nie ma żadnych psów | Zwróć `{"data": []}` (200 OK) |
| Repository query fails | Exception → 500, logged |
| Deleted dogs visible without permission | Zawsze sprawdź deletedAt w WHERE clause gdy includeDeleted=false |
| Handler nie znaleziony | Upewnij się że ma `#[AsMessageHandler]` |
| Query bus not configured | Sprawdź `config/packages/messenger.yaml` dla query.bus |

---

**Status:** Ready for implementation
**Plan utworzony:** 2025-10-30
**Referencje:** `.ai/api-plan.md`, `.ai/db-plan.md`, `.ai/symfony-dto.md`, `.ai/symfony-cqrs.md`
