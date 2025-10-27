# Prompt: Generowanie Command i Query Models dla Symfony (CQRS)

Jesteś wykwalifikowanym programistą PHP/Symfony, którego zadaniem jest stworzenie biblioteki klas Command i Query dla aplikacji Symfony zgodnie z wzorcem CQRS (Command Query Responsibility Segregation).

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

Plan API zawiera zdefiniowane endpointy do zmapowania na Commands (zapis) i Queries (odczyt).

---

## Wzorzec CQRS w aplikacji

### Podstawy CQRS

**CQRS (Command Query Responsibility Segregation)** rozdziela operacje zapisu (Commands) od operacji odczytu (Queries).

**Korzyści:**
- Jasny podział odpowiedzialności
- Łatwiejsze testowanie
- Lepsza skalowalność
- Przejrzysty przepływ danych

### Symfony Messenger Buses

Aplikacja używa trzech osobnych busów:
- **command.bus** - operacje zapisu (CREATE, UPDATE, DELETE)
- **query.bus** - operacje odczytu (GET, LIST)
- **event.bus** - zdarzenia domenowe (opcjonalnie)

### Commands (api/src/Action/Command/)

**Charakterystyka:**
- Reprezentują operacje zapisu
- Przykłady: `RegisterUserCommand`, `CreateDogCommand`, `UpdateDogCommand`
- **Readonly** (immutable)
- NIE zawierają logiki biznesowej
- Przetwarzane przez dedykowane CommandHandlers
- Mogą zwracać wartość lub void
- Nazwa kończy się słowem "Command"

**Przepływ:**
```
Controller → Request DTO → Command → command.bus → CommandHandler → Entity → Response DTO
```

### Queries (api/src/Action/Query/)

**Charakterystyka:**
- Reprezentują operacje odczytu
- Przykłady: `GetDogQuery`, `ListDogsQuery`
- **Readonly** (immutable)
- NIE modyfikują stanu
- Przetwarzane przez dedykowane QueryHandlers
- Zawsze zwracają wartość
- Nazwa kończy się słowem "Query"

**Przepływ:**
```
Controller → Query → query.bus → QueryHandler → Entities/DTOs → Response DTO
```

---

## Wymagania techniczne

### PHP i Symfony
- PHP 8.3 z `declare(strict_types=1);`
- **Constructor property promotion**
- **Readonly** dla wszystkich Commands i Queries
- **Typed properties**
- Atrybuty PHP zamiast adnotacji

### Zasady projektowania

**1. Immutability**
```php
final readonly class CreateDogCommand
{
    // Właściwości readonly - nie mogą być zmienione
}
```

**2. Single Responsibility**
- Jeden Command/Query = jedna operacja

**3. Brak logiki biznesowej**
- Commands i Queries to tylko "kontenery na dane"
- Logika w Handlerach

**4. Nazewnictwo**
- Command: `{Action}{Resource}Command` (np. `CreateDogCommand`)
- Query: `{Action}{Resource}Query` (np. `GetDogQuery`)

---

## Struktura katalogów

```
api/src/Action/
├── Command/
│   ├── Auth/
│   │   ├── RegisterUserCommand.php
│   │   ├── RegisterUserCommandHandler.php
│   │   ├── LoginCommand.php
│   │   └── LoginCommandHandler.php
│   ├── Dog/
│   │   ├── CreateDogCommand.php
│   │   ├── CreateDogCommandHandler.php
│   │   ├── UpdateDogCommand.php
│   │   ├── UpdateDogCommandHandler.php
│   │   ├── DeleteDogCommand.php
│   │   └── DeleteDogCommandHandler.php
│   └── AdviceCard/
│       ├── CreateAdviceCardCommand.php
│       ├── CreateAdviceCardCommandHandler.php
│       ├── RateAdviceCardCommand.php
│       └── RateAdviceCardCommandHandler.php
└── Query/
    ├── Dog/
    │   ├── GetDogQuery.php
    │   ├── GetDogQueryHandler.php
    │   ├── ListDogsQuery.php
    │   └── ListDogsQueryHandler.php
    ├── Category/
    │   ├── GetCategoryQuery.php
    │   ├── GetCategoryQueryHandler.php
    │   ├── ListCategoriesQuery.php
    │   └── ListCategoriesQueryHandler.php
    └── AdviceCard/
        ├── GetAdviceCardQuery.php
        ├── GetAdviceCardQueryHandler.php
        ├── ListAdviceCardsQuery.php
        └── ListAdviceCardsQueryHandler.php
```

