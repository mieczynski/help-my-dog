# Schemat bazy danych PostgreSQL - Help My Dog

## 1. Tabele z kolumnami, typami danych i ograniczeniami

### 1.1 Tabela: `user`

Przechowuje konta właścicieli psów dla autentykacji JWT (stateless).

| Kolumna         | Typ danych       | Ograniczenia                           | Opis                                          |
|-----------------|------------------|----------------------------------------|-----------------------------------------------|
| id              | UUID             | PRIMARY KEY                            | Unikalny identyfikator użytkownika            |
| email           | VARCHAR(255)     | NOT NULL, UNIQUE                       | Email użytkownika (identyfikator logowania)   |
| password_hash   | VARCHAR(255)     | NOT NULL, CHECK (LENGTH >= 60)         | Hash hasła (bcrypt)                           |
| is_active       | BOOLEAN          | NOT NULL, DEFAULT TRUE                 | Czy konto jest aktywne                        |
| created_at      | TIMESTAMP        | NOT NULL, DEFAULT CURRENT_TIMESTAMP    | Data utworzenia konta                         |
| updated_at      | TIMESTAMP        | NOT NULL, DEFAULT CURRENT_TIMESTAMP    | Data ostatniej aktualizacji                   |
| deleted_at      | TIMESTAMP        | NULL                                   | Data soft delete (NULL = aktywny)             |

**Decyzje projektowe:**
- Minimalizacja danych osobowych - brak pól imię/nazwisko (RODO compliance)
- Brak pól JWT tokens/refresh tokens (stateless authentication)
- Soft delete przez `deleted_at` dla możliwości odzyskania konta

**CHECK Constraints:**
```sql
CONSTRAINT check_user_email_format CHECK (email ~* '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}$')
CONSTRAINT check_user_password_hash_length CHECK (LENGTH(password_hash) >= 60)
```

---

### 1.2 Tabela: `dog`

Przechowuje profile psów użytkowników dla personalizacji porad AI.

| Kolumna         | Typ danych       | Ograniczenia                           | Opis                                          |
|-----------------|------------------|----------------------------------------|-----------------------------------------------|
| id              | UUID             | PRIMARY KEY                            | Unikalny identyfikator psa                    |
| user_id         | UUID             | NOT NULL, FOREIGN KEY → user(id)      | Właściciel psa                                |
| name            | VARCHAR(100)     | NOT NULL, CHECK (LENGTH >= 1)          | Imię psa                                      |
| breed           | VARCHAR(100)     | NOT NULL                               | Rasa lub "mieszaniec"                         |
| age_months      | INTEGER          | NOT NULL, CHECK (>= 0 AND <= 300)      | Wiek w miesiącach (0-300, max 25 lat)         |
| gender          | VARCHAR(10)      | NOT NULL, CHECK IN ('male', 'female')  | Płeć psa                                      |
| weight_kg       | DECIMAL(5,2)     | NOT NULL, CHECK (> 0 AND <= 200)       | Waga w kilogramach                            |
| energy_level    | VARCHAR(20)      | NOT NULL, CHECK IN (...)               | Poziom energii (5 wartości)                   |
| created_at      | TIMESTAMP        | NOT NULL, DEFAULT CURRENT_TIMESTAMP    | Data utworzenia profilu                       |
| updated_at      | TIMESTAMP        | NOT NULL, DEFAULT CURRENT_TIMESTAMP    | Data ostatniej aktualizacji                   |
| deleted_at      | TIMESTAMP        | NULL                                   | Data soft delete (NULL = aktywny)             |

**Decyzje projektowe:**
- Wiek przechowywany TYLKO w miesiącach dla dokładności (szczenięta) i prostoty
- Energy_level jako varchar z CHECK constraint (enum-like values)
- Soft delete przez `deleted_at`
- ON DELETE CASCADE - usunięcie użytkownika usuwa wszystkie jego psy

