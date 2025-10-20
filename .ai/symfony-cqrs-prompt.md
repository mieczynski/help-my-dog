# Prompt: Generowanie Command i Query Models dla Symfony (CQRS)

Jesteś wykwalifikowanym programistą PHP/Symfony, którego zadaniem jest stworzenie biblioteki klas Command i Query dla aplikacji Symfony zgodnie z wzorcem CQRS (Command Query Responsibility Segregation). Twoim zadaniem jest przeanalizowanie definicji encji Doctrine, planu API oraz Request DTOs, a następnie utworzenie odpowiednich klas Command i Query, które reprezentują intencje operacji w aplikacji.

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

Plan API zawiera zdefiniowane endpointy, które należy zmapować na Commands (operacje zapisu) i Queries (operacje odczytu).

### 3. Request DTOs (opcjonalnie)

```
@.ai/symfony-dto-prompt.md
```

Request DTOs definiują dane wejściowe z API, które często są przekazywane do Commands.

## Zadanie

Twoim zadaniem jest utworzenie definicji klas PHP dla:

1. **Commands** - obiekty komend dla operacji zapisu zgodnie z CQRS (w katalogu `api/src/Command/`)
2. **Queries** - obiekty zapytań dla operacji odczytu zgodnie z CQRS (w katalogu `api/src/Query/`)

## Wzorzec CQRS w aplikacji

### Podstawy CQRS

**CQRS (Command Query Responsibility Segregation)** to wzorzec architektury, który rozdziela operacje zapisu (Commands) od operacji odczytu (Queries).

**Korzyści:**
- Jasny podział odpowiedzialności
- Łatwiejsze testowanie
- Lepsza skalowalność
- Przejrzysty przepływ danych

### Symfony Messenger Buses

Aplikacja używa trzech osobnych busów Symfony Messenger:

- **command.bus** - dla operacji zapisu (CREATE, UPDATE, DELETE)
- **query.bus** - dla operacji odczytu (GET, LIST)
- **event.bus** - dla zdarzeń domenowych (opcjonalnie)

### Commands (api/src/Command/)

**Definicja:**
Commands reprezentują intencję zmiany stanu aplikacji.

**Charakterystyka:**
- Reprezentują operacje zapisu: tworzenie, aktualizacja, usuwanie
- Przykłady: `RegisterUserCommand`, `CreateDogCommand`, `UpdateDogCommand`, `DeleteDogCommand`
- Powinny być **readonly** (immutable) - raz utworzone nie mogą być modyfikowane
- NIE zawierają logiki biznesowej - tylko dane
- Są przetwarzane przez dedykowane **Command Handlers**
- Mogą zwracać wartość (np. ID utworzonego zasobu) lub nie zwracać nic
- Nazwa powinna kończyć się słowem "Command"

**Przepływ:**
1. Controller otrzymuje Request DTO
2. Controller tworzy Command z danych Request DTO
3. Command jest wysyłany do `command.bus`
4. CommandHandler przetwarza Command i wykonuje logikę biznesową
5. CommandHandler zwraca wynik (lub void)
6. Controller tworzy Response DTO i zwraca odpowiedź

### Queries (api/src/Query/)

**Definicja:**
Queries reprezentują intencję odczytu danych z aplikacji.

**Charakterystyka:**
- Reprezentują operacje odczytu: pobieranie pojedynczego zasobu, listowanie
- Przykłady: `GetDogQuery`, `ListDogsQuery`, `GetCategoryQuery`
- Powinny być **readonly** (immutable)
- NIE modyfikują stanu aplikacji - tylko odczytują dane
- Są przetwarzane przez dedykowane **Query Handlers**
- Zawsze zwracają wartość (encję, kolekcję, DTO)
- Nazwa powinna kończyć się słowem "Query"

**Przepływ:**
1. Controller tworzy Query (często bez Request DTO, tylko z parametrów URL)
2. Query jest wysyłana do `query.bus`
3. QueryHandler przetwarza Query i pobiera dane z bazy
4. QueryHandler zwraca encję/encje lub DTO
5. Controller tworzy Response DTO i zwraca odpowiedź

## Wymagania techniczne

### PHP i Symfony

- Używaj PHP 8.3 z włączonym `declare(strict_types=1);`
- Wykorzystuj **constructor property promotion** dla zwięzłego kodu
- Używaj **readonly** dla wszystkich Commands i Queries (immutable objects)
- Stosuj **typed properties** dla wszystkich właściwości
- Używaj atrybutów PHP zamiast adnotacji

