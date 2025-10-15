# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Help My Dog is a full-stack web application built with Symfony 7 (backend) and React + Vite (frontend), running in a Dockerized environment with PostgreSQL and Redis.

## Architecture

### Backend (Symfony 7 API)
- **Location**: `api/` directory
- **PHP Version**: 8.3
- **Framework**: Symfony 7 with API-first approach
- **Database**: PostgreSQL 16 with Doctrine ORM
- **Cache**: Redis
- **Authentication**: JWT (LexikJWTAuthenticationBundle)
- **API**: RESTful endpoints via FOSRestBundle
- **CORS**: Configured via NelmioCorsBundle

**Key architectural patterns**:
- **Messenger pattern**: Three separate buses (command.bus, query.bus, event.bus) for CQRS implementation
- **Entity mapping**: Doctrine attributes in `src/Entity/`
- **Service autowiring**: All services in `src/` are autowired by default
- **Security**: Stateless JWT authentication for `/api` routes (except `/api/auth/login`)
- **User provider**: Entity-based (App\Entity\User) via email property

### Frontend (React + Vite)
- **Location**: `frontend/` directory
- **Framework**: React 18 with TypeScript
- **Build tool**: Vite 5
- **Router**: React Router DOM v7
- **Dev server**: Runs on port 5173 with HMR
- **API proxy**: Vite proxies `/api` requests to nginx container

**Frontend-Backend integration**:
- Frontend proxies API calls through Vite dev server to `http://nginx:80`
- Production builds output to `frontend/dist`
- CORS is handled server-side via NelmioCorsBundle

## Docker Services

The application runs with 5 services:
1. **php**: PHP-FPM 8.3 with Xdebug, Node.js and npm support (working dir: `/var/www/html/api`)
2. **nginx**: Web server on port 8080, serves Symfony API
3. **db**: PostgreSQL 16 (port 5432, credentials in docker-compose.yml)
4. **redis**: Redis 7 for caching (port 6379)
5. **frontend**: Vite dev server on port 5173

All PHP commands must be executed inside the `php` container via `docker compose exec php`.
Node.js and npm are also available in the PHP container for tools like Prettier.

## Common Commands

### Docker Operations
```bash
make up              # Build and start all containers
make stop            # Stop containers
make down            # Stop and remove containers with volumes
```

### Backend (Symfony)
```bash
# Dependency management
make composer-install
make composer-update

# Database
make migrate                                                    # Run migrations
make fixtures                                                   # Load fixtures
docker compose exec php php bin/console doctrine:migrations:generate  # Generate new migration

# Cache
make cc                                                        # Clear cache
docker compose exec php php bin/console cache:clear           # Alternative cache clear

# Code quality
docker compose exec php composer fix                           # Run PHP CS Fixer
docker compose exec php composer analyse                       # Run PHPStan (level 8)
docker compose exec php npm run format                         # Format code with Prettier
docker compose exec php npm run format:check                   # Check formatting with Prettier
docker compose exec php composer test                          # Run PHPUnit tests
docker compose exec php vendor/bin/phpunit tests/path/to/Test.php  # Run single test
docker compose exec php vendor/bin/phpunit --filter testMethodName # Run specific test method

# Console commands
docker compose exec php php bin/console [command]              # General Symfony console
docker compose exec php php bin/console make:controller        # Create controller
docker compose exec php php bin/console make:entity            # Create/update entity
```

### Frontend (React)
```bash
make front-dev       # Start frontend dev server
make front-build     # Build frontend for production

# Inside frontend container
docker compose exec frontend npm install
docker compose exec frontend npm run dev
docker compose exec frontend npm run build
```

## Code Quality Standards

### Backend
- **PHP CS Fixer**: Uses `@Symfony` ruleset (configured in `.php-cs-fixer.dist.php`)
- **PHPStan**: Level 8 analysis on `src/` directory with Symfony and Doctrine extensions
- **Prettier**: PHP code formatting with `@prettier/plugin-php` (configured in `.prettierrc.json`)
- **PHPUnit**: Tests in `tests/` directory, bootstrap via Symfony extension
- Run `composer fix` before committing PHP code
- Run `composer analyse` to check for type errors
- Run `npm run format` to auto-format PHP code with Prettier
- Run `npm run format:check` to check code formatting

