# Prompt: Generowanie DTO (Data Transfer Objects) dla Symfony

Jesteś wykwalifikowanym programistą PHP/Symfony, którego zadaniem jest stworzenie biblioteki klas DTO (Data Transfer Object) dla aplikacji Symfony. Twoim zadaniem jest przeanalizowanie definicji encji Doctrine i planu API, a następnie utworzenie odpowiednich klas Request DTO i Response DTO, które dokładnie reprezentują struktury danych wymagane przez API, zachowując jednocześnie połączenie z podstawowymi encjami bazy danych.

## Dane wejściowe

Najpierw dokładnie przejrzyj następujące dane wejściowe:

### 1. Encje Doctrine

```
api/src/Entity
```

**Dostępne encje:**
- `App\Entity\User` - użytkownik aplikacji
- `App\Entity\Dog` - profil psa należący do użytkownika
- `App\Entity\ProblemCategory` - kategoria problemu treningowego
- `App\Entity\AdviceCard` - karta z poradą AI i planem treningowym

### 2. Plan API

```
@.ai/api-plan.md
```

Plan API zawiera zdefiniowane endpointy, struktury request/response oraz reguły walidacji.

### 3. Commands i Queries (CQRS)

```
@.ai/symfony-cqrs-prompt.md
```

**Uwaga:** Commands i Queries są w oddzielnym prompcie. Niniejszy prompt skupia się wyłącznie na DTOs (Request i Response).

## Zadanie

Twoim zadaniem jest utworzenie definicji klas PHP dla:

1. **Request DTOs** - obiekty przyjmujące dane z żądań HTTP (w katalogu `api/src/DTO/Request/`)
2. **Response DTOs** - obiekty do serializacji odpowiedzi API (w katalogu `api/src/DTO/Response/`)

## Wymagania techniczne

### PHP i Symfony

- Używaj PHP 8.3 z włączonym `declare(strict_types=1);`
- Wykorzystuj **constructor property promotion** dla zwięzłego kodu
- Używaj **readonly properties** dla niemutowalnych obiектów (Commands, Queries, Request DTOs)
- Stosuj **typed properties** dla wszystkich właściwości
- Używaj atrybutów PHP zamiast adnotacji

### Walidacja (Request DTOs)

Request DTOs muszą zawierać reguły walidacji przy użyciu atrybutów Symfony Validator:

```php
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\NotBlank]
#[Assert\Email]
#[Assert\Length(max: 255)]
private string $email;
```

**Najważniejsze constrainty:**
- `#[Assert\NotBlank]` - pole nie może być puste
- `#[Assert\NotNull]` - pole nie może być null
- `#[Assert\Email]` - walidacja formatu email
- `#[Assert\Length(min: X, max: Y)]` - długość tekstu
- `#[Assert\Range(min: X, max: Y)]` - zakres liczby
- `#[Assert\Choice(choices: ['value1', 'value2'])]` - enum
- `#[Assert\Type('string')]` - typ danych
- `#[Assert\Uuid]` - walidacja UUID
- `#[Assert\Valid]` - walidacja zagnieżdżonych obiektów

### Request DTOs

Request DTOs są używane do przyjmowania i walidacji danych z żądań HTTP:
- Powinny być **readonly** (immutable)
- Zawierają pełną walidację przy użyciu atrybutów Symfony Validator
- Są deserializowane z JSON request body przez Symfony Serializer
- NIE zawierają logiki biznesowej
- Są przekazywane do Controllers, które tworzą z nich Commands (dla zapisu) lub bezpośrednio wykorzystują w Queries

### Response DTOs

Response DTOs są używane do serializacji odpowiedzi API:
- Powinny być zwykłymi klasami (nie readonly, bo są konstruowane przez builder/mapper)
- Zawierają tylko dane do wysłania do klienta
- NIE zawierają wrażliwych danych (np. hashy haseł)
- Mogą zawierać dane z wielu encji (np. join dog name do advice card)

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
            ├── AdviceCardResponseDTO.php
            ├── AdviceCardListItemResponseDTO.php
            └── AdviceCardDetailResponseDTO.php
