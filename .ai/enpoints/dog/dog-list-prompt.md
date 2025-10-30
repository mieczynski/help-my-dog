# Prompt: Implementacja GET /api/dogs

Zaimplementuj endpoint REST API do listowania profili psów zgodnie z CQRS pattern.

## Pliki referencyjne

**PRZED ROZPOCZĘCIEM** przeczytaj:

| Plik | Zawiera |
|------|---------|
| `.ai/enpoints/dog/dog-list-implementation-plan.md` | Szczegółowy plan, przepływ danych, security, testy, przykłady |
| `CLAUDE.md` | Konwencje kodowania, architektura, standardy jakości, komendy Docker |
| `.ai/symfony-dto.md` | Wzorce DTOs z walidacją |
| `.ai/symfony-cqrs.md` | Wzorce Queries/Handlers (dla query.bus) |

## Kroki implementacji

| Krok            | Plik | Szczegóły w |
|-----------------|------|-------------|
| 1. Query Param DTO | `api/src/DTO/Request/Dog/ListDogsQueryDTO.php` | `.ai/enpoints/dog/dog-list-implementation-plan.md` (ln 108-121) |
| 2. Response DTO | `api/src/DTO/Response/Dog/DogResponseDTO.php` | Już istnieje z POST /api/dogs |
| 3. Query        | `api/src/Action/Query/Dog/ListDogsQuery.php` | `.ai/enpoints/dog/dog-list-implementation-plan.md` (ln 127-139) |
| 4. QueryHandler | `api/src/Action/Query/Dog/ListDogsQueryHandler.php` | `.ai/enpoints/dog/dog-list-implementation-plan.md` (ln 144-167) |
| 5. Repository   | `api/src/Repository/DogRepository.php` → add `findByUser()` | `.ai/enpoints/dog/dog-list-implementation-plan.md` (ln 172-188) |
| 6. Controller   | `api/src/Controller/DogController.php` → add `list()` method | `.ai/enpoints/dog/dog-list-implementation-plan.md` (ln 193-222) |
| 7. Config       | Weryfikuj `api/config/packages/messenger.yaml` | `CLAUDE.md` (Messenger/CQRS) |
| 8. Tests (unit) | `api/tests/Unit/Action/Query/Dog/ListDogsQueryHandlerTest.php` | `.ai/enpoints/dog/dog-list-implementation-plan.md` (ln 238-384) |
| 9. Quality      | Uruchom: `composer fix`, `analyse`, `test`, `npm run format` | `CLAUDE.md` (Code Quality) |

### Kluczowe różnice od POST /api/dogs

- **CQRS bus:** query.bus (nie command.bus)
- **HTTP method:** GET (nie POST)
- **Response format:** `{"data": [...]}` (opakowane w obiekt)
- **Security:** userId ZAWSZE z JWT, nigdy z query params
- **Repository:** Custom method `findByUser()` z opcją includeDeleted
- **No Factory:** Bezpośredni fetch z repository

### Więcej informacji
- **Checklist:** `.ai/enpoints/dog/dog-list-implementation-plan.md` (sekcja 6)
- **Problemy:** `.ai/enpoints/dog/dog-list-implementation-plan.md` (linie 395-402)
- **Konwencje:** `CLAUDE.md` (sekcje CQRS, Security, Testing)
