# Prompt: Generowanie DTO (Data Transfer Objects) dla Symfony

Jesteś wykwalifikowanym programistą PHP/Symfony, którego zadaniem jest stworzenie biblioteki klas DTO (Data Transfer Object) dla aplikacji Symfony. Twoim zadaniem jest przeanalizowanie definicji encji Doctrine i planu API, a następnie utworzenie odpowiednich klas Request DTO i Response DTO.

## Dane wejściowe

### 1. Encje Doctrine
```
api/src/Entity
```

**Dostępne encje:** User, Dog, ProblemCategory, AdviceCard

### 2. Plan API
```
@.ai/api-plan.md
```

Plan API zawiera zdefiniowane endpointy, struktury request/response oraz reguły walidacji.

---

## Wymagania techniczne

### PHP i Symfony
- PHP 8.3 z `declare(strict_types=1);`
- **Constructor property promotion**
- **Readonly properties** dla Request DTOs (immutable)
- **Typed properties** dla wszystkich właściwości
- Atrybuty PHP zamiast adnotacji

### Request DTOs
- **Readonly** (immutable)
- Pełna walidacja Symfony Validator
- Deserializowane z JSON przez Symfony Serializer
- NIE zawierają logiki biznesowej

### Response DTOs
- Zwykłe klasy (nie readonly)
- Static factory methods: `fromEntity()`
- NIE zawierają wrażliwych danych (hashe haseł, tokeny)

---

## Struktura katalogów

```
api/src/
└── DTO/
    ├── Request/
    │   ├── Auth/
    │   │   ├── RegisterUserRequestDTO.php
    │   │   └── LoginRequestDTO.php
    │   ├── Dog/
    │   │   ├── CreateDogRequestDTO.php
    │   │   └── UpdateDogRequestDTO.php
    │   └── AdviceCard/
    │       ├── CreateAdviceCardRequestDTO.php
    │       └── RateAdviceCardRequestDTO.php
    └── Response/
        ├── Auth/
        │   ├── AuthResponseDTO.php
        │   └── UserResponseDTO.php
        ├── Dog/
        │   └── DogResponseDTO.php
        ├── Category/
        │   └── CategoryResponseDTO.php
        └── AdviceCard/
            ├── AdviceCardDetailResponseDTO.php
            └── AdviceCardListItemResponseDTO.php
```

---

## Walidacja (Request DTOs)

### Podstawowe constrainty

```php
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\NotBlank]
#[Assert\Email]
#[Assert\Length(max: 255)]
#[Assert\Range(min: 0, max: 300)]
#[Assert\Choice(choices: ['male', 'female'])]
#[Assert\Type('string')]
#[Assert\Uuid]
#[Assert\Valid]  // dla zagnieżdżonych obiektów
```

---

## Szablon Request DTO

```php
<?php

declare(strict_types=1);

namespace App\DTO\Request\{Namespace};

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Request DTO dla {operacja}
 */
final readonly class {Name}RequestDTO
{
    public function __construct(
        #[Assert\NotBlank(message: 'Email cannot be blank.')]
        #[Assert\Email(message: 'Invalid email format.')]
        #[Assert\Length(max: 255, maxMessage: 'Email cannot be longer than {{ limit }} characters.')]
        public string $email,

        #[Assert\NotBlank(message: 'Password cannot be blank.')]
        #[Assert\Length(
            min: 8,
            minMessage: 'Password must be at least {{ limit }} characters long.'
        )]
        public string $password,
    ) {
    }
}
```

---

## Szablon Response DTO

```php
<?php

declare(strict_types=1);

namespace App\DTO\Response\{Namespace};

use App\Entity\{EntityName};

/**
 * Response DTO dla {zasób}
 */
class {Name}ResponseDTO
{
    public function __construct(
        public string $id,
        public string $email,
        public bool $isActive,
        public \DateTimeImmutable $createdAt,
    ) {
    }

    /**
     * Tworzy Response DTO z encji
     */
    public static function fromEntity({EntityName} $entity): self
    {
        return new self(
            id: $entity->getId(),
            email: $entity->getEmail(),
            isActive: $entity->getIsActive(),
            createdAt: $entity->getCreatedAt(),
        );
    }

    /**
     * Tworzy tablicę Response DTOs z tablicy encji
     */
    public static function fromEntities(array $entities): array
    {
        return array_map(
            fn({EntityName} $entity): self => self::fromEntity($entity),
            $entities
        );
    }
}
```

---

## Przykład kompletnego Request DTO

```php
<?php

declare(strict_types=1);

namespace App\DTO\Request\Dog;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Request DTO dla tworzenia profilu psa
 */
final readonly class CreateDogRequestDTO
{
    public function __construct(
        #[Assert\NotBlank(message: 'Dog name cannot be blank.')]
        #[Assert\Length(
            min: 1,
            max: 100,
            minMessage: 'Dog name must be at least {{ limit }} character long.',
            maxMessage: 'Dog name cannot be longer than {{ limit }} characters.'
        )]
        public string $name,

        #[Assert\NotBlank(message: 'Breed cannot be blank.')]
        #[Assert\Length(
            max: 100,
            maxMessage: 'Breed cannot be longer than {{ limit }} characters.'
        )]
        public string $breed,

        #[Assert\NotNull(message: 'Age in months is required.')]
        #[Assert\Type(type: 'integer', message: 'Age must be an integer.')]
        #[Assert\Range(
            min: 0,
            max: 300,
            notInRangeMessage: 'Age must be between {{ min }} and {{ max }} months.'
        )]
        public int $ageMonths,

        #[Assert\NotBlank(message: 'Gender cannot be blank.')]
        #[Assert\Choice(
            choices: ['male', 'female'],
            message: 'Gender must be either "male" or "female".'
        )]
        public string $gender,

        #[Assert\NotNull(message: 'Weight is required.')]
        #[Assert\Type(type: 'numeric', message: 'Weight must be a number.')]
        #[Assert\Range(
            min: 0.01,
            max: 200.00,
            notInRangeMessage: 'Weight must be between {{ min }} and {{ max }} kg.'
        )]
        public float $weightKg,

        #[Assert\NotBlank(message: 'Energy level cannot be blank.')]
        #[Assert\Choice(
            choices: ['very_low', 'low', 'medium', 'high', 'very_high'],
            message: 'Energy level must be one of: {{ choices }}.'
        )]
        public string $energyLevel,
    ) {
    }
}
```

