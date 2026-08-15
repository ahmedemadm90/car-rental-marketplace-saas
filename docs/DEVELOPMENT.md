# Development Guide

## Repository workflow

Work is performed in short-lived branches from `main`. Every branch begins from the latest protected `main`, uses a descriptive Conventional Commit sequence, and is merged through a reviewed pull request after required automated checks pass. A pull request must describe the business change, relevant tenant-impact considerations, migration plan, test evidence, operational changes, and rollback outcome.

| Branch type | Naming pattern | Purpose |
| --- | --- | --- |
| Feature | `feat/<area>-<description>` | New tested business capability |
| Fix | `fix/<area>-<description>` | Defect remediation with regression test |
| Security | `security/<area>-<description>` | Vulnerability remediation and impact record |
| Operations | `ops/<area>-<description>` | Infrastructure, observability, release or runbook change |
| Documentation | `docs/<area>-<description>` | Non-runtime documentation change |

## Backend conventions

Laravel uses strict types for new PHP files. Controllers stay thin and never calculate money or enforce state transitions. A Form Request validates and normalizes external input; a Policy authorizes the action; an Action or Service performs the domain operation; a Resource formats API data; a domain event communicates the completed state; and queue listeners carry non-critical external work. Eloquent models express persistence and relationships only.

| Concern | Standard |
| --- | --- |
| Namespaces | `App\Domain\<Module>` for business code; `App\Http` for transport code; `App\Support` for cross-cutting infrastructure |
| Money | Integer minor units plus ISO currency code; never float/double; explicit rounding mode in pricing services |
| Time | UTC persistence using immutable Carbon instances; tenant/branch timezone only at presentation and business-rule boundary |
| IDs | ULIDs for public/new business entities; internal integer IDs can remain framework-local but are not exposed unnecessarily |
| Enums | Native PHP backed enums for persistent state and limited choice sets |
| Events | Past tense and immutable payloads; event writes through outbox in the same database transaction |
| Errors | RFC 9457-style JSON problem responses with machine code, human title, detail, status and correlation ID |
| Queries | Tenant-scoped by default; reports use dedicated query services and paginated/cursor responses |
| Files | Private disk by default; public vehicle media requires explicit publishing workflow and content review |

## Database standards

Every business table has an incrementing primary key or ULID according to its exposure, timestamps, actor fields where relevant, and foreign keys. Tenant-owned records have a non-null `company_id` plus compound indexes that begin with `company_id`. Financial, contract, inspection, and audit tables use a non-destructive lifecycle and append-only history rather than hard deletes. A migration must be safe for the deployment order and cannot depend on a same-release background backfill before serving live traffic.

Schema changes use an expand–migrate–contract sequence. First add nullable or backwards-compatible structures, deploy code that writes both representations if required, backfill in resumable jobs, verify read parity, then remove obsolete fields in a later release. Destructive migrations require a documented backup, rollback approach, and approval.

## Frontend conventions

The web interface uses Tailwind design tokens, Blade components, Livewire for server-backed interactive workflows, and Alpine for isolated client behaviour. All new user-facing content uses translation keys with English and Arabic copies. Layout direction is derived from locale and tested for both LTR and RTL. Pages support system, light, and dark themes while retaining WCAG-focused contrast, visible focus treatment, semantic landmarks, keyboard operation, and meaningful empty/error/loading states.

## Mobile conventions

Flutter apps follow feature-first clean architecture: presentation views and controllers, application use cases, domain entities, and data repositories. Offline data resides in an encrypted local database; the sync engine uses operation IDs, server versioning, retry policy, and explicit conflict resolution. Tokens are stored only in platform secure storage. Biometric unlock gates local session restoration but does not replace server authentication. Camera, maps, QR, and notification permissions are requested only at point of use with a meaningful explanation and alternate path.

## Test strategy

| Layer | Required checks |
| --- | --- |
| Unit | Pricing arithmetic, state transition map, ledger posting, policy helpers, sync conflict rules |
| Feature | Requests, validation, authentication, permissions, tenant isolation, model bindings and idempotency |
| Integration | Queues, webhooks, payment adapter mapping, storage access, exports and notification preference logic |
| Browser | Critical web booking, company fleet, admin tenant and RTL journeys |
| Mobile | Repository, controller, widget, sync and secure-session tests |
| Security | Authorization negatives, cross-tenant access attempts, signature verification, export/document access and rate limits |
| Regression | Every incident or defect receives a reproducible test before the fix is accepted |

## Seed data

`DatabaseSeeder` creates a platform administrator, two isolated companies, representative branch and vehicle data, staff role assignments, a customer, price plans, confirmed and future reservation states, wallet entries, inspection examples, localized content, and test notification templates. Seed credentials are printed only for local development and are rejected in non-local environments. Factory states allow tests to generate tenant-specific fixtures without global database coupling.
