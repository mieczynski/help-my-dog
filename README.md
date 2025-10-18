# Help My Dog

A full-stack web application built with Symfony 7 (backend) and React + Vite (frontend), running in a Dockerized environment with PostgreSQL and Redis.

## Table of Contents

- [Project Description](#project-description)
- [Tech Stack](#tech-stack)
- [Getting Started Locally](#getting-started-locally)
- [Available Scripts](#available-scripts)
- [Project Scope](#project-scope)
- [Project Status](#project-status)
- [License](#license)

## Project Description

Help My Dog is a modern full-stack web application designed with an API-first approach. The backend is powered by Symfony 7, implementing CQRS patterns with separate command, query, and event buses. The frontend leverages React 18 with TypeScript and Vite for a fast development experience. The entire stack runs in Docker containers, ensuring consistent environments across development and deployment.

### Key Features

- **RESTful API**: Built with Symfony 7 and FOSRestBundle
- **JWT Authentication**: Stateless authentication via LexikJWTAuthenticationBundle
- **CQRS Architecture**: Separate buses for commands, queries, and events using Symfony Messenger
- **Modern Frontend**: React 18 with TypeScript, React Router DOM v7, and Vite 5
- **Database**: PostgreSQL 16 with Doctrine ORM
- **Caching**: Redis for application cache and session storage
- **Code Quality**: PHP CS Fixer, PHPStan (level 8), ESLint, and Prettier
- **Testing**: PHPUnit for backend testing with Symfony PHPUnit Bridge

## Tech Stack

### Backend

- **PHP**: 8.3
- **Framework**: Symfony 7
- **Database**: PostgreSQL 16
- **Cache**: Redis 7
- **Authentication**: JWT (LexikJWTAuthenticationBundle)
- **API**: RESTful endpoints via FOSRestBundle
- **ORM**: Doctrine ORM 3.2
- **CORS**: NelmioCorsBundle

**Key Symfony Bundles:**
- `symfony/messenger` - CQRS implementation with command/query/event buses
- `doctrine/doctrine-bundle` - Database abstraction and ORM
- `lexik/jwt-authentication-bundle` - JWT authentication
- `nelmio/cors-bundle` - CORS configuration

**Development Tools:**
- PHP CS Fixer - Code style fixing with @Symfony ruleset
- PHPStan (level 8) - Static analysis with Symfony and Doctrine extensions
- PHPUnit - Unit and functional testing
- Prettier - PHP code formatting
- Xdebug - Debugging support

### Frontend

- **Framework**: React 18
- **Language**: TypeScript
- **Build Tool**: Vite 5
- **Router**: React Router DOM v7
- **Dev Server**: Vite dev server with HMR on port 5173

**Development Tools:**
- ESLint - Linting with TypeScript and React support
- Prettier - Code formatting
- TypeScript - Type checking

### DevOps

- **Docker**: Multi-container application with Docker Compose
- **Containers**:
  - PHP 8.3-FPM with Xdebug
  - Nginx 1.27
  - PostgreSQL 16
  - Redis 7
  - Vite development server

## Getting Started Locally

### Prerequisites

- Docker
- Docker Compose
- Git

### Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd help-my-dog
   ```

2. **Build and start Docker containers**
   ```bash
   make up
   ```

   This command will:
   - Build all Docker images
   - Start all services (PHP, Nginx, PostgreSQL, Redis, Frontend)
   - Set up the development environment

3. **Install backend dependencies**
   ```bash
   make composer-install
   ```

4. **Run database migrations**
   ```bash
   make migrate
   ```

5. **Load fixtures (optional)**
   ```bash
   make fixtures
   ```

6. **Access the application**
   - **Backend API**: http://localhost:8080
   - **Frontend**: http://localhost:5173
   - **Database**: localhost:5432
   - **Redis**: localhost:6379


## Project Scope

### Architecture Overview

**Backend (Symfony 7 API)**

The backend follows an API-first approach with these architectural patterns:

- **Entity Mapping**: Doctrine attributes in `src/Entity/`
- **Service Autowiring**: All services in `src/` are autowired by default
- **Security**: Stateless JWT authentication for `/api` routes (except `/api/auth/login`)
- **User Provider**: Entity-based (App\Entity\User) via email property

**Directory Structure:**
```
api/
├── config/             # Bundle configurations and routing
├── migrations/         # Doctrine migrations
├── public/            # Web root (index.php)
├── src/
│   ├── Controller/    # API controllers
│   ├── Entity/        # Doctrine entities
│   ├── Repository/    # Doctrine repositories
│   ├── DataFixtures/  # Database fixtures
│   └── Kernel.php     # Application kernel
└── tests/             # PHPUnit tests
```

**Frontend (React + Vite)**

- React 18 with functional components and hooks
- TypeScript strict mode enabled
- Vite dev server with HMR
- API proxy: Vite proxies `/api` requests to nginx container
- Production builds output to `frontend/dist`

**Directory Structure:**
```
frontend/
├── src/
│   ├── main.tsx       # Application entry point
│   ├── App.tsx        # Root component
│   └── index.css      # Global styles
└── vite.config.ts     # Vite configuration with API proxy
```

### Database

- PostgreSQL 16 with UUID type support
- Uses `identity` generation strategy for PostgreSQL
- Naming strategy: `underscore_number_aware`
- Entity mappings use PHP attributes (not XML/YAML)

### Security & Authentication

- JWT authentication required for all `/api` routes except `/api/auth/login`
- User entity uses email as identifier
- Password hashing uses Symfony's auto algorithm
- Tokens are stateless (no session storage)
- CORS configured via NelmioCorsBundle

### Testing

- PHPUnit configuration in `api/phpunit.dist.xml`
- Test environment uses `.env.test` with separate test database
- Symfony PHPUnit Bridge installed for additional testing utilities
- Run tests: `docker compose exec php composer test`
