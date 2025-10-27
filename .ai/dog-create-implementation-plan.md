# API Endpoint Implementation Plan: POST /api/dogs

## 1. Przegląd punktu końcowego

**Cel:** Utworzenie nowego profilu psa przypisanego do uwierzytelnionego użytkownika.

**Funkcjonalność:**
- Walidacja danych zgodnie z regułami z api-plan.md
- JWT authentication (ROLE_USER required)
- CQRS pattern: Request DTO → Command → CommandHandler → Factory → Entity
- Auto-przypisanie psa do zalogowanego użytkownika (userId z JWT)

**Szczegóły API:** Zobacz sekcję `POST /api/dogs` w `.ai/api-plan.md`

---

## 2. Wykorzystywane typy

### Request DTO
**Klasa:** `App\DTO\Request\Dog\CreateDogRequestDTO`
- Readonly class z pełną walidacją
- Wzorzec w `.ai/symfony-dto.md`

### Response DTO
**Klasa:** `App\DTO\Response\Dog\DogResponseDTO`
- Static factory: `fromEntity(Dog $dog): self`
- Wzorzec w `.ai/symfony-dto.md`

### CQRS Command
**Klasa:** `App\Action\Command\Dog\CreateDogCommand`
- Readonly class
- Właściwości: userId (z JWT), name, breed, ageMonths, gender, weightKg (string!), energyLevel
- Wzorzec w `.ai/symfony-cqrs.md`

### CommandHandler
**Klasa:** `App\Action\Command\Dog\CreateDogCommandHandler`
- `#[AsMessageHandler]`
- Delegacja do DogFactory → persist → flush → return Dog
- Wzorzec w `.ai/symfony-cqrs.md`

### Factory
**Klasa:** `App\Factory\DogFactory`
- Metoda: `createFromCommand(CreateDogCommand $command): Dog`
- Logika: pobierz User → utwórz Dog → przypisz właściwości

---

## 3. Przepływ danych

```
Client (JSON)
  ↓
Controller::create()
  ↓ deserializacja + walidacja
CreateDogRequestDTO
  ↓ + userId z JWT
CreateDogCommand
  ↓ dispatch to command.bus
CreateDogCommandHandler
  ↓
DogFactory::createFromCommand()
  ↓ UserRepository->find(userId)
  ↓ new Dog() + setters
Dog entity
  ↓ persist + flush
Database INSERT
  ↓
DogResponseDTO::fromEntity()
  ↓
JsonResponse (201 Created)
```

**Queries:**
1. SELECT User by ID (z JWT)
2. INSERT Dog

---

## 4. Względy bezpieczeństwa

### Authentication
- JWT token required (LexikJWTAuthenticationBundle)
- `#[IsGranted('ROLE_USER')]` na kontrolerze
- userId **ZAWSZE** z tokena: `$security->getUser()->getId()`

### Authorization
```php
// ✅ POPRAWNIE
$command = new CreateDogCommand(
    userId: $security->getUser()->getId(),  // Z JWT
    // ...
);

// ❌ PODATNOŚĆ NA ATAK
$command = new CreateDogCommand(
    userId: $requestDTO->userId,  // NIGDY!
    // ...
);
```

### Walidacja
- Symfony Validator w Request DTO (constraints)
- SQL Injection prevented (Doctrine prepared statements)
- XSS prevented (React/JSON API)

---

## 5. Obsługa błędów

| Status | Scenariusz | Response |
|--------|-----------|----------|
| 201 | Success | Dog profile JSON |
| 400 | Validation failed | `{"error": "validation_failed", "violations": [...]}` |
| 401 | Missing/invalid JWT | `{"error": "unauthorized", "message": "..."}` |
| 500 | DB error, User not found | `{"error": "internal_server_error", "message": "..."}` |

**Format błędów walidacji:**
```json
{
  "error": "validation_failed",
  "message": "Invalid input data",
  "violations": [
    {"field": "name", "message": "Name cannot be blank."}
  ]
}
```

**Environment-aware errors:**
- Dev: szczegółowe stack traces
- Production: generyczne komunikaty

---

## 6. Etapy wdrożenia

### Krok 1: Request DTO
**Plik:** `api/src/DTO/Request/Dog/CreateDogRequestDTO.php`
- Readonly class
- Pełna walidacja: NotBlank, Length, Range, Choice
- Wzorzec w `.ai/symfony-dto.md`

### Krok 2: Response DTO
**Plik:** `api/src/DTO/Response/Dog/DogResponseDTO.php`
- `fromEntity(Dog $dog): self`
- Konwersja weightKg: string → float
- Wzorzec w `.ai/symfony-dto.md`

### Krok 3: CQRS Command
**Plik:** `api/src/Action/Command/Dog/CreateDogCommand.php`
- `final readonly class`
- weightKg jako **string** (DECIMAL precision)
- Wzorzec w `.ai/symfony-cqrs.md`