**UWAGA:** Handlery w tym samym katalogu co Command/Query!

---

## Szablon Command

```php
<?php

declare(strict_types=1);

namespace App\Action\Command\{Namespace};

/**
 * Komenda: {opis operacji}
 *
 * Command jest przetwarzany przez {Name}CommandHandler,
 * który wykonuje logikę biznesową {operacji}.
 */
final readonly class {Name}Command
{
    public function __construct(
        public string $userId,
        public string $property1,
        public int $property2,
        // ... pozostałe właściwości
    ) {
    }
}
```

---

## Szablon Query

```php
<?php

declare(strict_types=1);

namespace App\Action\Query\{Namespace};

/**
 * Zapytanie: {opis operacji}
 *
 * Query jest przetwarzana przez {Name}QueryHandler,
 * który pobiera dane z bazy danych.
 */
final readonly class {Name}Query
{
    public function __construct(
        public string $userId,
        public ?string $filter1 = null,
        public bool $includeDeleted = false,
        // ... pozostałe parametry
    ) {
    }
}
```

---

## Przykład kompletnego Command

```php
<?php

declare(strict_types=1);

namespace App\Action\Command\Dog;

/**
 * Komenda: Utworzenie nowego profilu psa
 *
 * Command jest przetwarzany przez CreateDogCommandHandler,
 * który wykonuje logikę biznesową tworzenia psa.
 *
 * Przepływ:
 * 1. Controller waliduje CreateDogRequestDTO
 * 2. Controller tworzy CreateDogCommand z danych DTO
 * 3. Command jest wysyłany do command.bus
 * 4. CreateDogCommandHandler tworzy nową encję Dog
 * 5. Handler zapisuje Dog do bazy danych
 * 6. Handler zwraca utworzoną encję Dog
 */
final readonly class CreateDogCommand
{
    public function __construct(
        public string $userId,
        public string $name,
        public string $breed,
        public int $ageMonths,
        public string $gender,
        public string $weightKg,  // STRING dla DECIMAL precision
        public string $energyLevel,
    ) {
    }
}
```

---

## Przykład kompletnego Query

```php
<?php

declare(strict_types=1);

namespace App\Action\Query\Dog;

/**
 * Zapytanie: Pobranie listy psów należących do użytkownika
 *
 * Query jest przetwarzana przez ListDogsQueryHandler,
 * który pobiera listę psów z bazy danych.
 *
 * Przepływ:
 * 1. Controller tworzy ListDogsQuery z userId
 * 2. Query jest wysyłana do query.bus
 * 3. ListDogsQueryHandler pobiera psy z repository
 * 4. Handler filtruje psy według userId i includeDeleted
 * 5. Handler zwraca tablicę encji Dog
 */
final readonly class ListDogsQuery
{
    public function __construct(
        public string $userId,
        public bool $includeDeleted = false,
    ) {
    }
}
```

---

## Przykład Query z paginacją

```php
<?php

declare(strict_types=1);

namespace App\Action\Query\AdviceCard;

/**
 * Zapytanie: Pobranie listy kart porad z filtrowaniem i paginacją
 *
 * Query jest przetwarzana przez ListAdviceCardsQueryHandler,
 * który pobiera przefiltrowane i stronicowane karty porad.
 */
final readonly class ListAdviceCardsQuery
{
    public function __construct(
        public string $userId,
        public ?string $dogId = null,
        public ?string $categoryId = null,
        public ?string $adviceType = null,
        public int $page = 1,
        public int $limit = 20,
    ) {
    }
}
```

---

## Mapowanie endpointów na Commands i Queries

**Pełne mapowanie wszystkich endpointów znajduje się w `.ai/api-plan.md`**

### Przykładowe mapowanie (skrócone)

| Endpoint | HTTP | Type | Class |
|----------|------|------|-------|
| POST /api/auth/register | POST | Command | RegisterUserCommand |
| POST /api/auth/login | POST | Command | LoginCommand |
| GET /api/dogs | GET | Query | ListDogsQuery |
| GET /api/dogs/{id} | GET | Query | GetDogQuery |
| POST /api/dogs | POST | Command | CreateDogCommand |
| PUT /api/dogs/{id} | PUT | Command | UpdateDogCommand |
| DELETE /api/dogs/{id} | DELETE | Command | DeleteDogCommand |
| GET /api/categories | GET | Query | ListCategoriesQuery |
| POST /api/advice-cards | POST | Command | CreateAdviceCardCommand |
| PATCH /api/advice-cards/{id}/rating | PATCH | Command | RateAdviceCardCommand |