**CHECK Constraints:**
```sql
CONSTRAINT check_dog_name_length CHECK (LENGTH(name) >= 1 AND LENGTH(name) <= 100)
CONSTRAINT check_dog_age_months CHECK (age_months >= 0 AND age_months <= 300)
CONSTRAINT check_dog_weight_kg CHECK (weight_kg > 0 AND weight_kg <= 200.00)
CONSTRAINT check_dog_gender CHECK (gender IN ('male', 'female'))
CONSTRAINT check_dog_energy_level CHECK (energy_level IN ('very_low', 'low', 'medium', 'high', 'very_high'))
```

**Foreign Key:**
```sql
CONSTRAINT fk_dog_user_id FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
```

---

### 1.3 Tabela: `advice_card`

Przechowuje karty porad i planów treningowych wygenerowanych przez AI.

| Kolumna               | Typ danych       | Ograniczenia                              | Opis                                          |
|-----------------------|------------------|-------------------------------------------|-----------------------------------------------|
| id                    | UUID             | PRIMARY KEY                               | Unikalny identyfikator porady                 |
| dog_id                | UUID             | NOT NULL, FOREIGN KEY → dog(id)          | Pies, którego dotyczy porada                  |
| category_id           | UUID             | NOT NULL, FOREIGN KEY → problem_category(id) | Kategoria problemu                       |
| problem_description   | TEXT             | NOT NULL                                  | Oryginalny opis problemu od użytkownika       |
| ai_response           | TEXT             | NOT NULL                                  | Pełna odpowiedź AI (dla obu typów porad)      |
| plan_content          | JSONB            | NULL                                      | Plan 7-dniowy w formacie JSON (NULL dla quick)|
| advice_type           | VARCHAR(20)      | NOT NULL, CHECK IN (...)                  | Typ porady: 'quick' lub 'plan_7_days'         |
| rating                | VARCHAR(20)      | NULL, CHECK IN (...) gdy NOT NULL         | Ocena: 'helpful', 'not_helpful' lub NULL      |
| created_at            | TIMESTAMP        | NOT NULL, DEFAULT CURRENT_TIMESTAMP       | Data wygenerowania porady                     |
| updated_at            | TIMESTAMP        | NOT NULL, DEFAULT CURRENT_TIMESTAMP       | Data ostatniej aktualizacji                   |
| deleted_at            | TIMESTAMP        | NULL                                      | Data soft delete (NULL = aktywna)             |

**Decyzje projektowe:**
- `plan_content` JSONB ze strukturą: `{"days": [{"day": 1, "content": "..."}, {"day": 2, "content": "..."}, ...]}`
- `ai_response` zawiera pełną odpowiedź AI dla obu trybów (quick i plan_7_days)
- `rating` nullable - użytkownik może nie ocenić porady
- Soft delete przez `deleted_at`
- ON DELETE CASCADE dla dog → advice_card
- ON DELETE RESTRICT dla categories → advice_card (chroni przed usunięciem używanych kategorii)
- Brak metadanych AI (ai_model_used, response_time_ms, tokens_used, api_cost) - pominięte dla MVP

**CHECK Constraints:**
```sql
CONSTRAINT check_advice_card_advice_type CHECK (advice_type IN ('quick', 'plan_7_days'))
CONSTRAINT check_advice_card_rating CHECK (rating IS NULL OR rating IN ('helpful', 'not_helpful'))
CONSTRAINT check_advice_card_plan_content_when_plan CHECK (
  (advice_type = 'plan_7_days' AND plan_content IS NOT NULL) OR
  (advice_type = 'quick' AND plan_content IS NULL)
)
```

**Foreign Keys:**
```sql
CONSTRAINT fk_advice_card_dog_id FOREIGN KEY (dog_id) REFERENCES dog(id) ON DELETE CASCADE
CONSTRAINT fk_advice_card_category_id FOREIGN KEY (category_id) REFERENCES problem_category(id) ON DELETE RESTRICT
```

