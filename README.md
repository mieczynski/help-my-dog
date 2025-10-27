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

**Tech stack:** Zobacz `.ai/tech-stack.md`

- **Backend:** Symfony 7 + PHP 8.3, PostgreSQL 16, Redis 7, JWT auth, CQRS (Messenger)
- **Frontend:** React 18 + TypeScript, Vite 5, React Router v7
- **DevOps:** Docker Compose (5 containers: PHP-FPM, Nginx, PostgreSQL, Redis, Vite)
- **Code Quality:** PHP CS Fixer, PHPStan Level 8, PHPUnit, Prettier, ESLint

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

