# GEMINI.md

## Project Overview

**Sistema Arqueológico — API** is a central backend built with **Laravel 13** and **PHP 8.4** for managing archaeological data. It serves a mobile collection app (Flutter) and a web administrative interface.

- **Domain:** Archaeology, data management, and scientific audit.
- **Key Modules:**
  - **Coletas (Collections):** Field data entry with geospatial support (PostGIS).
  - **Bens Materiais (Material Goods):** Lifecycle and inventory of artifacts.
  - **Curadoria (Curatorship):** Technical validation workflow for field records.
  - **Auditoria (Auditory):** Immutable logs of sensitive system operations.
  - **Artigos Científicos (Scientific Papers):** Linkage of publications to artifacts.
  - **Sincronização Offline:** Batch processing for remote field data.

### Tech Stack
- **Framework:** Laravel 13.x
- **Database:** PostgreSQL 16 + PostGIS
- **Cache/Queue:** Redis 7.2 (via Laravel Horizon)
- **Auth:** Laravel Sanctum (Mobile/API) & Fortify (Web/Admin)
- **Infrastructure:** Docker & Makefile

---

## Building and Running

### Prerequisites
- Docker & Docker Compose
- Node.js & npm (for assets)

### Setup Commands
```bash
make up             # Start containers
make install        # Install PHP dependencies (runs composer install in-container)
make key            # Generate APP_KEY
make migrate        # Run database migrations
make seed           # Optional: Populate with sample data
```

### Key Development Commands
- **Run Tests:** `make test`
- **Linting:** `composer lint` (uses Laravel Pint)
- **Queue Worker:** `make queue`
- **Recreate DB:** `make fresh` (Warning: destructive)
- **Logs:** `make logs`
- **Shell access:** `make bash`

---

## Development Conventions

### Architecture & Patterns
- **API Versioning:** Prefixed with `v1/mobile` and `v1/admin` in `routes/api.php`.
- **Controllers:** Organized by context (`Admin`, `Auth`, `Mobile`).
- **Middleware:**
  - `CheckRole`: Enforces `perfil` (admin, curador, coletor).
  - `OptionalAuthenticate`: Allows guest access on specific endpoints but enforces token validity if present.
- **Policies:** Explicit authorization logic in `app/Policies`.
- **Jobs:** Heavy tasks (sync, media upload) handled via queues in `app/Jobs`.

### Coding Standards
- **Style:** Laravel Pint (standard PSR-12/Laravel style).
- **Types:** Strict typing in PHP 8.4; TypeScript for frontend assets.
- **Commits:** Clear, concise messages (refer to existing git history).
- **Validation:** Always use FormRequests or inline validation; verify with unit/feature tests.

### Final Verification Mandate
To ensure system integrity and code quality, **ALWAYS** run the following command as the final step of any task:
```bash
composer lint     # Automatically fix style issues
composer test     # Run the test suite
```
Or for a complete check: `composer ci:check`.

### Testing Practices
- **PHPUnit:** Feature tests for API endpoints (`tests/Feature`) and Unit tests for isolated logic (`tests/Unit`).
- **Database:** Uses `.env.testing` and database transactions/migrations for test isolation.

---

## Key Files & Directories
- `app/Models/`: Eloquent models representing the archaeological domain.
- `app/Http/Controllers/`: Request handlers segmented by interface (Mobile/Admin).
- `database/migrations/`: Schema definitions including PostGIS setup.
- `routes/api.php`: Main API route definitions and middleware application.
- `Makefile`: Entry point for common development and infrastructure tasks.
- `docker-compose.yml`: Local infrastructure definition.