**Struktura JSONB dla plan_content:**
```json
{
  "days": [
    {
      "day": 1,
      "content": "**Cel dnia:** ...\n\n**Kroki:**\n1. ...\n\n**Kryterium sukcesu:** ...\n\n**Wskazówki:** ..."
    },
    {
      "day": 2,
      "content": "..."
    }
  ]
}
```

---

### 1.4 Tabela: `problem_category`

Lookup table dla kategorii problemów treningowych.

| Kolumna         | Typ danych       | Ograniczenia                           | Opis                                          |
|-----------------|------------------|----------------------------------------|-----------------------------------------------|
| id              | UUID             | PRIMARY KEY                            | Unikalny identyfikator kategorii              |
| code            | VARCHAR(50)      | NOT NULL, UNIQUE                       | Kod kategorii (np. 'behavior', 'tricks')      |
| name            | VARCHAR(100)     | NOT NULL                               | Nazwa wyświetlana w UI (po polsku)            |
| priority        | INTEGER          | NOT NULL, DEFAULT 0                    | Kolejność sortowania w interface              |
| is_active       | BOOLEAN          | NOT NULL, DEFAULT TRUE                 | Czy kategoria jest aktywna                    |
| created_at      | TIMESTAMP        | NOT NULL, DEFAULT CURRENT_TIMESTAMP    | Data utworzenia kategorii                     |
| updated_at      | TIMESTAMP        | NOT NULL, DEFAULT CURRENT_TIMESTAMP    | Data ostatniej aktualizacji                   |

**Decyzje projektowe:**
- Bez pola `description` (opisy hardcoded w frontendzie) - uproszczenie dla MVP
- Bez suffixów `_pl` (tylko polski język w MVP)
- `code` dla integracji z kodem aplikacji (wartości enum-like)
- `priority` dla sortowania w UI
- `is_active` dla możliwości ukrycia kategorii bez usuwania
- Brak soft delete (kategorie są stałe)

**CHECK Constraints:**
```sql
CONSTRAINT check_problem_category_code_format CHECK (code ~ '^[a-z_]+$')
CONSTRAINT check_problem_category_name_length CHECK (LENGTH(name) >= 1 AND LENGTH(name) <= 100)
```

---

## 2. Relacje między tabelami

### Diagram relacji

```
user (1) ────< (N) dog (1) ────< (N) advice_card (N) >──── (1) problem_category
  │                                       │
  │                                       │
  └───────────────────────────────────────┘
     (przez dog_id → user_id)
```

### Szczegółowy opis relacji

#### 2.1 user → dog (One-to-Many)

- **Kardynalność:** Jeden użytkownik może mieć wielu psów (0..N)
- **Foreign Key:** `dog.user_id` → `user.id`
- **ON DELETE:** CASCADE - usunięcie użytkownika usuwa wszystkie jego profile psów
- **ON UPDATE:** CASCADE (dla UUID nie ma znaczenia, ale dla spójności)

```sql
ALTER TABLE dog
  ADD CONSTRAINT fk_dog_user_id
  FOREIGN KEY (user_id) REFERENCES user(id)
  ON DELETE CASCADE
  ON UPDATE CASCADE;
```

#### 2.2 dog → advice_card (One-to-Many)

- **Kardynalność:** Jeden pies może mieć wiele kart porad (0..N)
- **Foreign Key:** `advice_card.dog_id` → `dog.id`
- **ON DELETE:** CASCADE - usunięcie psa usuwa wszystkie jego porady
- **ON UPDATE:** CASCADE

```sql
ALTER TABLE advice_card
  ADD CONSTRAINT fk_advice_card_dog_id
  FOREIGN KEY (dog_id) REFERENCES dog(id)
  ON DELETE CASCADE
  ON UPDATE CASCADE;
```

#### 2.3 problem_category → advice_card (One-to-Many)

- **Kardynalność:** Jedna kategoria może być przypisana do wielu porad (0..N)
- **Foreign Key:** `advice_card.category_id` → `problem_category.id`
- **ON DELETE:** RESTRICT - nie można usunąć kategorii, jeśli istnieją przypisane porady
- **ON UPDATE:** CASCADE

