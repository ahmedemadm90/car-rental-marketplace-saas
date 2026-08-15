# API Integration Guide

The VoyagerRent API is versioned under `/api/v1`. Mobile clients authenticate with short-lived JWT bearer tokens. Browser pages use Laravel session authentication and Sanctum where a first-party token is needed. Generated interactive API documentation is available at `/api/documentation` after deployment and is produced from the repository’s OpenAPI attributes.

## Authentication

| Client | Method | Session handling |
| --- | --- | --- |
| Flutter customer and employee apps | `POST /api/v1/auth/mobile/login` then `Authorization: Bearer <JWT>` | Device registration stores a push token and client metadata; logout invalidates the JWT and revokes that device |
| Browser web | Secure, encrypted Laravel session cookie | Session ID is regenerated on successful sign-in; password reset invalidates active sessions |
| Internal service | Scoped Sanctum personal access token | Token abilities and rotation are controlled per service account |

## Core endpoints

| Endpoint | Authentication | Purpose |
| --- | --- | --- |
| `POST /auth/mobile/login` | None | Validate credentials, register a device, and receive JWT access token |
| `POST /auth/mobile/refresh` | JWT | Rotate a valid mobile token |
| `POST /auth/mobile/logout` | JWT | Revoke current mobile device session and invalidate token |
| `GET /me` | JWT | Get profile, tenant memberships, and active permissions |
| `GET /marketplace/search` | None | Search public inventory, filter offers, evaluate capacity, and calculate transparent totals |
| `POST /reservations` | JWT | Create an idempotent time-limited reservation hold |
| `GET /reservations/{reservation}` | JWT | Retrieve an owned reservation with vehicle and branch data |
| `GET /company/context` | JWT + `X-Company-Id` | Retrieve active tenant context after membership/role validation |

## Tenant scope

Company operational APIs require `X-Company-Id` containing a company numeric ID, ULID, or slug. The backend resolves active company context, verifies user membership or platform support entitlement, scopes model queries, sets permission team scope, prefixes cache/storage access, and records the company in audit events. Client-supplied body fields never establish tenant authority.

## Idempotency

All requests that create a reservation, payment, refund, wallet entry, inspection submission, damage report, or operation sync record require an `Idempotency-Key` header. The key must be unique to the intended business action and remains valid for at least twenty-four hours. A repeated request returns the original response with `Idempotency-Replayed: true`, rather than duplicating a financial or inventory effect.

## Error response

Validation errors use Laravel’s structured 422 response with field-level errors. Domain conflicts return a 422 response with an actionable message. Authentication failures return 401, authorization failures return 403, absent records return 404 without cross-tenant existence disclosure, and throttled requests return 429 with `Retry-After`. All responses include `X-Request-Id`; clients must include it in support reports.

## Pagination and time

List endpoints restrict `per_page` to a bounded range and return standard pagination metadata. All timestamps are supplied and returned in ISO 8601 UTC. Monetary values are integer minor units plus an ISO currency code; client interfaces must format amounts only at presentation time and must not perform authoritative financial arithmetic using floating point values.