```

**Uwaga:** Commands i Queries znajdują się w oddzielnych katalogach - patrz `@.ai/symfony-cqrs-prompt.md`

## Proces wykonania

### Krok 1: Analiza w bloku <dto_analysis>

Przed utworzeniem ostatecznego wyniku, pracuj wewnątrz tagów `<dto_analysis>` w swoim bloku myślenia, aby pokazać swój proces myślowy. W swojej analizie:

1. **Wymień wszystkie endpointy z planu API**, numerując każdy z nich
2. **Dla każdego endpointu:**
   - Zidentyfikuj Request DTO (jeśli endpoint przyjmuje body)
   - Zidentyfikuj Response DTO
   - Wskaż odpowiednie encje Doctrine, z których czerpać dane
   - Opisz reguły walidacji dla Request DTO
3. **Zaplanuj strukturę klas:**
   - Request DTOs z pełną walidacją (readonly)
   - Response DTOs jako zwykłe klasy ze static factory methods

### Krok 2: Generowanie klas

Po przeprowadzeniu analizy, wygeneruj klasy PHP zgodnie z poniższymi szablonami:

#### Szablon Request DTO

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

#### Szablon Response DTO

```php
<?php

declare(strict_types=1);

namespace App\DTO\Response\{Namespace};

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
}
```

## Mapowanie endpointów na DTOs

**Uwaga:** Pełne mapowanie Commands i Queries znajduje się w `@.ai/symfony-cqrs-prompt.md`. Poniżej skupiamy się tylko na DTOs.

### Authentication (POST /api/auth/register)

**Request DTO:** `RegisterUserRequestDTO`
- email (string, required, email format, max 255)
- password (string, required, min 8)

**Response DTO:** `AuthResponseDTO`
- token (string)
- user (UserResponseDTO)

---

### Authentication (POST /api/auth/login)

**Request DTO:** `LoginRequestDTO`
- email (string, required, email format)
- password (string, required)

**Response DTO:** `AuthResponseDTO`
- token (string)
- user (UserResponseDTO)

---

### Dogs (GET /api/dogs)

**Response DTO:** `DogListResponseDTO`
- data (array of DogResponseDTO)

---

### Dogs (GET /api/dogs/{id})

**Response DTO:** `DogResponseDTO`
- id, name, breed, ageMonths, gender, weightKg, energyLevel, createdAt, updatedAt

---

### Dogs (POST /api/dogs)

**Request DTO:** `CreateDogRequestDTO`
- name (string, required, 1-100 chars)
- breed (string, required, max 100 chars)
- ageMonths (int, required, 0-300)
- gender (string, required, Choice: male/female)
- weightKg (float, required, 0.01-200.00)
- energyLevel (string, required, Choice: very_low, low, medium, high, very_high)

**Response DTO:** `DogResponseDTO`

---

### Dogs (PUT /api/dogs/{id})

**Request DTO:** `UpdateDogRequestDTO`
- name, breed, ageMonths, gender, weightKg, energyLevel (same as CreateDogRequestDTO)

**Response DTO:** `DogResponseDTO`

---

### Dogs (DELETE /api/dogs/{id})

**Response:** 204 No Content (brak Response DTO)

---

### Categories (GET /api/categories)

**Response DTO:** `CategoryListResponseDTO`
- data (array of CategoryResponseDTO)

---

### Categories (GET /api/categories/{id})

**Response DTO:** `CategoryResponseDTO`
- id, code, name, priority, isActive

---

### Advice Cards (POST /api/advice-cards)

**Request DTO:** `CreateAdviceCardRequestDTO`
- dogId (string, required, UUID)
- categoryId (string, required, UUID)
- problemDescription (string, required, 10-2000 chars)
- adviceType (string, required, Choice: quick, plan_7_days)

**Response DTO:** `AdviceCardDetailResponseDTO`
- id, dogId, categoryId, problemDescription, aiResponse, planContent, adviceType, rating, createdAt, updatedAt

---

### Advice Cards (GET /api/advice-cards)

**Response DTO:** `AdviceCardPaginatedResponseDTO`
- data (array of AdviceCardListItemResponseDTO)
- meta (PaginationMetaDTO)

**AdviceCardListItemResponseDTO:**
- id, dogId, dogName, categoryId, categoryName, problemDescription, adviceType, rating, createdAt

---

### Advice Cards (GET /api/advice-cards/{id})

**Response DTO:** `AdviceCardDetailResponseDTO`
- id
- dog (DogResponseDTO - embedded)
- category (CategoryResponseDTO - embedded)
- problemDescription, aiResponse, planContent, adviceType, rating, createdAt, updatedAt

---

### Advice Cards (PATCH /api/advice-cards/{id}/rating)

**Request DTO:** `RateAdviceCardRequestDTO`
- rating (string, required, Choice: helpful, not_helpful)

**Response DTO:** `AdviceCardRatingResponseDTO`
- id, rating, updatedAt

## Dodatkowe wskazówki

### Użycie namespace

Organizuj klasy w odpowiednie namespace:
- Request DTOs: `App\DTO\Request\{Resource}\{Name}RequestDTO`
- Response DTOs: `App\DTO\Response\{Resource}\{Name}ResponseDTO`

**Uwaga:** Namespace dla Commands i Queries znajdują się w `@.ai/symfony-cqrs-prompt.md`

### Typy danych

- UUID: `string` (będzie walidowane przez `#[Assert\Uuid]`)
- Daty: `\DateTimeImmutable` (preferowane nad DateTime)
- Decimal (np. weightKg): `string` w Request DTO, `float` w Command/Response
- Boolean: `bool`
- Arrays/JSONB: `array`