### Zasady projektowania Commands i Queries

**1. Immutability (Niezmienność)**
```php
final readonly class CreateDogCommand
{
    // Właściwości są readonly, więc nie mogą być zmienione po utworzeniu
}
```

**2. Single Responsibility**
- Jeden Command/Query = jedna operacja
- NIE twórz "wielozadaniowych" Commands/Queries

**3. Brak logiki biznesowej**
- Commands i Queries są tylko "kontenerami na dane"
- Cała logika biznesowa powinna być w Handlerach

**4. Właściwe nazewnictwo**
- Command: `{Action}{Resource}Command` np. `CreateDogCommand`, `UpdateDogCommand`
- Query: `{Action}{Resource}Query` np. `GetDogQuery`, `ListDogsQuery`

**5. Parametry Commands vs Queries**
- **Command**: zawiera dane potrzebne do wykonania operacji + ID użytkownika
- **Query**: zawiera kryteria wyszukiwania + ID użytkownika (dla autoryzacji)

## Struktura katalogów

```
api/src/
├── Command/
│   ├── Auth/
│   │   ├── RegisterUserCommand.php
│   │   └── LoginCommand.php
│   ├── Dog/
│   │   ├── CreateDogCommand.php
│   │   ├── UpdateDogCommand.php
│   │   └── DeleteDogCommand.php
│   └── AdviceCard/
│       ├── CreateAdviceCardCommand.php
│       └── RateAdviceCardCommand.php
└── Query/
    ├── Dog/
    │   ├── GetDogQuery.php
    │   └── ListDogsQuery.php
    ├── Category/
    │   ├── GetCategoryQuery.php
    │   └── ListCategoriesQuery.php
    └── AdviceCard/
        ├── GetAdviceCardQuery.php
        └── ListAdviceCardsQuery.php
```

## Proces wykonania

### Krok 1: Analiza w bloku <cqrs_analysis>

Przed utworzeniem ostatecznego wyniku, pracuj wewnątrz tagów `<cqrs_analysis>` w swoim bloku myślenia, aby pokazać swój proces myślowy. W swojej analizie:

1. **Wymień wszystkie endpointy z planu API**, numerując każdy z nich
2. **Dla każdego endpointu:**
   - Określ, czy to operacja zapisu (Command) czy odczytu (Query)
   - Zidentyfikuj parametry potrzebne dla Command/Query
   - Wskaż, czy Command/Query zwraca wartość
   - Określ odpowiedni Handler, który będzie przetwarzał Command/Query
3. **Zaplanuj strukturę klas:**
   - Commands jako readonly obiekty z danymi do zapisu
   - Queries jako readonly obiekty z kryteriami wyszukiwania

### Krok 2: Generowanie klas

Po przeprowadzeniu analizy, wygeneruj klasy PHP zgodnie z poniższymi szablonami:

#### Szablon Command

```php
<?php

declare(strict_types=1);

namespace App\Command\{Namespace};

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

#### Szablon Query

```php
<?php

declare(strict_types=1);

namespace App\Query\{Namespace};

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

## Mapowanie endpointów na Commands i Queries

### Authentication

#### POST /api/auth/register

**Command:** `RegisterUserCommand`
```php
final readonly class RegisterUserCommand
{
    public function __construct(
        public string $email,
        public string $password,
    ) {
    }
}
```

**Handler zwraca:** `User` entity + JWT token

---

#### POST /api/auth/login

**Command:** `LoginCommand` (lub Query - login jest edge case)
```php
final readonly class LoginCommand
{
    public function __construct(
        public string $email,
        public string $password,
    ) {
    }
}
```

**Handler zwraca:** `User` entity + JWT token

**Uwaga:** Login jest edge case - może być Command (bo zmienia stan sesji) lub Query (bo tylko weryfikuje dane). W tym projekcie traktujemy jako Command.

---

### Dogs

#### GET /api/dogs

**Query:** `ListDogsQuery`
```php
final readonly class ListDogsQuery
{
    public function __construct(
        public string $userId,
        public bool $includeDeleted = false,
    ) {
    }
}
```

**Handler zwraca:** `Dog[]` (array of Dog entities)

---

#### GET /api/dogs/{id}