### Frontend
- TypeScript strict mode enabled
- React 18 best practices
- No formal linting configured yet

## Testing

### Backend Tests
- Configuration: `api/phpunit.dist.xml`
- Test environment: Uses `.env.test` with separate test database
- Run all tests: `docker compose exec php composer test`
- Run specific test file: `docker compose exec php vendor/bin/phpunit tests/YourTest.php`
- Symfony PHPUnit Bridge is installed for additional testing utilities

## Environment Configuration

### Backend Environment Files
- `.env`: Base environment configuration (committed)
- `.env.local`: Local overrides (not committed)
- `.env.dev`: Development-specific config
- `.env.test`: Test environment config

### Frontend Environment
- `VITE_API_BASE_URL`: Set to `http://localhost:8080` in docker-compose.yml
- Vite automatically loads `.env` files for environment variables prefixed with `VITE_`

## Directory Structure

### Backend (`api/`)
```
src/
├── Controller/      # API controllers
├── Entity/          # Doctrine entities
├── Repository/      # Doctrine repositories
├── DataFixtures/    # Database fixtures
└── Kernel.php       # Application kernel

config/
├── packages/        # Bundle configurations
├── routes/          # Routing configuration
└── services.yaml    # Service container config

migrations/          # Doctrine migrations
tests/              # PHPUnit tests
public/             # Web root (index.php)
```

### Frontend (`frontend/`)
```
src/
├── main.tsx        # Application entry point
├── App.tsx         # Root component
└── index.css       # Global styles

vite.config.ts      # Vite configuration with API proxy
```

## Important Notes

### Database
- PostgreSQL 16 with UUID type support configured in Doctrine
- Use `identity` generation strategy for PostgreSQL
- Naming strategy: `underscore_number_aware`
- Entity mappings use PHP attributes (not XML/YAML)

### Messenger (CQRS)
- Three buses configured: `command.bus`, `query.bus`, `event.bus`
- Event bus allows no handlers (for async events)
- Default bus is `command.bus`

### Security & Authentication
- JWT authentication required for all `/api` routes except `/api/auth/login`
- User entity uses email as identifier
- Password hashing uses Symfony's auto algorithm
- Tokens are stateless (no session storage)

### Cache
- Redis is the default cache adapter
- Connection: `redis://localhost` (from PHP container perspective)
- Framework cache configured for Redis in production

### CORS
- Configured via NelmioCorsBundle
- Check `config/packages/nelmio_cors.yaml` for allowed origins/methods

## Xdebug Configuration

The PHP container includes Xdebug with the following settings:
- Server name: `helpMyDog` (for IDE configuration)
- Client host: `host.docker.internal`
- Configure your IDE to listen on the standard Xdebug port (9003)


## BACKEND

### Guidelines for SYMFONY

#### SYMFONY_CODING_STANDARDS

- Use PHP 8.3 features: typed properties, constructor property promotion, named arguments, and attributes
- Leverage Symfony's dependency injection container with autowiring for all services
- Use PHP attributes for routing, validation, and entity mapping instead of YAML/XML configuration
- Implement strict type declarations (`declare(strict_types=1);`) in all PHP files
- Follow PSR-12 coding standards and use PHP CS Fixer with `@Symfony` ruleset
- Use readonly properties for immutable data transfer objects and value objects
- Prefer constructor injection over setter injection for required dependencies
- Use interface type hints in method signatures instead of concrete implementations

#### CONTROLLERS_AND_API

- Keep controllers thin - delegate business logic to services, handlers, or command/query buses
- Use `#[Route]` attributes for API endpoints following RESTful conventions
- Return JsonResponse or use FOSRestBundle's view layer for consistent API responses
- Use `#[ParamConverter]` for automatic request deserialization when appropriate
- Implement proper HTTP status codes (200, 201, 204, 400, 401, 403, 404, 422, 500)
- Use Symfony Serializer with normalization groups to control API response structure
- Validate request data using `#[Assert]` constraints on DTOs before processing
- Handle exceptions with custom exception listeners for consistent error responses
- Use stateless controllers - avoid storing state in controller properties
- Prefix all API routes with `/api` to align with JWT authentication configuration

#### ENTITIES_AND_DOCTRINE