```sql
ALTER TABLE advice_card
  ADD CONSTRAINT fk_advice_card_category_id
  FOREIGN KEY (category_id) REFERENCES problem_category(id)
  ON DELETE RESTRICT
  ON UPDATE CASCADE;
```

**Uzasadnienie RESTRICT:** Ochrona integralności danych - zabrania usunięcia kategorii używanej przez porady.

---

## 3. Indeksy

### 3.1 Indeksy Primary Key (automatyczne)

```sql
CREATE UNIQUE INDEX user_pkey ON user(id);
CREATE UNIQUE INDEX dog_pkey ON dog(id);
CREATE UNIQUE INDEX advice_card_pkey ON advice_card(id);
CREATE UNIQUE INDEX problem_category_pkey ON problem_category(id);
```

### 3.2 Single-Column Indexes (B-tree)

#### Tabela: user

```sql
-- Unikalny indeks dla autentykacji
CREATE UNIQUE INDEX idx_user_email ON user(email);
```

#### Tabela: dog

```sql
-- Indeks na foreign key dla wydajności JOIN
CREATE INDEX idx_dog_user_id ON dog(user_id);
```

#### Tabela: advice_card

```sql
-- Indeksy na foreign keys
CREATE INDEX idx_advice_card_dog_id ON advice_card(dog_id);
CREATE INDEX idx_advice_card_category_id ON advice_card(category_id);

-- Indeks dla filtrowania po typie porady
CREATE INDEX idx_advice_card_type ON advice_card(advice_type);

-- Indeks na rating dla metryki sukcesu (tylko dla ocenionych porad)
CREATE INDEX idx_advice_card_rating ON advice_card(rating)
  WHERE rating IS NOT NULL;
```

#### Tabela: problem_category

```sql
-- Unikalny indeks na kod kategorii
CREATE UNIQUE INDEX idx_problem_category_code ON problem_category(code);

-- Indeks dla sortowania po priorytecie
CREATE INDEX idx_problem_category_priority ON problem_category(priority);
```

### 3.3 Composite Indexes (wielokolumnowe)

#### Tabela: dog

```sql
-- Optymalizacja dla: "pokaż aktywne psy użytkownika"
CREATE INDEX idx_dog_user_active ON dog(user_id, deleted_at);
```

**Zapytanie optymalizowane:**
```sql
SELECT * FROM dog WHERE user_id = ? AND deleted_at IS NULL;
```

#### Tabela: advice_card

```sql
-- Optymalizacja dla: "historia porad psa, posortowane od najnowszych"
CREATE INDEX idx_advice_card_dog_date ON advice_card(dog_id, created_at DESC);

-- Optymalizacja dla: "porady psa z oceną"
CREATE INDEX idx_advice_card_dog_rating ON advice_card(dog_id, rating);
```

**Zapytania optymalizowane:**
```sql
-- Historia porad
SELECT * FROM advice_card WHERE dog_id = ? ORDER BY created_at DESC;

-- Porady z oceną
SELECT * FROM advice_card WHERE dog_id = ? AND rating IS NOT NULL;
```

### 3.4 Partial Indexes (dla soft delete)

```sql
-- Optymalizacja dla aktywnych psów (pomiń usunięte)
CREATE INDEX idx_dog_active ON dog(user_id)
  WHERE deleted_at IS NULL;

-- Optymalizacja dla aktywnych porad (opcjonalnie, jeśli potrzebne)
CREATE INDEX idx_advice_card_active ON advice_card(dog_id)
  WHERE deleted_at IS NULL;
```

**Zapytanie optymalizowane:**
```sql
SELECT * FROM dog WHERE user_id = ? AND deleted_at IS NULL;
```

### 3.5 JSONB Index (opcjonalny, dla przyszłości)

```sql
-- Indeks GIN na plan_content dla wyszukiwania w treści planu (do rozważenia po MVP)
-- CREATE INDEX idx_advice_card_plan_gin ON advice_card USING GIN (plan_content);
```