**Szczegóły:** Zobacz sekcję "2. Endpoints" w `.ai/api-plan.md`

---

## Dodatkowe wskazówki

### Namespace
- Commands: `App\Action\Command\{Resource}\{Name}Command`
- Command Handlers: `App\Action\Command\{Resource}\{Name}CommandHandler`
- Queries: `App\Action\Query\{Resource}\{Name}Query`
- Query Handlers: `App\Action\Query\{Resource}\{Name}QueryHandler`

### Typy danych
- UUID: `string`
- Daty: `\DateTimeImmutable`
- Decimal (np. weightKg): `string` w Command (konwersja do float w Handler)
- Boolean: `bool`
- Arrays: `array`
- Nullable: `?string`, `?int` (dla opcjonalnych filtrów)

### Wartości domyślne
Używaj wartości domyślnych dla opcjonalnych parametrów (głównie w Queries):

```php
final readonly class ListDogsQuery
{
    public function __construct(
        public string $userId,
        public bool $includeDeleted = false,
        public int $page = 1,
        public int $limit = 20,
    ) {
    }
}
```

### Walidacja
Commands i Queries **NIE zawierają** walidacji:
- Walidacja danych wejściowych → Request DTOs
- Walidacja logiki biznesowej → Handlers
- Commands/Queries to "czyste" obiekty danych

### ID użytkownika
Większość Commands i Queries powinna zawierać `userId`:
- Dla autoryzacji (sprawdzenie dostępu do zasobu)
- ID pobierane z JWT tokena w Controller

### Użycie w Controller

```php
public function createDog(
    CreateDogRequestDTO $requestDTO,
    MessageBusInterface $commandBus,
    Security $security
): JsonResponse {
    $userId = $security->getUser()->getId();

    $command = new CreateDogCommand(
        userId: $userId,
        name: $requestDTO->name,
        breed: $requestDTO->breed,
        ageMonths: $requestDTO->ageMonths,
        gender: $requestDTO->gender,
        weightKg: (string) $requestDTO->weightKg,  // float → string
        energyLevel: $requestDTO->energyLevel,
    );

    $dog = $commandBus->dispatch($command);

    return new JsonResponse(
        DogResponseDTO::fromEntity($dog),
        Response::HTTP_CREATED
    );
}
```

---

## Proces wykonania

### Krok 1: Analiza w bloku <cqrs_analysis>

1. Wymień wszystkie endpointy z `.ai/api-plan.md`
2. Dla każdego endpointu:
   - Określ, czy to Command czy Query
   - Zidentyfikuj parametry
   - Wskaż, czy zwraca wartość
   - Określ odpowiedni Handler

### Krok 2: Generowanie klas

Wygeneruj klasy zgodnie z szablonami powyżej.

---

## Końcowe sprawdzenie

- ✅ POST, PUT, PATCH, DELETE mają Commands
- ✅ GET mają Queries
- ✅ Commands/Queries są readonly
- ✅ Commands/Queries NIE zawierają logiki
- ✅ Commands/Queries NIE zawierają walidacji
- ✅ Struktura katalogów zgodna
- ✅ `declare(strict_types=1);`
- ✅ Namespace poprawne
- ✅ Commands zawierają `userId`

---

## Różnice: Commands vs Queries

| Aspekt | Commands | Queries |
|--------|----------|---------|
| Cel | Zmiana stanu | Odczyt danych |
| Operacje | CREATE, UPDATE, DELETE | GET, LIST |
| Bus | command.bus | query.bus |
| Zwracana wartość | Entity, void, ID | Entity, Entity[], DTO |
| Parametry | Dane do zapisu + userId | Kryteria + userId |
| Side effects | TAK (zmienia DB) | NIE (tylko odczyt) |

---

## Podsumowanie

Po wykonaniu będziesz miał:
- **~10-12 Commands** dla operacji zapisu
- **~8-10 Queries** dla operacji odczytu

Wszystkie klasy:
- Zgodne z PHP 8.3
- Constructor property promotion
- Readonly (immutable)
- CQRS pattern
- Proste obiekty danych
- Gotowe do przetwarzania przez Handlers

**Referencje:**
- Endpointy: `.ai/api-plan.md`
- Encje: `api/src/Entity/`
- DTOs: `.ai/symfony-dto.md`

**Następny krok:** Implementacja Handlers w tym samym katalogu co Command/Query