- Use PHP attributes for entity mapping: `#[ORM\Entity]`, `#[ORM\Column]`, `#[ORM\Id]`
- Use `identity` generation strategy for PostgreSQL auto-increment IDs
- Use `uuid_binary_ordered_time` type for UUID primary keys with better performance
- Define entity relationships with proper cascade and orphanRemoval options
- Keep entities focused on data representation - avoid business logic in entities
- Use custom repository classes for complex queries - avoid DQL in controllers
- Implement `__construct()` to initialize collections and set required default values
- Use Doctrine lifecycle callbacks (`#[ORM\PrePersist]`, `#[ORM\PreUpdate]`) sparingly
- Prefer repository methods over query builders in controllers for reusability
- Use named parameters in DQL/SQL queries to prevent SQL injection
- Apply database indexes on frequently queried columns and foreign keys
- Use `#[ORM\Table]` attributes to define indexes and unique constraints

#### CQRS_AND_MESSENGER

- Use three separate message buses: `command.bus`, `query.bus`, and `event.bus`
- Dispatch commands for write operations: `$commandBus->dispatch(new CreateUserCommand(...))`
- Dispatch queries for read operations: `$queryBus->dispatch(new GetUserQuery(...))`
- Dispatch events for domain events: `$eventBus->dispatch(new UserCreatedEvent(...))`
- Create dedicated command/query handler classes implementing `MessageHandlerInterface`
- Keep commands and queries as simple DTOs with readonly properties
- Use `#[AsMessageHandler]` attribute to register handlers automatically
- Return values from query handlers, but not from command handlers
- Consider async transport for non-critical events to improve performance
- Use middleware for cross-cutting concerns like validation, logging, or transactions

#### DEPENDENCY_INJECTION

