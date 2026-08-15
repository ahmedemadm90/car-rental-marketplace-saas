# Mobile Applications

VoyagerRent ships two native Flutter applications. The **customer application** serves discovery, booking, trips, wallet, documents, agreements, support, notifications, maps, camera capture, and QR entry points. The **employee application** serves branch tasks, handovers, inspections, vehicle lookup, camera evidence, damage reporting, maintenance operations, and offline synchronization.

| Application | Package | Primary roles | Native targets |
| --- | --- | --- | --- |
| Customer | `apps/customer_mobile` | Customer, authorized contract signer | Android and iOS |
| Employee | `apps/employee_mobile` | Counter agent, branch manager, fleet manager, assigned operations staff | Android and iOS |
| Shared core | `packages/rental_mobile_core` | Secure API access, encrypted operation queue, device session and synchronization contract | Flutter package |

## Offline model

The mobile applications never infer a completed server operation from local UI state. They persist user actions as encrypted, durable operations, each with a UUIDv7 idempotency key. When connectivity returns, the synchronizer sends each operation in creation order. The backend records that key, making replay safe. Permanent validation failures remain visible to the user with their original local evidence; transient failures remain queued with an incremented attempt count.

| Data category | Offline behaviour | Conflict authority |
| --- | --- | --- |
| Search results and catalogue | Cached for display with freshness timestamp | Server refresh replaces cache |
| Confirmed reservation and trip details | Readable offline after first load | Server reservation version wins; local changes become explicit amendment requests |
| Inspection and damage drafts | Encrypted draft plus queued evidence metadata | Server accepts first valid idempotent operation; later conflict requires staff review |
| Document media | Local encrypted upload queue; binary uploads retry independently | Server checksum and document version control |
| Wallet and payment data | Read-only cached summaries; no offline payment capture | Server ledger is sole financial authority |
| Support drafts | Encrypted queued messages | Server message chronology is authoritative |

## Device security

Session tokens and user identity are stored in platform secure storage. Biometric unlock is an additional local gate before restoring an active session; it never replaces backend token validation. The user can revoke a device from the account, and the backend invalidates the device registration on logout or suspicious activity. Sensitive notifications contain no document number, payment token, or full reservation data on the lock screen.

## Native setup

The repository includes Android and iOS Flutter project layers. Configure the following values in environment-specific native build files or secure CI variables before distribution: Android application signing key, iOS signing team and provisioning profile, Firebase project configuration, maps API key restricted by package/bundle ID, API base URL, and notification sender configuration. The values are never committed.

Camera, photo library, location, notification, and biometric permissions must use localized purpose messages. The product requests each capability at the action that needs it, provides a non-permission alternative where possible, and handles denial without blocking unrelated flows.

## Verification

The customer and employee applications have been generated with Android and iOS projects and validated using Flutter static analysis and widget tests. Run the following from each application directory:

```bash
flutter pub get
flutter analyze
flutter test
```

Use device or emulator integration tests for camera, maps, QR, biometrics, notification permission, and encrypted-database verification in the release pipeline, because those require platform services unavailable to headless widget tests.