---

## Przykład kompletnego Response DTO

```php
<?php

declare(strict_types=1);

namespace App\DTO\Response\Dog;

use App\Entity\Dog;

/**
 * Response DTO dla profilu psa
 */
class DogResponseDTO
{
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
    ) {
    }

    /**
     * Tworzy Response DTO z encji Dog
     */
    public static function fromEntity(Dog $dog): self
    {
        return new self(
            id: $dog->getId(),
            name: $dog->getName(),
            breed: $dog->getBreed(),
            ageMonths: $dog->getAgeMonths(),
            gender: $dog->getGender(),
            weightKg: (float) $dog->getWeightKg(),  // string → float dla JSON
            energyLevel: $dog->getEnergyLevel(),
            createdAt: $dog->getCreatedAt(),
            updatedAt: $dog->getUpdatedAt(),
        );
    }

    /**
     * Tworzy tablicę Response DTOs z tablicy encji
     */
    public static function fromEntities(array $dogs): array
    {
        return array_map(
            fn(Dog $dog): self => self::fromEntity($dog),
            $dogs
        );
    }
}
```

---

## Typy danych

- **UUID**: `string` (walidacja przez `#[Assert\Uuid]`)
- **Daty**: `\DateTimeImmutable`
- **Decimal** (np. weightKg):
  - Request DTO: `float` (deserializacja z JSON)
  - Response DTO: `float` (konwersja z string entity)
- **Boolean**: `bool`
- **Arrays/JSONB**: `array`

---

## Mapowanie endpointów na DTOs

**Pełne mapowanie wszystkich endpointów znajduje się w `.ai/api-plan.md`**

### Przykładowe mapowanie (skrócone)

| Endpoint | Request DTO | Response DTO |
|----------|-------------|--------------|
| POST /api/auth/register | RegisterUserRequestDTO | AuthResponseDTO |
| POST /api/auth/login | LoginRequestDTO | AuthResponseDTO |
| POST /api/dogs | CreateDogRequestDTO | DogResponseDTO |
| PUT /api/dogs/{id} | UpdateDogRequestDTO | DogResponseDTO |
| GET /api/dogs | - | DogListResponseDTO |
| POST /api/advice-cards | CreateAdviceCardRequestDTO | AdviceCardDetailResponseDTO |
| PATCH /api/advice-cards/{id}/rating | RateAdviceCardRequestDTO | AdviceCardRatingResponseDTO |

**Szczegóły:** Zobacz sekcję "2. Endpoints" w `.ai/api-plan.md`

---

## Dodatkowe wskazówki

### Namespace
- Request DTOs: `App\DTO\Request\{Resource}\{Name}RequestDTO`
- Response DTOs: `App\DTO\Response\{Resource}\{Name}ResponseDTO`

### Walidacja custom
Dla złożonych reguł użyj `#[Assert\Callback]`:

```php
#[Assert\Callback]
public function validate(ExecutionContextInterface $context): void
{
    // Custom validation logic
}
```

### Użycie w Controller
```php
public function create(
    Request $request,
    SerializerInterface $serializer,
    ValidatorInterface $validator,
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

    // 3. Użycie DTO
    // ...
}
```

---

## Proces wykonania

### Krok 1: Analiza w bloku <dto_analysis>

1. Wymień wszystkie endpointy z `.ai/api-plan.md`
2. Dla każdego endpointu:
   - Zidentyfikuj Request DTO (jeśli endpoint przyjmuje body)
   - Zidentyfikuj Response DTO
   - Wskaż odpowiednie encje Doctrine
   - Opisz reguły walidacji

### Krok 2: Generowanie klas

Wygeneruj klasy zgodnie z szablonami powyżej.

---

## Końcowe sprawdzenie

- ✅ Wszystkie endpointy mają odpowiednie DTOs
- ✅ Request DTOs są readonly
- ✅ Request DTOs mają pełną walidację zgodną z `.ai/api-plan.md`
- ✅ Response DTOs mają metody `fromEntity()` i `fromEntities()`
- ✅ Struktura katalogów zgodna z konwencją
- ✅ `declare(strict_types=1);` w każdym pliku
- ✅ Response DTOs NIE zawierają wrażliwych danych

---

## Podsumowanie

Po wykonaniu będziesz miał:
- **~6-8 Request DTOs** z pełną walidacją
- **~10-12 Response DTOs** ze static factory methods

Wszystkie DTOs:
- Zgodne z PHP 8.3
- Constructor property promotion
- Request DTOs: readonly
- Response DTOs: z `fromEntity()`
- Bez wrażliwych danych w Response
- Messages w języku angielskim

**Referencje:**
- Endpointy i reguły walidacji: `.ai/api-plan.md`
- Encje: `api/src/Entity/`
- Commands/Queries: `.ai/symfony-cqrs.md`
