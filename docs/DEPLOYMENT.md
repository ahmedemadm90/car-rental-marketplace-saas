# Deployment and Operations Runbook

## Release topology

A release comprises the `platform` PHP-FPM image, Nginx edge service, Redis queue/cache/session service, MySQL database, queue workers, and scheduler. Web assets are built before image creation. The same image digest must be used by the web process, workers, and scheduler. Production never mounts source code into containers.

| Service | Runtime responsibility | Scaling rule |
| --- | --- | --- |
| Nginx | TLS termination behind managed load balancer, static assets, PHP routing | Horizontally scalable |
| Platform | Laravel web and API requests | Horizontally scalable; stateless |
| Queue workers | Notifications, payment callbacks, refunds, exports, maintenance jobs, analytics | Scale by queue latency and priority |
| Scheduler | Runs scheduled commands exactly once | One active replica with a leader guard |
| MySQL | Durable transactional data | Managed HA database with point-in-time recovery |
| Redis | Cache, sessions, queue transport, rate limits | Managed Redis with persistence and alerting |
| Object storage | Private documents, signed contracts, inspection media, exports | Versioning, encryption and lifecycle policy |

## Release procedure

1. Require green GitHub Actions checks, reviewed pull request, approved migration assessment, and a successful staging smoke test.
2. Build the immutable image from the tagged commit. Record image digest and release version in deployment metadata.
3. Provision or rotate secrets outside Git. Confirm the new environment has `APP_KEY`, JWT secret, database credentials, object storage credentials, provider keys, and public URLs.
4. Deploy one platform instance in maintenance-aware mode, run `php artisan migrate --force`, then `php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache`, and `php artisan l5-swagger:generate`.
5. Start the worker and scheduler images at the same digest. Verify `critical`, `billing`, `notifications`, `exports`, and `default` queue health separately.
6. Shift traffic after `/up`, authenticated API, database, Redis, queue, and upload health checks pass. Watch error rate, p95 latency, failed jobs, payment callback errors, and unauthorized access spikes.
7. Publish the release tag and attach migration, validation, and rollback evidence to the deployment record.

## Database migration safety

Migrations follow expand–migrate–contract. An expand migration introduces a compatible new table/column/index. A resumable backfill job migrates data while old and new code coexist. A later release changes reads to the new representation. A final contract migration removes obsolete structures only after verification. Releases do not rely on immediate destructive migration, manually edited production rows, or long blocking table rewrites.

Before a migration, verify current backup restore point, row counts, index creation plan, lock risk, and tenant impact. Use online schema tooling for large production tables. After migration, run integrity queries and verify queue jobs can read the new schema.

## Queue operations

| Queue | Work | Priority and response |
| --- | --- | --- |
| `critical` | Reservation expiry, inventory release, contractual time limits | Highest priority; page on sustained latency |
| `billing` | Payment capture, refunds, wallet entries, invoices, commission | Idempotent only; reconcile and page on failures |
| `notifications` | Email, SMS, WhatsApp and push delivery | Retry with provider-aware backoff |
| `exports` | Reports and data-subject exports | Isolated from customer-impacting work |
| `default` | Non-critical domain events and maintenance tasks | Monitored and capacity-managed |

Failed jobs are retained with exception, correlation ID, tenant ID, and payload references that omit secrets. The operator decides whether each failed action is safe to retry. Never bulk-retry payment or refund jobs without provider idempotency and reconciliation checks.

## Backup and recovery

MySQL uses encrypted daily full backups, point-in-time binary log recovery, and tested cross-region copies according to the organization’s recovery objectives. Object storage uses versioning, retention locks for signed contracts where legally appropriate, and separate export expiration policy. Redis is not the source of record; queues can be reconstructed from database outbox events when necessary.

A quarterly recovery exercise restores a production-sized anonymized copy into an isolated environment, validates tenant counts, reservation and ledger totals, signed document hashes, and application login. The recovery evidence records duration, data point recovered, gaps, and corrective action.

## Rollback

If a release causes material customer harm, disable the affected feature flag or route first. Roll back application containers to the last known-good digest only when database schema remains compatible. For data changes, apply an explicitly reviewed forward-fix migration or compensating transaction; do not restore the entire database solely to undo a single application defect without incident-lead approval. Preserve logs, audit events, queue payload references, image digests, and timeline before cleanup.
