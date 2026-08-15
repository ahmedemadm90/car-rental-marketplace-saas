# Security and Privacy Controls

## Security objectives

VoyagerRent protects tenant segregation, customer identity data, vehicle and operational records, financial integrity, private documents, and administrative authority. Security is designed as an enforceable part of application flow, not a post-deployment checklist. The platform applies least privilege, defence in depth, secure defaults, complete accountability for sensitive actions, and resilient failure handling.

| Control area | Implementation standard |
| --- | --- |
| Identity | Laravel Sanctum is used for browser sessions and first-party personal access tokens; JWT access tokens are short-lived for mobile clients and paired with rotating refresh tokens stored per device |
| Access control | Spatie permissions are checked in policies and actions; company and branch membership is always evaluated before resource permissions |
| Tenant isolation | Tenant context middleware, global scopes, scoped route binding, job middleware, cache/storage namespacing and negative authorization tests |
| Passwords | Argon2id hashing, breached-password validation, password confirmation for sensitive changes, throttled resets, and revocation of active sessions after reset |
| Multi-factor readiness | TOTP secret, recovery-code, and verified-device data model; administrators and company owners can be required to enroll by policy |
| API protection | TLS only, CORS allowlist, request rate limits by identity and IP, idempotency for state-changing payment/reservation requests, JSON schema validation and pagination ceilings |
| Data protection | Encrypted backups, database encryption at rest where infrastructure supports it, application-level encryption for high-sensitivity values, private object storage and expiring signed access URLs |
| Financial integrity | Integer minor-unit money values, append-only ledgers, idempotent provider callbacks, reconciled status changes and immutable invoice snapshots |
| Document integrity | Private uploads, malware scan hook, MIME/type and size validation, signed contracts, document versions, immutable evidence metadata |
| Supply chain | Locked dependencies, GitHub dependency review, secret scanning, code scanning, image scanning and reproducible builds |
| Observability | Correlation IDs, structured logs with data redaction, audit events, health checks, alert routing, failed job review and controlled log access |

## Authorization order

Every endpoint follows a strict order: authenticate the actor; resolve or verify tenant context; validate all input; bind models through scoped queries; authorize the action through a policy; execute within an action/service transaction; write the audit event; and publish a domain event through the outbox. A request never decides tenant context solely from a body parameter.

| Sensitive action | Required control |
| --- | --- |
| Changing payment, refund, wallet, invoice or commission state | Permission, idempotency key, transactional ledger entry, audit record and two-person review option where policy requires it |
| Reading or downloading documents | Object policy, tenant membership, expiring signed URL, access audit event |
| Signing contract | Authenticated signer, purpose-bound signed link or session, immutable timestamp/IP/user-agent evidence and template digest |
| Impersonating a tenant user | Explicit support permission, time-bounded session, visible banner, reason, target, initiator and all actions audited |
| Exporting customer or finance data | Purpose-specific permission, queued protected export, expiry, download audit and watermark metadata |
| Altering tenant subscription or plan | Platform permission, immutable billing event, audit record and notification to tenant owner |
| Deleting personal data | Verified data-subject request, retention policy evaluation, legal-hold check, pseudonymization/deletion audit record |

## Audit event schema

Audit events are append-only and never updated through application code. The event contains a UUID, occurred timestamp, actor user and employee identifiers, acting company, target type and ID, action, before/after JSON with protected-field redaction, source IP, user agent, route, request/correlation ID, impersonator, and reason where required. Audit storage has a retention schedule and restricted query permissions.

## Secrets and configuration

Secrets are injected at runtime from the deployment secret manager or CI environment and are never committed. `.env.example` contains names and safe local defaults only. Encryption keys, JWT signing keys, payment credentials, messaging credentials, storage credentials, and OAuth secrets require rotation procedures. Key rotation accepts the active key and one previous key during a controlled migration window.

## Incident procedure

| Severity | Example | Response target | First actions |
| --- | --- | --- | --- |
| Critical | Tenant data exposure, payment fraud, production compromise | Immediate | Stop affected entry points, revoke credentials, preserve evidence, notify incident owner, begin scope analysis |
| High | Authorization defect, persistent queue failure, corrupted price output | Same business day | Disable affected feature flag, identify affected records, prepare corrective transaction, communicate status |
| Medium | Isolated failure with workaround, non-sensitive integration outage | Next business day | Create incident record, retry or repair safely, monitor recurrence |
| Low | Cosmetic or localized non-blocking issue | Scheduled | Record, prioritize and include in planned release |

The incident lead documents timeline, scope, mitigations, customer impact, recovery validation, and preventive actions in the repository issue tracker. A corrective migration or code change is peer reviewed and deployed through the standard pipeline; direct production database edits are prohibited except through documented break-glass procedure.

## Privacy implementation

The product separates account profile, verification documents, transaction history, communications, and operational evidence to apply different retention rules. The privacy workflow supports consent records, notification preferences, self-service data export, verified deletion requests, legal holds, and retention jobs. Data exports are generated asynchronously, encrypted at rest, expire automatically, and are available only to the requesting verified customer or authorized company data controller.