- Rely on autowiring for automatic service injection based on type hints
- Use constructor injection for required dependencies, never setter injection
- Tag services with `#[AsController]`, `#[AsMessageHandler]`, or custom tags when needed
- Avoid service locator pattern - inject specific services instead of the container
- Use interface-based dependencies to allow easy mocking in tests
- Configure service aliases in `services.yaml` for interface-to-implementation binding
- Use `#[Autowire]` attribute for injecting scalar values or environment variables
- Avoid circular dependencies by restructuring service relationships
- Make services private by default (Symfony's default behavior)
- Use service factories for complex service instantiation logic

#### VALIDATION

- Use Symfony Validator with `#[Assert]` constraints on entities and DTOs
- Validate data at the application boundary (controllers, command handlers)
- Create custom validation constraints for complex business rules
- Use validation groups to apply different rules for create vs update operations
- Return validation errors with 422 status code and structured error messages
- Use `#[Assert\Valid]` for cascading validation to embedded objects
- Leverage built-in constraints: NotBlank, Email, Length, Regex, Choice, etc.
- Create reusable custom validators in `src/Validator/` directory
- Use `#[Assert\Callback]` for validation logic requiring multiple property checks
- Consider using validation groups like `['registration', 'profile_update']`

#### SECURITY_AND_AUTHENTICATION

- Use JWT tokens for stateless API authentication via LexikJWTAuthenticationBundle
- Configure security firewalls in `config/packages/security.yaml`
- Use `#[IsGranted]` attribute on controllers/methods for access control
- Implement custom voters for complex authorization logic in `src/Security/Voter/`
- Hash passwords using Symfony's PasswordHasher service, never store plain text
- Use UserInterface implementation for user entities
- Validate JWT token signature, expiration, and claims on every request
- Return 401 for authentication failures, 403 for authorization failures
- Use Security component's `getUser()` method to access authenticated user
- Implement refresh token mechanism for long-lived sessions if needed

#### ERROR_HANDLING

- Create custom exception classes in `src/Exception/` for domain-specific errors
- Use exception listeners/subscribers to catch and format exceptions consistently
- Map exceptions to appropriate HTTP status codes in exception listeners
- Return structured error responses: `{"error": {"code": "...", "message": "..."}}`
- Log exceptions with appropriate severity levels (error, warning, critical)
- Use `#[Route]` `requirements` parameter for input validation at routing level
- Distinguish between client errors (4xx) and server errors (5xx)
- Include request ID or trace ID in error responses for debugging
- Hide sensitive error details in production environment
- Use Monolog for structured logging with context data

#### TESTING

- Write unit tests for services, handlers, and business logic using PHPUnit
- Write functional tests for API endpoints using Symfony's WebTestCase
- Use fixtures for setting up test database state via DoctrineFixturesBundle
- Test with a separate test database configured in `.env.test`
- Mock external dependencies using PHPUnit's mock objects or Prophecy
- Use data providers for testing multiple scenarios with the same logic
- Test validation rules by providing invalid data and asserting error messages
- Test authorization by making requests with different user roles/permissions
- Use `KernelTestCase` for integration tests that need the service container
- Achieve high test coverage but focus on critical business logic
- Run tests before committing: `docker compose exec php composer test`

#### PERFORMANCE_OPTIMIZATION

- Use Redis for caching frequently accessed data and session storage
- Implement query result caching for expensive database queries
- Use Doctrine's `PARTIAL` keyword to fetch only required entity properties
- Enable OPcache in production for bytecode caching
- Use Symfony's HTTP cache for cacheable API responses
- Implement database query optimization: proper indexes, avoid N+1 problems
- Use Doctrine's `fetch="EXTRA_LAZY"` for large collections
- Profile slow requests using Symfony Profiler or Blackfire.io
- Use Messenger async transport to offload heavy tasks to background workers
- Minimize eager loading - use lazy loading or explicit joins
- Consider implementing API pagination for large result sets
- Use database connection pooling to reduce connection overhead


## FRONTEND

### Guidelines for REACT

#### REACT_CODING_STANDARDS

- Use functional components with hooks instead of class components
- Implement React.memo() for expensive components that render often with the same props
- Utilize React.lazy() and Suspense for code-splitting and performance optimization
- Use the useCallback hook for event handlers passed to child components to prevent unnecessary re-renders
- Prefer useMemo for expensive calculations to avoid recomputation on every render
- Implement useId() for generating unique IDs for accessibility attributes
- Use the new use hook for data fetching in React 19+ projects
- Leverage Server Components for {{data_fetching_heavy_components}} when using React with Next.js or similar frameworks
- Consider using the new useOptimistic hook for optimistic UI updates in forms
- Use useTransition for non-urgent state updates to keep the UI responsive

## DATABASE

### Guidelines for SQL

#### POSTGRES

- Use connection pooling to manage database connections efficiently
- Implement JSONB columns for semi-structured data instead of creating many tables for {{flexible_data}}
- Use materialized views for complex, frequently accessed read-only data

## DEVOPS

### Guidelines for CI_CD

#### GITHUB_ACTIONS

- Check if `package.json` exists in project root and summarize key scripts
- Check if `.nvmrc` exists in project root
- Check if `.env.example` exists in project root to identify key `env:` variables
- Always use terminal command: `git branch -a | cat` to verify whether we use `main` or `master` branch
- Always use `env:` variables and secrets attached to jobs instead of global workflows
- Always use `npm ci` for Node-based dependency setup
- Extract common steps into composite actions in separate files
- Once you're done, as a final step conduct the following: for each public action always use <tool>"Run Terminal"</tool> to see what is the most up-to-date version (use only major version) - extract tag_name from the response:
- ```bash curl -s https://api.github.com/repos/{owner}/{repo}/releases/latest ```


### Guidelines for CONTAINERIZATION

#### DOCKER

- Use multi-stage builds to create smaller production images
- Implement layer caching strategies to speed up builds for {{dependency_types}}
- Use non-root users in containers for better security


## CODING_PRACTICES

### Guidelines for DOCUMENTATION

#### SWAGGER

- Define comprehensive schemas for all request and response objects
- Use semantic versioning in API paths to maintain backward compatibility
- Implement detailed descriptions for endpoints, parameters, and {{domain_specific_concepts}}
- Configure security schemes to document authentication and authorization requirements
- Use tags to group related endpoints by resource or functional area
- Implement examples for all endpoints to facilitate easier integration by consumers

### Guidelines for VERSION_CONTROL

#### GITHUB

- Use pull request templates to standardize information provided for code reviews
- Implement branch protection rules for {{protected_branches}} to enforce quality checks
- Configure required status checks to prevent merging code that fails tests or linting
- Use GitHub Actions for CI/CD workflows to automate testing and deployment
- Implement CODEOWNERS files to automatically assign reviewers based on code paths
- Use GitHub Projects for tracking work items and connecting them to code changes