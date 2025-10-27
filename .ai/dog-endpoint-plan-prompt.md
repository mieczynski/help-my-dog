# Prompt: Tworzenie planu implementacji endpointu API

Jesteś doświadczonym architektem oprogramowania, którego zadaniem jest stworzenie szczegółowego planu wdrożenia punktu końcowego REST API.

## Dane wejściowe

### 1. Specyfikacja API endpointu
```
<route_api_specification>
[Fragment z .ai/api-plan.md dla konkretnego endpointu]
</route_api_specification>
```

### 2. Related database resources
```
<related_db_resources>
[Fragment z .ai/db-plan.md dla powiązanych tabel]
</related_db_resources>
```

### 3. Referencje
- **DTOs:** `.ai/symfony-dto.md`
- **CQRS:** `.ai/symfony-cqrs.md`
- **Tech stack:** `.ai/tech-stack.md`
- **Implementation rules:** `CLAUDE.md`

## Proces

### Krok 1: Analiza w <analysis>

Przeanalizuj:
1. Kluczowe punkty specyfikacji API
2. Wymagane i opcjonalne parametry
3. Niezbędne typy DTO i CQRS
4. Logikę service/factory
5. Walidację danych zgodnie ze specyfikacją
6. Potencjalne zagrożenia bezpieczeństwa
7. Scenariusze błędów i kody statusu

### Krok 2: Stwórz plan markdown

Sekcje:
1. **Przegląd punktu końcowego** - cel, funkcjonalność
2. **Wykorzystywane typy** - DTOs, Commands, Handlers, Factories
3. **Przepływ danych** - diagram, queries
4. **Względy bezpieczeństwa** - auth, authorization, walidacja
5. **Obsługa błędów** - tabela status codes
6. **Etapy wdrożenia** - krok po kroku (DTO → Command → Handler → Factory → Controller → Tests)
7. **Unit Tests** - test structure, test cases dla Factory/Handler/DTO, mocking guidelines, coverage requirements
8. **Potencjalne problemy** - tabela problem/solution


## Output

Plan w markdown gotowy do implementacji.

**Referencje:** `.ai/api-plan.md`, `.ai/db-plan.md`, `.ai/symfony-dto.md`, `.ai/symfony-cqrs.md`