**Query:** `GetDogQuery`
```php
final readonly class GetDogQuery
{
    public function __construct(
        public string $dogId,
        public string $userId,
    ) {
    }
}
```

**Handler zwraca:** `Dog` entity lub null

---

#### POST /api/dogs

**Command:** `CreateDogCommand`
```php
final readonly class CreateDogCommand
{
    public function __construct(
        public string $userId,
        public string $name,
        public string $breed,
        public int $ageMonths,
        public string $gender,
        public string $weightKg,
        public string $energyLevel,
    ) {
    }
}
```

**Handler zwraca:** `Dog` entity (utworzony pies)

---

#### PUT /api/dogs/{id}

**Command:** `UpdateDogCommand`
```php
final readonly class UpdateDogCommand
{
    public function __construct(
        public string $dogId,
        public string $userId,
        public string $name,
        public string $breed,
        public int $ageMonths,
        public string $gender,
        public string $weightKg,
        public string $energyLevel,
    ) {
    }
}
```

**Handler zwraca:** `Dog` entity (zaktualizowany pies)

---

#### DELETE /api/dogs/{id}

**Command:** `DeleteDogCommand`
```php
final readonly class DeleteDogCommand
{
    public function __construct(
        public string $dogId,
        public string $userId,
    ) {
    }
}
```

**Handler zwraca:** `void` (204 No Content)

---

### Categories

#### GET /api/categories

**Query:** `ListCategoriesQuery`
```php
final readonly class ListCategoriesQuery
{
    public function __construct()
    {
        // Brak parametrów - zwraca wszystkie aktywne kategorie
    }
}
```

**Handler zwraca:** `ProblemCategory[]` (array of active categories)

---

#### GET /api/categories/{id}

**Query:** `GetCategoryQuery`
```php
final readonly class GetCategoryQuery
{
    public function __construct(
        public string $categoryId,
    ) {
    }
}
```

**Handler zwraca:** `ProblemCategory` entity lub null

---

### Advice Cards

#### POST /api/advice-cards

**Command:** `CreateAdviceCardCommand`
```php
final readonly class CreateAdviceCardCommand
{
    public function __construct(
        public string $userId,
        public string $dogId,
        public string $categoryId,
        public string $problemDescription,
        public string $adviceType,
    ) {
    }
}
```

**Handler zwraca:** `AdviceCard` entity (utworzona karta z AI response)

**Uwaga:** Handler musi:
1. Zweryfikować, czy pies należy do użytkownika
2. Sprawdzić risk keywords
3. Wywołać AI service
4. Zapisać wynik do bazy

---

#### GET /api/advice-cards

**Query:** `ListAdviceCardsQuery`
```php
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

**Handler zwraca:** `PaginatedResult` (custom class) zawierający:
- `AdviceCard[]` - lista kart
- Metadata paginacji (total, page, limit, totalPages)

---

#### GET /api/advice-cards/{id}

**Query:** `GetAdviceCardQuery`
```php
final readonly class GetAdviceCardQuery
{
    public function __construct(
        public string $adviceCardId,
        public string $userId,
    ) {
    }
}
```

**Handler zwraca:** `AdviceCard` entity z relacjami (dog, category) lub null

---

#### PATCH /api/advice-cards/{id}/rating

**Command:** `RateAdviceCardCommand`
```php
final readonly class RateAdviceCardCommand
{
    public function __construct(
        public string $adviceCardId,
        public string $userId,
        public string $rating,
    ) {
    }
}
```

**Handler zwraca:** `AdviceCard` entity (zaktualizowana karta)

**Uwaga:** Handler musi sprawdzić, czy karta nie została już oceniona (immutability of rating).

---

## Dodatkowe wskazówki

### Użycie namespace

Organizuj klasy w odpowiednie namespace:
- Commands: `App\Command\{Resource}\{Name}Command`
- Queries: `App\Query\{Resource}\{Name}Query`

### Typy danych

- UUID: `string` (będzie walidowane w Request DTO lub Handler)
- Daty: `\DateTimeImmutable` (jeśli potrzebne)
- Decimal (np. weightKg): `string` (konwersja do float w Handler)
- Boolean: `bool`
- Arrays: `array`
- Nullable: `?string`, `?int` (dla opcjonalnych filtrów w Queries)

### Wartości domyślne

Używaj wartości domyślnych dla opcjonalnych parametrów (głównie w Queries):

```php
final readonly class ListDogsQuery
{
    public function __construct(
        public string $userId,
        public bool $includeDeleted = false,  // wartość domyślna
        public int $page = 1,
        public int $limit = 20,
    ) {
    }
}
```

### Walidacja

Commands i Queries **NIE zawierają** walidacji:
- Walidacja danych wejściowych jest w Request DTOs
- Walidacja logiki biznesowej jest w Handlerach
- Commands/Queries są "czystymi" obiektami danych

### ID użytkownika

Większość Commands i Queries powinna zawierać `userId`:
- Dla autoryzacji (sprawdzenie, czy użytkownik ma dostęp do zasobu)
- ID jest pobierane z JWT tokena w Controller

### Relacja z Request DTOs

**Typowy przepływ:**
1. Request DTO → walidacja danych wejściowych
2. Command/Query → utworzony z danych Request DTO + userId
3. Handler → wykonuje operację

**Przykład w Controller:**
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
        weightKg: (string) $requestDTO->weightKg,
        energyLevel: $requestDTO->energyLevel,
    );

    $dog = $commandBus->dispatch($command);

    return new JsonResponse(
        DogResponseDTO::fromEntity($dog),
        Response::HTTP_CREATED
    );
}
```