### Walidacja custom

Dla złożonych reguł walidacji (np. risk keywords detection), możesz utworzyć custom constraint:

```php
#[Assert\Callback]
public function validate(ExecutionContextInterface $context): void
{
    // Custom validation logic
}
```

### Static factory methods

Response DTOs powinny mieć static factory methods dla wygody:

```php
public static function fromEntity(Dog $dog): self
{
    return new self(
        id: $dog->getId(),
        name: $dog->getName(),
        // ...
    );
}

public static function fromEntities(array $dogs): array
{
    return array_map(fn(Dog $dog) => self::fromEntity($dog), $dogs);
}
```

## Końcowe sprawdzenie

Przed wygenerowaniem klas upewnij się, że:

1. ✅ Wszystkie endpointy z api-plan.md mają odpowiednie Request/Response DTOs
2. ✅ Każde Request DTO ma pełną walidację zgodną z planem API
3. ✅ Request DTOs są readonly i nie zawierają logiki
4. ✅ Response DTOs mają metody `fromEntity()` i `fromEntities()`
5. ✅ Struktura katalogów jest zgodna z konwencją (DTO/Request/ i DTO/Response/)
6. ✅ Wszystkie klasy używają `declare(strict_types=1);`
7. ✅ Namespace są poprawne
8. ✅ Typy właściwości są odpowiednie dla encji Doctrine
9. ✅ Response DTOs NIE zawierają wrażliwych danych (hashy haseł, tokeny, etc.)

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
            weightKg: (float) $dog->getWeightKg(),
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

## Podsumowanie

Po wykonaniu tego zadania będziesz miał:

- **~6-8 Request DTOs** z pełną walidacją (dla endpointów POST, PUT, PATCH)
- **~10-12 Response DTOs** dla serializacji odpowiedzi (dla wszystkich endpointów zwracających dane)

Wszystkie DTOs będą:
- Zgodne z PHP 8.3
- Wykorzystywały nowoczesne features (readonly dla Request DTOs, constructor promotion)
- Request DTOs: readonly z pełną walidacją Symfony Validator
- Response DTOs: zwykłe klasy ze static factory methods `fromEntity()`
- Połączone z encjami Doctrine
- Kompletnie zwalidowane (Request DTOs)
- Bez wrażliwych danych (Response DTOs)

**Uwaga:** Commands i Queries są w oddzielnym prompcie: `@.ai/symfony-cqrs-prompt.md`

---

**Teraz rozpocznij analizę w tagu `<dto_analysis>` i wygeneruj wszystkie wymagane DTOs!**