### Krok 4: DogFactory
**Plik:** `api/src/Factory/DogFactory.php`
```php
public function createFromCommand(CreateDogCommand $command): Dog
{
    $user = $this->userRepository->find($command->userId);
    if (!$user) {
        throw new \RuntimeException('User not found');
    }

    $dog = new Dog();
    $dog->setUser($user);
    $dog->setName($command->name);
    // ... remaining setters

    return $dog;
}
```

### Krok 5: CommandHandler
**Plik:** `api/src/Action/Command/Dog/CreateDogCommandHandler.php`
```php
#[AsMessageHandler]
class CreateDogCommandHandler
{
    public function __invoke(CreateDogCommand $command): Dog
    {
        $dog = $this->dogFactory->createFromCommand($command);
        $this->entityManager->persist($dog);
        $this->entityManager->flush();
        return $dog;
    }
}
```

### Krok 6: Controller
**Plik:** `api/src/Controller/DogController.php`
```php
#[Route('/api/dogs', methods: ['POST'])]
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
            'violations' => $this->formatValidationErrors($errors),
        ], 400);
    }

    // 3. Command
    $command = new CreateDogCommand(
        userId: $security->getUser()->getId(),
        name: $requestDTO->name,
        breed: $requestDTO->breed,
        ageMonths: $requestDTO->ageMonths,
        gender: $requestDTO->gender,
        weightKg: (string) $requestDTO->weightKg,  // float → string
        energyLevel: $requestDTO->energyLevel,
    );

    // 4. Dispatch
    $dog = $commandBus->dispatch($command);

    // 5. Response
    return $this->json(
        DogResponseDTO::fromEntity($dog),
        201
    );
}
```

## 7. Unit Tests

### Test Structure

**Test directories:**
- `api/tests/Unit/Factory/DogFactoryTest.php`
- `api/tests/Unit/Action/Command/Dog/CreateDogCommandHandlerTest.php`
- `api/tests/Unit/DTO/Response/Dog/DogResponseDTOTest.php`

### 7.1 DogFactory Tests

**File:** `api/tests/Unit/Factory/DogFactoryTest.php`

**Test cases:**

1. **testCreateFromCommandSuccess**
   - Setup: Mock UserRepository to return valid User entity
   - Execute: Call `createFromCommand()` with valid CreateDogCommand
   - Assert:
     - Dog entity is created
     - All properties match command values
     - User is correctly assigned to Dog
     - weightKg is stored as string

2. **testCreateFromCommandThrowsExceptionWhenUserNotFound**
   - Setup: Mock UserRepository to return null
   - Execute: Call `createFromCommand()`
   - Assert: RuntimeException is thrown with message "User not found"

**Example test structure:**
```php
class DogFactoryTest extends TestCase
{
    private DogFactory $factory;
    private UserRepository|MockObject $userRepository;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->factory = new DogFactory($this->userRepository);
    }

    public function testCreateFromCommandSuccess(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with('user-uuid-123')
            ->willReturn($user);

        $command = new CreateDogCommand(
            userId: 'user-uuid-123',
            name: 'Rex',
            breed: 'German Shepherd',
            ageMonths: 24,
            gender: 'male',
            weightKg: '35.50',
            energyLevel: 'high'
        );

        $dog = $this->factory->createFromCommand($command);

        $this->assertInstanceOf(Dog::class, $dog);
        $this->assertSame('Rex', $dog->getName());
        $this->assertSame('German Shepherd', $dog->getBreed());
        $this->assertSame(24, $dog->getAgeMonths());
        $this->assertSame('male', $dog->getGender());
        $this->assertSame('35.50', $dog->getWeightKg());
        $this->assertSame('high', $dog->getEnergyLevel());
        $this->assertSame($user, $dog->getUser());
    }
}
```

**Coverage target:** 100% (2 test cases cover all code paths)

### 7.2 CreateDogCommandHandler Tests

**File:** `api/tests/Unit/Action/Command/Dog/CreateDogCommandHandlerTest.php`

**Test cases:**

1. **testInvokeSuccess**
   - Setup:
     - Mock DogFactory to return Dog entity
     - Mock EntityManager persist() and flush()
   - Execute: Call `__invoke()` with CreateDogCommand
   - Assert:
     - Factory method was called with correct command
     - persist() was called with Dog entity
     - flush() was called once
     - Correct Dog entity is returned

**Example test structure:**
```php
class CreateDogCommandHandlerTest extends TestCase
{
    private CreateDogCommandHandler $handler;
    private DogFactory|MockObject $dogFactory;
    private EntityManagerInterface|MockObject $entityManager;

    protected function setUp(): void
    {
        $this->dogFactory = $this->createMock(DogFactory::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->handler = new CreateDogCommandHandler(
            $this->dogFactory,
            $this->entityManager
        );
    }

    public function testInvokeSuccess(): void
    {
        $command = new CreateDogCommand(
            userId: 'user-uuid-123',
            name: 'Rex',
            breed: 'German Shepherd',
            ageMonths: 24,
            gender: 'male',
            weightKg: '35.50',
            energyLevel: 'high'
        );

        $dog = new Dog();
        $dog->setName('Rex');

        $this->dogFactory
            ->expects($this->once())
            ->method('createFromCommand')
            ->with($command)
            ->willReturn($dog);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($dog);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = ($this->handler)($command);

        $this->assertSame($dog, $result);
    }
}
```