**Zastosowanie:** Jeśli w przyszłości będziemy wyszukiwać/filtrować po zawartości JSON (np. "znajdź plany zawierające słowo 'smycz'").

---

## 4. Dane początkowe (Initial Data)

### Kategorie problemów (8 kategorii MVP)

```sql
INSERT INTO problem_category (id, code, name, priority, is_active, created_at, updated_at) VALUES
  (gen_random_uuid(), 'behavior', 'Zachowanie', 1, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
  (gen_random_uuid(), 'obedience', 'Posłuszeństwo', 2, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
  (gen_random_uuid(), 'tricks', 'Nauka sztuczek', 3, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
  (gen_random_uuid(), 'free_shaping', 'Free-shaping', 4, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
  (gen_random_uuid(), 'socialization', 'Socjalizacja', 5, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
  (gen_random_uuid(), 'anxiety', 'Lęki i fobie', 6, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
  (gen_random_uuid(), 'leash_walking', 'Chodzenie na smyczy', 7, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
  (gen_random_uuid(), 'other', 'Inne', 99, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);
```

---

## 5. Dodatkowe uwagi i wyjaśnienia decyzji projektowych

### 5.1 UUID jako klucze główne

**Decyzja:** Wszystkie tabele używają UUID zamiast SERIAL/BIGSERIAL.

**Uzasadnienie:**
- Nieodgadywalne ID (bezpieczeństwo)
- Łatwy sharding/replikacja w przyszłości
- Możliwość generowania ID po stronie aplikacji przed INSERT
- Brak konfliktów przy distributed systems

**Implementacja w Symfony/Doctrine:**
```php
#[ORM\Id]
#[ORM\Column(type: 'uuid', unique: true)]
#[ORM\GeneratedValue(strategy: 'CUSTOM')]
#[ORM\CustomIdGenerator(class: UuidGenerator::class)]
private ?Uuid $id = null;
```

**Typ PostgreSQL:**
```sql
-- Natywny typ UUID PostgreSQL 16
CREATE TABLE user (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  ...
);
```

**Alternatywa:** `uuid_binary_ordered_time` w Doctrine dla lepszej wydajności indeksów B-tree.

---

### 5.2 Soft Delete Strategy

**Decyzja:** Kolumna `deleted_at` (timestamp NULL) w tabelach: user, dog, advice_card.

**Uzasadnienie:**
- Możliwość odzyskania usuniętych danych przez 30 dni
- Zgodność z RODO (prawo do usunięcia - hard delete po 30 dniach)
- Lepsze experience dla użytkowników (cofnięcie przypadkowego usunięcia)

**Implementacja:**
- Doctrine lifecycle callbacks (@PreRemove) ustawiają `deleted_at = CURRENT_TIMESTAMP`
- Partial indexes: `WHERE deleted_at IS NULL` dla wydajności
- CRON job w Symfony: codziennie o 3:00 hard delete rekordów z `deleted_at < NOW() - INTERVAL '30 days'`

**Przykład zapytania:**
```sql
-- Pokaż tylko aktywne psy
SELECT * FROM dog WHERE user_id = ? AND deleted_at IS NULL;
```

---

### 5.3 Timestampy - zarządzanie przez Symfony/Doctrine

**Decyzja:** Wszystkie timestampy (`created_at`, `updated_at`) zarządzane przez Doctrine, NIE przez triggery PostgreSQL.

**Uzasadnienie:**
- Spójność z architekturą Symfony (Entity Lifecycle Callbacks)
- Łatwiejsze testowanie (kontrola nad timestampami w testach)
- Brak triggerów = prostszy schemat bazy
- Doctrine @PrePersist, @PreUpdate automatycznie aktualizują pola

**Implementacja w Symfony Entity:**
```php
#[ORM\HasLifecycleCallbacks]
class Dog
{
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
```

---

### 5.4 JSONB dla planu 7-dniowego

**Decyzja:** Kolumna `plan_content` typu JSONB w tabeli `advice_card`.

