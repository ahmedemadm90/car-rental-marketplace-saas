# VoyagerRent

**VoyagerRent** is a production-oriented, enterprise-grade, multi-tenant car-rental marketplace. It is implemented as a single GitHub monorepo containing the Laravel 12 platform, customer and employee Flutter applications, infrastructure definitions, automated quality gates, and operational documentation.

The platform supports two operating models at the same time. Customers can discover, compare, reserve, pay for, modify, and review rentals across participating companies. Each rental company operates an isolated tenant workspace for branches, fleet, staff, pricing, contracts, maintenance, finance, customer operations, and performance reporting. Platform administrators govern tenant subscriptions, commission, content, campaigns, support, audit, and operational health.

> **Repository principle.** Every deployable component, migration, configuration template, automated check, architecture decision, and runbook belongs in this repository. No business behaviour is defined only in an external document or manual process.

| Area | Technology | Location |
| --- | --- | --- |
| Platform API and web console | Laravel 12, PHP 8.3, MySQL, Redis, Sanctum, JWT, Spatie Permission, Livewire, Alpine, Tailwind | `apps/platform` |
| Customer application | Flutter, Dart, encrypted offline store, push, maps, camera, QR, local biometrics | `apps/customer_mobile` |
| Employee application | Flutter, Dart, encrypted offline store, push, maps, camera, QR, local biometrics | `apps/employee_mobile` |
| Containers and edge configuration | Docker Compose, Nginx, PHP-FPM, Redis, MySQL, Mailpit | `infrastructure` |
| Continuous delivery | GitHub Actions | `.github/workflows` |
| Architecture and operations | Markdown runbooks, diagrams, decision records | `docs` |

## Domain model

| Bounded context | Main responsibilities |
| --- | --- |
| Platform governance | Tenant lifecycle, plans, subscriptions, commissions, ad inventory, CMS, SEO, support, monitoring, global audit |
| Identity and access | Customer, company, employee, and administrator identity; Sanctum sessions; JWT mobile tokens; MFA-ready device sessions; role and permission enforcement |
| Marketplace | Search, availability, comparison, offers, waitlists, reviews, favourites, public location and fleet catalogues |
| Reservation lifecycle | Quotes, reservations, hold windows, amendments, cancellations, deposits, pickup, return, extensions, contracts and digital signing |
| Fleet operations | Vehicles, groups, units, branches, assignments, inspections, maintenance, damage, odometer and availability state |
| Revenue operations | Rate cards, dynamic rules, taxes, fees, invoices, expenses, commissions, wallet ledgers, payment and refund records |
| Customer care | Notifications, customer documents, support conversations, claims, review moderation, service history |
| Analytics | Tenant and platform dashboards, export jobs, scheduled snapshots, operational and financial KPIs |

## Quality and delivery contract

The codebase applies tenant isolation before business authorization, uses immutable audit events for sensitive writes, dispatches external side effects through queues and an outbox pattern, and calculates monetary values from integer minor units. Public and mobile APIs are versioned under `/api/v1`, use Laravel API Resources, and are described in the OpenAPI specification.

All pull requests are gated by formatting, static analysis, backend tests, frontend checks, mobile analysis, dependency audit, secret scanning, and container build validation. Deployment is environment-driven; secrets remain external to Git and all committed environment files are non-secret templates.

## Local development

### Prerequisites

Install Docker Engine with Compose v2. For non-containerized local development, install PHP 8.3, Composer 2, Node 22, pnpm 9, MySQL 8, and Redis 7. Flutter 3.24 or later is required for the mobile applications.

### Container workflow

```bash
git clone https://github.com/ahmedemadm90/car-rental-marketplace-saas.git
cd car-rental-marketplace-saas
cp apps/platform/.env.example apps/platform/.env
docker compose -f infrastructure/docker-compose.yml up --build -d
docker compose -f infrastructure/docker-compose.yml exec platform php artisan migrate --seed
docker compose -f infrastructure/docker-compose.yml exec platform php artisan storage:link
```

The web console is served at `http://localhost:8080`, Mailpit at `http://localhost:8025`, and the API is available at `http://localhost:8080/api/v1`.

### Development commands

| Task | Command |
| --- | --- |
| Backend test suite | `cd apps/platform && php artisan test` |
| Backend static analysis | `cd apps/platform && ./vendor/bin/phpstan analyse` |
| Backend formatting | `cd apps/platform && ./vendor/bin/pint --test` |
| Web assets | `cd apps/platform && pnpm install && pnpm build` |
| Customer app checks | `cd apps/customer_mobile && flutter pub get && flutter analyze && flutter test` |
| Employee app checks | `cd apps/employee_mobile && flutter pub get && flutter analyze && flutter test` |
| OpenAPI validation | `cd apps/platform && php artisan l5-swagger:generate` |

## Operational documentation

| Document | Purpose |
| --- | --- |
| [Architecture](docs/ARCHITECTURE.md) | System topology, boundaries, isolation controls, and data flow |
| [Feature matrix](docs/FEATURE-MATRIX.md) | Traceability from requested capabilities to implemented modules |
| [Security](docs/SECURITY.md) | Security controls, incident handling, and access governance |
| [Development guide](docs/DEVELOPMENT.md) | Installation, conventions, branching, seed data, and testing |
| [Deployment runbook](docs/DEPLOYMENT.md) | Container rollout, migrations, queues, monitoring, and rollback |
| [API guide](docs/API.md) | Authentication, versioning, pagination, errors, idempotency, and OpenAPI |
| [Data retention](docs/DATA-RETENTION.md) | Personal data, documents, audits, and deletion workflow |
| [Architecture decisions](docs/adr) | Durable records of material engineering choices |

## Repository governance

The default branch is protected in production usage. Changes are merged only through reviewed pull requests, where required status checks must pass. Conventional Commit messages are enforced by CI, releases are tagged, and database migrations are reviewed as backwards-compatible operational changes. The platform does not permit force pushes to release branches.

## License

This repository is proprietary. All rights are reserved.