**Coverage target:** 100% (handler logic is straightforward)

### 7.3 DogResponseDTO Tests

**File:** `api/tests/Unit/DTO/Response/Dog/DogResponseDTOTest.php`

**Test cases:**

1. **testFromEntityMapsAllProperties**
   - Setup: Create Dog entity with all properties set
   - Execute: Call `DogResponseDTO::fromEntity($dog)`
   - Assert:
     - All properties are correctly mapped
     - weightKg is converted from string to float
     - UUID is returned as string
     - Timestamps are preserved

2. **testFromEntityConvertsWeightKgStringToFloat**
   - Setup: Create Dog with weightKg = '35.50'
   - Execute: Call `fromEntity()`
   - Assert: DTO weightKg is float 35.5

**Example test structure:**
```php
class DogResponseDTOTest extends TestCase
{
    public function testFromEntityMapsAllProperties(): void
    {
        $user = new User();
        $createdAt = new \DateTimeImmutable('2025-01-15 10:00:00');
        $updatedAt = new \DateTimeImmutable('2025-01-15 12:00:00');

        $dog = new Dog();
        $dog->setUser($user);
        $dog->setName('Rex');
        $dog->setBreed('German Shepherd');
        $dog->setAgeMonths(24);
        $dog->setGender('male');
        $dog->setWeightKg('35.50');
        $dog->setEnergyLevel('high');

        // Mock getId() if using reflection or set via constructor
        $reflection = new \ReflectionClass($dog);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($dog, '650e8400-e29b-41d4-a716-446655440001');

        $dto = DogResponseDTO::fromEntity($dog);

        $this->assertSame('650e8400-e29b-41d4-a716-446655440001', $dto->id);
        $this->assertSame('Rex', $dto->name);
        $this->assertSame('German Shepherd', $dto->breed);
        $this->assertSame(24, $dto->ageMonths);
        $this->assertSame('male', $dto->gender);
        $this->assertSame(35.5, $dto->weightKg);
        $this->assertIsFloat($dto->weightKg);
        $this->assertSame('high', $dto->energyLevel);
    }

    public function testFromEntityConvertsWeightKgStringToFloat(): void
    {
        $dog = new Dog();
        $dog->setWeightKg('42.75');

        $dto = DogResponseDTO::fromEntity($dog);

        $this->assertIsFloat($dto->weightKg);
        $this->assertSame(42.75, $dto->weightKg);
    }
}
```

**Coverage target:** 100%

### 7.4 Test Execution

**Run all unit tests:**
```bash
docker compose exec php vendor/bin/phpunit tests/Unit/
```

**Run specific test file:**
```bash
docker compose exec php vendor/bin/phpunit tests/Unit/Factory/DogFactoryTest.php
```

**Run with coverage:**
```bash
docker compose exec php vendor/bin/phpunit --coverage-html coverage/ tests/Unit/
```

### 7.5 Testing Best Practices

**Mocking guidelines:**
- Mock all external dependencies (repositories, entity manager)
- Use PHPUnit's `createMock()` for simple mocks
- Verify method calls with `expects($this->once())`
- Use `willReturn()` for return values

**Assertion patterns:**
- Always assert return types (`assertInstanceOf`, `assertIsFloat`)
- Verify all entity properties after creation
- Test both success and error paths
- Use meaningful test method names (test + WhatIsBeingTested + ExpectedOutcome)

**Test isolation:**
- Each test should be independent
- Use `setUp()` for common initialization
- Don't rely on test execution order
- Mock time-dependent values (dates, timestamps)

**Coverage requirements:**
- Factory: 100% (all branches tested)
- CommandHandler: 100% (straightforward logic)
- ResponseDTO: 100% (all properties verified)
- Overall unit test coverage: minimum 80%

---

## 8. Potencjalne problemy

| Problem | Rozwiązanie |
|---------|-------------|
| User z tokena nie istnieje | RuntimeException w Factory → 500 |
| Doctrine flush() fails | Exception → 500, logged |
| weightKg precision loss | Użyj string w Command/Entity, float w DTOs |
| Handler nie znaleziony | Upewnij się że ma `#[AsMessageHandler]` |


---

**Status:** Ready for implementation
**Plan utworzony:** 2025-10-20
**Referencje:** `.ai/api-plan.md`, `.ai/db-plan.md`, `.ai/symfony-dto.md`, `.ai/symfony-cqrs.md`
