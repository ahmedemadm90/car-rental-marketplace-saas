# Data Retention and Deletion

VoyagerRent uses retention categories instead of one global deletion rule. The category is applied at creation, reviewed by the responsible platform or tenant data controller, and enforced by scheduled jobs after legal-hold evaluation. A deletion request is a governed workflow, not a direct database delete.

| Data category | Examples | Retention control | Deletion outcome |
| --- | --- | --- | --- |
| Account identity | Name, email, phone, language, device registrations | Retained while account is active and for the approved account-closure period | Pseudonymize identity, revoke sessions and devices, remove non-required profile content |
| Verification documents | Licence, identity document, verification status | Retain only for approved verification, rental, fraud-prevention, and legal needs | Delete private object and document metadata when no retention basis remains |
| Reservation and contract | Pricing snapshot, agreement, signature evidence, pickup/return record | Retain according to applicable rental, accounting, and limitation obligations | Preserve immutable contractual record until retention expires; then pseudonymize customer identifiers where permitted |
| Finance | Payments, refunds, wallet ledger, invoices, expense records | Retain under applicable accounting and tax requirements | Do not alter ledger; pseudonymize associated personal fields only after approval |
| Operations | Inspections, damage evidence, maintenance, fleet availability | Retain for dispute, safety, insurance, and fleet history period | Delete or anonymize media and actor identifiers when no operational basis remains |
| Communications | Support tickets, notifications, reviews | Retain for service, quality, dispute and moderation period | Delete attachments and pseudonymize personal content per category |
| Audit | Authorization, finance, export, impersonation, document access | Restricted immutable record under security and compliance retention | Never silently alter; expire only through governed archival/deletion process |

## Privacy request workflow

A customer can request access, export, correction, or deletion through the authenticated support route. The request is verified against current account authentication, logged in the audit record, assigned to the relevant tenant or platform data controller, and checked for legal hold, open dispute, contract, financial, insurance, and regulatory obligations. The result documents which data was delivered, corrected, deleted, pseudonymized, retained, or excluded with the reason.

Exports are generated asynchronously in protected storage, encrypted at rest, available through a time-limited signed link, and audited on download. An export cannot be accessed by a support operator unless that operator has explicit purpose-limited access and the action is audited.

## Deletion mechanics

The deletion worker uses a transaction for database state, object storage deletion/version marker, search index cleanup, cache invalidation, device token revocation, and an immutable deletion audit event. It retries safely through a stable request ID. When a record must remain for financial or contractual reasons, direct identifying fields are pseudonymized instead of deleting linked ledger or contract records. Backup expiry follows the backup lifecycle; restored backups remain access-controlled and are not reintroduced into production without applying current deletion suppression records.
