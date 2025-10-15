# Tech Stack - Help My Dog

## Backend

### Framework i język
- **Symfony 7** - nowoczesny framework PHP do budowy API
- **PHP 8.3** - najnowsza stabilna wersja PHP z typowaniem i atrybutami

### Baza danych i cache
- **PostgreSQL 16** - relacyjna baza danych z wsparciem dla UUID
- **Redis 7** - cache i sesje (do rozważenia usunięcia w MVP)

### Kluczowe bundle i biblioteki
- **Doctrine ORM** - mapowanie obiektowo-relacyjne z atrybutami PHP
- **LexikJWTAuthenticationBundle** - autentykacja JWT (stateless)
- **FOSRestBundle** - narzędzia do budowy REST API
- **NelmioCorsBundle** - obsługa CORS dla komunikacji z frontendem
- **Symfony Messenger** - implementacja wzorca CQRS (command/query/event buses)
- **API Platform** - opcjonalnie do rozważenia dla auto-generowanego API

### Jakość kodu
- **PHP CS Fixer** - formatowanie kodu (@Symfony ruleset)
- **PHPStan Level 8** - statyczna analiza typów z rozszerzeniami Symfony i Doctrine
- **Prettier + @prettier/plugin-php** - formatowanie PHP
- **PHPUnit** - testy jednostkowe i funkcjonalne

### Architektura backend
- **Wzorzec CQRS** z trzema busami: `command.bus`, `query.bus`, `event.bus`
- **Entity-based User Provider** - autentykacja oparta na `App\Entity\User` (email)
- **Stateless JWT** - tokeny bez przechowywania sesji po stronie serwera
- **Autowiring serwisów** - wszystkie serwisy w `src/` automatycznie wstrzykiwane

---

## Frontend

### Framework i narzędzia
- **React 18** - biblioteka UI z hookami i funkcyjnymi komponentami
- **TypeScript** - typowanie statyczne dla zwiększenia bezpieczeństwa
- **Vite 5** - szybki bundler z HMR (Hot Module Replacement)
- **React Router DOM v7** - routing po stronie klienta

### Konfiguracja
- Dev server na porcie **5173** z proxy API do `http://nginx:80`
- Output produkcyjny w `frontend/dist`
- TypeScript strict mode włączony
- CORS obsługiwany po stronie Symfony (NelmioCorsBundle)

### Jakość kodu frontend
- TypeScript strict mode
- React 18 best practices
- ESLint/Prettier

---

## Infrastruktura (Docker)

### Serwisy Docker Compose

1. **php** (PHP-FPM 8.3)
   - Working directory: `/var/www/html/api`
   - Xdebug dla debugowania (host: `host.docker.internal`, port 9003)
   - Node.js i npm dla narzędzi typu Prettier

2. **nginx**
   - Web server obsługujący Symfony API
   - Port: **8080**

3. **db** (PostgreSQL 16)
   - Port: **5432**
   - Credentials w `docker-compose.yml`

4. **redis** (Redis 7)
   - Port: **6379**
   - Cache adapter dla Symfony

5. **frontend** (Vite dev server)
   - Port: **5173**
   - HMR dla szybkiego developmentu

### Komendy Docker
- `make up` - build i start kontenerów
- `make stop` - zatrzymanie
- `make down` - zatrzymanie + usunięcie wolumenów
- Wszystkie komendy PHP przez: `docker compose exec php`

---

## Bezpieczeństwo

### Autentykacja i autoryzacja
- **JWT (JSON Web Tokens)** dla stateless authentication
- Symfony Security Component z entity provider
- Hashowanie haseł przez Symfony (algorytm auto)
- Ochrona wszystkich endpointów `/api/*` poza `/api/auth/login`

### Zabezpieczenia built-in
- **CSRF protection** (Symfony)
- **XSS prevention** (escapowanie w Twig/React)
- **SQL Injection prevention** (Doctrine Parameterized Queries)
- **CORS** kontrolowany przez NelmioCorsBundle
- **TypeScript** redukuje błędy runtime po stronie frontendu

---

## Integracje zewnętrzne

### Planowane
- **OpenAI GPT API** - generowanie porad treningowych i planów 7-dniowych
- Model: GPT-4 lub GPT-3.5-turbo
- Język: polski
- Timeout response: docelowo < 8 sekund

---

## Środowiska

### Backend (pliki `.env`)
- `.env` - bazowa konfiguracja (committowana)
- `.env.local` - nadpisania lokalne (gitignored)
- `.env.dev` - development
- `.env.test` - testowa baza danych

### Frontend
- Zmienne środowiskowe z prefiksem `VITE_*`
- `VITE_API_BASE_URL=http://localhost:8080` (z docker-compose.yml)

---

## Struktura katalogów

### Backend (`api/`)
```
src/
├── Controller/      # REST API controllers
├── Entity/          # Doctrine entities (User, Dog, AdviceCard)
├── Repository/      # Doctrine repositories
├── DataFixtures/    # Seeders dla dev/test
└── Kernel.php

config/
├── packages/        # Konfiguracje bundli
├── routes/          # Definicje routingu
└── services.yaml    # Container serwisów

migrations/          # Doctrine migrations
tests/              # PHPUnit tests
public/             # Web root (index.php)
```

### Frontend (`frontend/`)
```
src/
├── main.tsx        # Entry point
├── App.tsx         # Root component
└── index.css       # Global styles

vite.config.ts      # Konfiguracja Vite + API proxy
```

---

## Workflow deweloperski

### Backend
1. Zmiany w kodzie PHP
2. `docker compose exec php composer fix` - auto-formatowanie
3. `docker compose exec php composer analyse` - PHPStan check
4. `docker compose exec php composer test` - uruchomienie testów
5. `docker compose exec php php bin/console doctrine:migrations:generate` - nowa migracja
6. `make migrate` - wykonanie migracji

### Frontend
1. Zmiany w React/TS
2. Hot reload automatyczny (Vite HMR)
3. `make front-build` - build produkcyjny
4. ESLint/Prettier (do skonfigurowania)

### Git workflow
- Commity w języku angielskim
- Konwencja: `feat:`, `fix:`, `refactor:`, `docs:`, `test:`
- Pre-commit hooks (do rozważenia): PHP CS Fixer + PHPStan