**Uzasadnienie:**
- Elastyczna struktura dla planu 7 dni bez nadmiernej normalizacji
- Brak potrzeby osobnej tabeli `training_plan_days` (uproszczenie dla MVP)
- PostgreSQL JSONB: szybkie zapytania, walidacja, indeksowanie (GIN opcjonalnie)
- Łatwe parsowanie w React (JSON.parse)

**Struktura:**
```json
{
  "days": [
    {
      "day": 1,
      "content": "**Cel dnia:** Wprowadzenie do komendy 'siad'\n\n**Kroki:**\n1. Przygotuj przysmaki\n2. Czekaj aż pies usiądzie sam\n3. Nagrodź natychmiast\n\n**Kryterium sukcesu:** Pies usiada w ciągu 10 sekund\n\n**Wskazówki:** Pracuj w spokojnym miejscu"
    },
    {
      "day": 2,
      "content": "..."
    }
  ]
}
```

**Zapytania JSONB (opcjonalne po MVP):**
```sql
-- Znajdź plany zawierające słowo "smycz" (wymaga GIN index)
SELECT * FROM advice_card
WHERE plan_content @> '{"days": [{"content": "smycz"}]}';
```

---

### 5.5 Walidacja danych - dwupoziomowa strategia

**Decyzja:** Walidacja na poziomie bazy danych (CHECK constraints) + poziomie aplikacji (Symfony Validator).

**Uzasadnienie:**
- Database level: ostatnia linia obrony przed błędnymi danymi
- Application level: przyjazne komunikaty błędów dla użytkownika, złożone reguły biznesowe
- Redundancja zwiększa bezpieczeństwo danych

**Przykład:**
```sql
-- Database: CHECK constraint
ALTER TABLE dog ADD CONSTRAINT check_dog_age_months
  CHECK (age_months >= 0 AND age_months <= 300);
```

```php
// Application: Symfony Validator
#[Assert\Range(min: 0, max: 300, notInRangeMessage: 'Wiek psa musi być między {{ min }} a {{ max }} miesiącami.')]
private int $ageMonths;
```

---

### 5.6 ON DELETE Strategies

**Decyzje:**

1. **CASCADE dla hierarchii user → dog → advice_card:**
   - Usunięcie użytkownika usuwa wszystkie jego psy i porady
   - Usunięcie psa usuwa wszystkie jego porady
   - Uzasadnienie: Dane są ściśle powiązane, porady bez psa/użytkownika są bezwartościowe

2. **RESTRICT dla categories → advice_card:**
   - Nie można usunąć kategorii używanej przez porady
   - Uzasadnienie: Ochrona integralności danych, kategorie są "master data"
   - Workaround: Jeśli kategoria musi zniknąć, ustaw `is_active = FALSE` zamiast usuwać

---

### 5.7 Brak historii czatu

**Decyzja:** Nie przechowujemy historii rozmów z chatbotem AI.

**Uzasadnienie:**
- PRD: "Brak przechowywania historii rozmów – sesje jednorazowe"
- Minimalizacja storage i kosztów
- Ochrona prywatności użytkowników
- Uproszczenie schematu bazy danych
- Karta porady (`advice_card`) zawiera finalne podsumowanie, co jest wystarczające dla MVP

**Implikacje:**
- Brak tabeli `chat_messages` lub `chat_sessions`
- Brak możliwości przywrócenia kontekstu rozmowy
- User experience: każda sesja to nowy problem, nowy start

---

### 5.8 Stateless JWT - brak sesji w bazie

**Decyzja:** Brak tabel `sessions`, `refresh_tokens`, `jwt_tokens`.

**Uzasadnienie:**
- Stateless JWT: token zawiera wszystkie potrzebne dane (user ID, email, roles)
- Brak potrzeby walidacji tokenu w bazie (walidacja przez sygnaturę)
- Skalowalność: serwer nie przechowuje stanu sesji
- Uproszczenie architektury

**Implementacja:**
- LexikJWTAuthenticationBundle generuje tokeny z `user_id` w payload
- Frontend przechowuje token w localStorage/sessionStorage
- Każde API request zawiera header: `Authorization: Bearer <token>`
- Token expiration: 1 godzina (konfiguracja w Symfony)

