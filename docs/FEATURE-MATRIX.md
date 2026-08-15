# Feature Traceability Matrix

This matrix is the release-control record for VoyagerRent. Each capability maps to a named application module, persisted data, access model, API or web surface, and automated verification target. A feature is considered complete only when all five are present.

| Persona | Capability | Module and persisted records | Delivery surface | Verification |
| --- | --- | --- | --- | --- |
| Customer | Search by location, dates, class, transmission, fuel, provider and price | Marketplace search, availability queries, filter DTOs | Public web and customer API | Feature tests for filters, pagination, inaccessible inventory |
| Customer | Compare vehicle offers and rate inclusions | Quote comparison and pricing snapshots | Public web and customer API | Price snapshot and tax/fee calculation tests |
| Customer | Real-time availability and reservation hold | Availability allocations, quote holds, lock service | Customer API | Concurrent booking and expiry tests |
| Customer | Book, reschedule, extend and cancel | Reservations, amendments, cancellation policies | Web and customer API | State-machine and money-delta tests |
| Customer | Waitlist when unavailable | Waitlists, promotion jobs, notifications | Web and customer API | Queue, priority and expiry tests |
| Customer | Wallet, payments, deposits and refunds | Wallets, ledger entries, payment intents, refunds | Web and customer API | Idempotency and append-only ledger tests |
| Customer | Identity and document upload | Customer profiles, document versions, private media | Web and customer API | Signed upload and access-policy tests |
| Customer | Contract review and digital signature | Contracts, templates, signers, signature evidence | Web and customer app | Tamper-proof contract and signer tests |
| Customer | Email, SMS, WhatsApp and push updates | Notification templates and delivery records | API preferences and web centre | Queued delivery and preference tests |
| Customer | Reviews and support chat | Reviews, moderation states, support tickets, messages | Web and customer API | Ownership, moderation and realtime event tests |
| Rental company | Company, branch and employee administration | Companies, branches, memberships, shifts | Company console and employee API | Tenant isolation and role tests |
| Rental company | Fleet catalogue and physical vehicle tracking | Vehicle groups, vehicles, photos, telematics references | Company console and employee API | Company scope and state-transition tests |
| Rental company | Pricing, offers, taxes, fees and dynamic rules | Rate plans, rules, promotions, fiscal profiles | Company console | Price engine matrix tests |
| Rental company | Pickup, return, inspections and damage evidence | Handover tasks, inspections, damages, media | Employee app and company console | Offline sync, camera evidence, task state tests |
| Rental company | Service, maintenance and availability blocks | Maintenance records, work orders, blocks | Company console and employee app | Vehicle conflict and release tests |
| Rental company | Finance and billing | Invoices, invoice lines, expenses, settlements | Company console | Accounting totals, exports and permission tests |
| Rental company | Analytics and exports | KPI snapshots, report jobs, export records | Company console | CSV/XLSX authorization and queued generation tests |
| Administrator | Tenant plans, subscriptions and commissions | Plans, subscriptions, commission rules, settlements | Platform console | Lifecycle and financial calculation tests |
| Administrator | Support, feature access and impersonation | Support cases, grants, impersonation sessions | Platform console | Audit, expiry and least-privilege tests |
| Administrator | CMS, SEO, ads and public content | Pages, localized content, SEO tags, campaigns | Platform console and public web | Locale, publish schedule, sanitizer tests |
| Administrator | Audit, monitoring and platform health | Audit events, health checks, incident records | Platform console | Immutable log and status permission tests |
| Mobile customer | Offline browsing, wallet and trip state | Encrypted local database, sync envelope, conflict journal | Flutter customer app | Sync and conflict unit tests |
| Mobile customer | Biometrics, QR, camera, maps and pushes | Secure credentials, QR scanner, document capture, map routes | Flutter customer app | Device adapter and permission tests |
| Mobile employee | Offline task execution and evidence capture | Encrypted local task cache, draft inspections, media queue | Flutter employee app | Reconnect merge and media retry tests |
| Mobile employee | Biometric session, QR handover and notifications | Secure token store, QR validation, task alerts | Flutter employee app | Authentication and action authorization tests |

## Permission matrix

The database uses granular Spatie permissions grouped by resource and operation. Roles contain permission sets but authorization checks use permissions rather than hard-coded role names. Platform roles are globally scoped; company roles are scoped by `company_id`; branch assignments further limit operational employee actions.

| Permission family | Platform administrator | Platform support | Company owner | Fleet manager | Branch manager | Counter agent | Finance officer | Customer |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| `platform.tenants.*` | Full | Read and support-limited | None | None | None | None | None | None |
| `platform.cms.*` | Full | Read | None | None | None | None | None | None |
| `company.settings.*` | Read when supporting | None | Full | Read | Read | None | Read fiscal | Own profile |
| `fleet.*` | Read support | Read support | Full | Full | Branch inventory | Read assigned | Read | Public catalogue only |
| `pricing.*` | Read support | None | Full | Manage | Read | Quote only | Read | Quote only |
| `reservations.*` | Read support | Manage support cases | Full | Read | Full branch | Full assigned branch | Read | Own reservations |
| `operations.*` | Read support | None | Full | Manage fleet tasks | Manage branch tasks | Execute handovers | Read | None |
| `finance.*` | Commission only | None | Full | Read | Read | None | Full | Own wallet and invoices |
| `reports.*` | Full platform | Support reports | Full tenant | Fleet reports | Branch reports | Own tasks | Finance reports | Own data export |
| `audit.*` | Full | Support-limited | Tenant audit | Fleet audit | Branch audit | Own actions | Finance audit | Own data requests |

## Acceptance gates

| Gate | Completion requirement |
| --- | --- |
| Functional | Every matrix line has routes, validated actions, persisted state, policy checks and API/web responses |
| Security | Tenant and object authorization have positive and negative tests; documents and finances are private by default |
| Data | Migrations have foreign keys, indexes, state constraints, monetary integer columns and a documented retention category |
| Reliability | Long-running or external work is queued, retryable, idempotent and observable |
| Mobile | Each mobile capability has offline behaviour, an explicit sync policy, error state, local privacy control and accessibility semantics |
| Operations | Health checks, runbooks, alert routes, rollback procedure and migration execution steps are documented |
