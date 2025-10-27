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

## 7. Potencjalne problemy

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