**Uwaga:** Jeśli w przyszłości potrzebny będzie refresh token mechanism, można dodać tabelę `refresh_tokens`.

---

### 5.9 Metryki sukcesu - obliczenia

**Wymaganie z PRD:** Skuteczność porad > 80% ocen pozytywnych.

**Implementacja:**
```sql
-- Obliczenie procentu pozytywnych ocen
SELECT
  COUNT(*) FILTER (WHERE rating = 'helpful') * 100.0 / NULLIF(COUNT(*) FILTER (WHERE rating IS NOT NULL), 0) AS positive_percentage
FROM advice_card;

-- Optymalizacja: użyj indeksu idx_advice_card_rating (partial index)
```

**Monitoring:**
- Dashboard w Symfony: agregacja ocen per kategoria
- Export danych do analytics (np. Google Analytics, Mixpanel)
- Możliwość dodania tabeli `metrics_daily` dla cache wyników (opcjonalnie)

---

### 5.10 Wykrywanie ryzyka - poza bazą danych

**Decyzja:** Słowa kluczowe ryzyka (agresja, ból, krew) w pliku JSON w kodzie aplikacji, nie w bazie.

**Uzasadnienie:**
- Łatwiejsze zarządzanie i aktualizacja (nie wymaga migracji)
- Możliwość wersjonowania w Git
- Szybkie dodanie/usunięcie słów kluczowych
- Brak potrzeby złożonych zapytań SQL

**Implementacja:**
```json
// config/risk_keywords.json
{
  "keywords": [
    "agresja", "atak", "pogryzienie", "krew", "rana",
    "silny ból", "krwawienie", "ugryzł człowieka"
  ]
}
```

## 6. Migration Strategy

### 6.1 Kolejność tworzenia tabel

**Zalecana kolejność migracji:**
1. `user` (brak zależności)
2. `problem_category` (brak zależności)
3. `dog` (zależy od user)
4. `advice_card` (zależy od dog i problem_category)

### 6.2 Doctrine Migrations

**Przykładowy plik migracji:**
```php
public function up(Schema $schema): void
{
    // Utwórz extension dla UUID (jeśli nie istnieje)
    $this->addSql('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');

    // Utwórz tabele w odpowiedniej kolejności
    $this->addSql('CREATE TABLE user (...)');
    $this->addSql('CREATE TABLE problem_category (...)');
    $this->addSql('CREATE TABLE dog (...)');
    $this->addSql('CREATE TABLE advice_card (...)');

    // Dodaj foreign keys
    $this->addSql('ALTER TABLE dog ADD CONSTRAINT fk_dog_user_id...');
    $this->addSql('ALTER TABLE advice_card ADD CONSTRAINT fk_advice_card_dog_id...');
    $this->addSql('ALTER TABLE advice_card ADD CONSTRAINT fk_advice_card_category_id...');

    // Utwórz indeksy
    $this->addSql('CREATE INDEX idx_dog_user_id ON dog(user_id)');
    // ...

    // Wstaw dane początkowe (kategorie)
    $this->addSql("INSERT INTO problem_category (id, code, name, priority, is_active, created_at, updated_at) VALUES...");
}
```

---

## 7. Unresolved Issues (do rozważenia w przyszłości)

### 7.1 Automatyczne czyszczenie soft-deleted rekordów

**Problem:** Jak zaimplementować usuwanie rekordów z `deleted_at` starszym niż 30 dni?

**Opcje:**
1. CRON job w Symfony (rekomendowane dla MVP)
2. PostgreSQL `pg_cron` extension
3. External scheduler (Kubernetes CronJob)

**Decyzja do podjęcia:** Częstotliwość czyszczenia, logowanie operacji, notyfikacje dla użytkowników.

### 7.2 Indeks GIN na plan_content

**Problem:** Czy potrzebujemy `CREATE INDEX USING GIN` na `plan_content` dla wyszukiwania w treści JSON?