## Przykłady kompletnych klas

### Przykład Command

```php
<?php

declare(strict_types=1);

namespace App\Command\Dog;

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
        public string $weightKg,
        public string $energyLevel,
    ) {
    }
}
```

### Przykład Query

```php
<?php

declare(strict_types=1);

namespace App\Query\Dog;

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

### Przykład Query z paginacją

```php
<?php

declare(strict_types=1);

namespace App\Query\AdviceCard;

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

## Końcowe sprawdzenie

Przed wygenerowaniem klas upewnij się, że:

1. ✅ Wszystkie endpointy POST, PUT, PATCH, DELETE mają odpowiednie Commands
2. ✅ Wszystkie endpointy GET mają odpowiednie Queries
3. ✅ Commands/Queries są readonly (immutable)
4. ✅ Commands/Queries NIE zawierają logiki biznesowej
5. ✅ Commands/Queries NIE zawierają walidacji
6. ✅ Struktura katalogów jest zgodna z konwencją (Command/{Resource}/, Query/{Resource}/)
7. ✅ Wszystkie klasy używają `declare(strict_types=1);`
8. ✅ Namespace są poprawne
9. ✅ Typy właściwości są odpowiednie
10. ✅ Commands zawierają `userId` dla autoryzacji
11. ✅ Queries zawierają kryteria wyszukiwania i opcjonalne filtry

## Różnice: Commands vs Queries

| Aspekt | Commands | Queries |
|--------|----------|---------|
| Cel | Zmiana stanu | Odczyt danych |
| Operacje | CREATE, UPDATE, DELETE | GET, LIST |
| Bus | command.bus | query.bus |
| Zwracana wartość | Entity, void, lub ID | Entity, Entity[], DTO |
| Parametry | Dane do zapisu + userId | Kryteria wyszukiwania + userId |
| Side effects | TAK (zmienia bazę danych) | NIE (tylko odczyt) |
| Przykład | CreateDogCommand | GetDogQuery |

## Podsumowanie

Po wykonaniu tego zadania będziesz miał:

- **~10-12 Commands** dla operacji zapisu
  - Auth: RegisterUserCommand, LoginCommand
  - Dog: CreateDogCommand, UpdateDogCommand, DeleteDogCommand
  - AdviceCard: CreateAdviceCardCommand, RateAdviceCardCommand

- **~8-10 Queries** dla operacji odczytu
  - Dog: GetDogQuery, ListDogsQuery
  - Category: GetCategoryQuery, ListCategoriesQuery
  - AdviceCard: GetAdviceCardQuery, ListAdviceCardsQuery

Wszystkie klasy będą:
- Zgodne z PHP 8.3
- Wykorzystywały constructor property promotion
- Readonly (immutable)
- Zgodne z CQRS pattern
- Proste obiekty danych bez logiki biznesowej
- Gotowe do przetwarzania przez dedykowane Handlers

---

**Następny krok:** Implementacja Command Handlers i Query Handlers w katalogu `api/src/Handler/`.

**Teraz rozpocznij analizę w tagu `<cqrs_analysis>` i wygeneruj wszystkie wymagane Commands i Queries!**
