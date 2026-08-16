# Architecture and Process Diagrams

This document contains Mermaid diagrams illustrating the VoyagerRent platform architecture, single-database multi-tenant isolation, reservation lifecycle, and mobile offline synchronization.

## 1. System Architecture

```mermaid
graph TD
    ClientWeb[Bilingual Web UI <br> Tailwind, Alpine, Livewire] --> Nginx[Nginx Edge / Reverse Proxy]
    ClientCust[Customer Mobile App <br> Flutter + Secure Core] --> Nginx
    ClientEmp[Employee Mobile App <br> Flutter + Secure Core] --> Nginx

    Nginx --> Laravel[Laravel 12 API & App Core]
    
    subgraph Laravel Core Services
        Laravel --> TenantMiddleware[Tenant Resolver Middleware]
        Laravel --> AuthGuard[Sanctum & JWT Guards]
        Laravel --> DomainServices[Pricing, Availability, Wallet Services]
    end

    Laravel --> MySQL[(MySQL 8.4 Single DB <br> Company Isolation Scope)]
    Laravel --> Redis[(Redis 7.4 <br> Cache, Sessions, Queues)]
    Laravel --> Queues[Async Workers <br> Critical, Billing, Notifications]
```

## 2. Reservation & Idempotency Lifecycle

```mermaid
sequenceDiagram
    participant Customer as Customer Client
    participant API as Laravel API
    participant Pricing as Pricing Service
    participant DB as MySQL Database

    Customer->>API: POST /api/v1/reservations (Idempotency-Key)
    API->>DB: Check Idempotency Cache
    alt Replayed Request
        DB-->>API: Return Cached Response
        API-->>Customer: 201/200 (Idempotency-Replayed: true)
    else New Request
        API->>Pricing: Calculate Subtotal, Tax, Deposit
        Pricing-->>API: Immutable Quote Snapshot
        API->>DB: Lock Capacity & Insert Reservation
        DB-->>API: Reservation Created (pending_payment)
        API-->>Customer: 201 Created + Quote Snapshot
    end
```

## 3. Mobile Offline Synchronization

```mermaid
sequenceDiagram
    participant App as Flutter Mobile App
    participant Local as Encrypted SQLCipher DB
    participant Sync as Sync Coordinator
    participant Server as Laravel Backend

    App->>Local: Write Action (Inspection, Photo, Handover)
    Local-->>App: Queued with UUIDv7 ID
    Note over App,Server: Device goes online
    App->>Sync: Trigger Sync Coordinator
    Sync->>Local: Fetch Pending Operations
    loop For Each Operation
        Sync->>Server: POST /api/v1/mobile/sync/{type} (Idempotency-Key)
        Server-->>Sync: 200 OK / Acknowledged
        Sync->>Local: Remove from Pending Queue
    end
```