**Decyzja:** Dla MVP nie jest potrzebny. Do rozważenia jeśli pojawi się feature "szukaj plany zawierające słowo X".

### 7.3 Limity i quota dla użytkowników

**Problem:** Czy limitować liczbę profili psów lub porad dziennie?

**Opcje:**
1. Dodać pole `dog_count` w `user` (denormalizacja)
2. Tabela `user_quotas` z limitami API calls
3. CHECK constraint na długość `problem_description`

**Decyzja:** Dla MVP brak limitów, ale warto rozważyć przed produkcją.

### 7.4 Backup i disaster recovery

**Problem:** Strategia backupów PostgreSQL nie została zdefiniowana.

**Do ustalenia:**
- Częstotliwość backupów (daily/hourly)
- Point-in-time recovery (PITR)
- Retention policy (30/90 dni)
- Test restore procedure

### 7.5 Monitoring i alerty

**Metryki do monitorowania:**
- Slow queries (> 1s)
- Connection pool utilization
- Index usage statistics
- Table bloat (przez soft delete)
- VACUUM/ANALYZE scheduling

**Narzędzia:** pg_stat_statements, pgAdmin, Grafana + Prometheus.

### 7.6 Internacjonalizacja (i18n)

**Problem:** PRD określa "tylko język polski" dla MVP, ale jak obsłużyć wielojęzyczność w v2.0?

**Opcje:**
1. Tabela `problem_category_translations`
2. User preference: `preferred_language` w `user`
3. AI prompts w różnych językach

**Decyzja:** Dla MVP pomijamy, ale architektura powinna umożliwiać rozbudowę.

---

## 8. Zgodność z tech stack

### PostgreSQL 16 Features

- ✅ Natywny typ UUID z `gen_random_uuid()`
- ✅ JSONB z indeksowaniem GIN
- ✅ CHECK constraints dla enum-like values
- ✅ Partial indexes dla soft delete optimization
- ✅ `uuid-ossp` extension (alternatywa: `pgcrypto`)

### Symfony 7 + Doctrine ORM

- ✅ Entity mapping przez PHP attributes (`#[ORM\Entity]`, `#[ORM\Column]`)
- ✅ UUID generation strategy: `UuidGenerator::class`
- ✅ UUID generation strategy: `UuidGenerator::class`
- ✅ Lifecycle callbacks: `@PrePersist`, `@PreUpdate`, `@PreRemove`
- ✅ Repository pattern dla złożonych zapytań
- ✅ CQRS buses: `command.bus`, `query.bus`, `event.bus`

### LexikJWTAuthenticationBundle

- ✅ Stateless JWT - brak sesji w bazie
- ✅ Entity-based User Provider: `App\Entity\User` (email)
- ✅ Token payload: `user_id`, `email`, `roles`

---

## 9. Podsumowanie

### Statystyki schematu

- **Liczba tabel:** 4 (user, dog, advice_card, problem_category)
- **Liczba relacji:** 3 (user→dog, dog→advice_card, categories→advice_card)
- **Liczba indeksów:** 12+ (B-tree, composite, partial, UNIQUE)
- **Liczba CHECK constraints:** 10+
- **Typy danych specjalne:** UUID (4 tabele), JSONB (1 kolumna), DECIMAL (1 kolumna)
- **Soft delete:** 3 tabele (user, dog, advice_card)

### Kluczowe cechy architektury

1. **Bezpieczeństwo:** Minimalizacja danych osobowych, stateless JWT, soft delete
2. **Skalowalność:** UUID dla sharding, JSONB dla elastyczności, indeksy composite
3. **Integralność:** Foreign keys z CASCADE/RESTRICT, CHECK constraints
4. **Wydajność:** 12+ indeksów, partial indexes, composite indexes
5. **Zgodność z RODO:** Brak nadmiarowych danych osobowych, możliwość hard delete
6. **MVP-ready:** Uproszczony schemat bez zbędnych pól (metadanych AI, historii czatu)
