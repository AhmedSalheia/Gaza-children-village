# ADR F12 — Person Model and Privacy Classification

**Status:** Accepted  
**Adopted:** 2026-08-14  
**Author:** GCV DATA Engineering  
**Implements:** Foundation Phase F12

---

## Context

The GCV DATA system manages real human beings—staff members, guardians, and eventually students and beneficiaries—across multiple institutions. Each human must be represented exactly once in the system as a stable `Person` record, regardless of how many roles, profiles, or accounts they accumulate over time.

This ADR establishes the canonical Person entity, its child identifier records, and the privacy classification for every proposed field. No F13 table may be created until this ADR is complete and accepted.

---

## Decision

### 1. Person Entity

A `Person` is the stable canonical record for one real human being.

#### Approved Fields

| Field | Type | Nullable | Description |
|---|---|---|---|
| `id` | bigint (PK) | No | Surrogate internal ID. Never a national ID. |
| `full_name_ar` | string | No | Full Arabic name. Required. |
| `full_name_en` | string | Yes | Full English name. Optional. |
| `birth_date` | date | Yes | Date of birth. Optional. |
| `birth_date_precision` | string | Yes | `exact`, `month`, `year`, `unknown`. |
| `created_at` | timestamp | No | Record creation time. |
| `updated_at` | timestamp | No | Last update time. |

#### Explicitly Deferred Fields

The following fields require domain-specific decisions and explicit approval before addition. They must NOT be added in F11–F15:

- Gender/sex
- Marital status
- Religion or sect
- Disability or health information
- Physical address
- Deceased state or death date
- Civil-registry record reference
- Nationality or passport-issuing country (covered instead by PersonIdentifier)

### 2. PersonIdentifier Entity

A `PersonIdentifier` is a child record attached to a Person, representing one government-issued or institutional identifier.

#### Design Principles

- A Person may have zero or more PersonIdentifier records.
- A Person may exist with no identifier at all.
- Identifiers are child records; Person stability is never conditional on identifier presence.
- No identifier is the Person primary key.
- A single normalized active identifier cannot silently belong to two people. Concurrent writes are protected at the database level (unique index on fingerprint column) in addition to application-level checks.
- Historical/superseded identifiers remain on record and their fingerprints remain reserved so that re-use can be detected and reviewed.

#### Approved Identifier Types

| Value | Description |
|---|---|
| `ps_national_id` | Palestinian National Authority national ID (9 digits) |
| `passport` | Any internationally recognized passport |
| `other` | Any other government-issued or institutional document |

Additional types must be approved and documented before addition.

#### Palestinian National ID Normalization

Normalization is required before storage (fingerprint) and lookup:

1. Convert Arabic-Indic digits (٠١٢٣٤٥٦٧٨٩) to ASCII digits (0–9).
2. Remove all spaces and hyphens.
3. Require exactly 9 numeric digits.
4. Do not implement automatic checksum validation unless a test specification for valid/invalid examples is approved.
5. Do not assume the civil registry is authoritative for correction.

**Test vectors** (normalization must produce identical output for all variants):

| Input | Expected Output | Notes |
|---|---|---|
| `123456789` | `123456789` | Already normalized |
| `123-456-789` | `123456789` | Hyphens removed |
| `123 456 789` | `123456789` | Spaces removed |
| `١٢٣٤٥٦٧٨٩` | `123456789` | Arabic digits converted |
| `١٢٣-٤٥٦-٧٨٩` | `123456789` | Arabic digits + hyphens |
| `12345678` | (reject) | Too short (8 digits) |
| `1234567890` | (reject) | Too long (10 digits) |
| `12345678A` | (reject) | Non-numeric character |
| `` | (reject) | Empty string |

**Masking:** display as `XXXXX6789` (last 4 digits visible, all others replaced with X).

#### PersonIdentifier Fields

| Field | Type | Nullable | Privacy Class | Description |
|---|---|---|---|---|
| `id` | bigint (PK) | No | Internal | Surrogate ID |
| `person_id` | bigint (FK) | No | Internal | Parent Person |
| `type` | string | No | Internal | Identifier type enum |
| `country_code` | string (2) | Yes | Internal | ISO-3166-1 alpha-2 issuing country |
| `issuer_name` | string | Yes | Internal | Issuing authority name |
| `identifier_encrypted` | text | No | Restricted | Laravel-encrypted raw value. Never serialized. Never logged. |
| `lookup_fingerprint` | string | No | Restricted | HMAC-SHA256 of normalized value using `IDENTIFIER_LOOKUP_KEY`. Unique index among current records. |
| `is_current` | boolean | No | Internal | True for the most recent non-superseded version |
| `superseded_by_id` | bigint (FK) | Yes | Internal | Forward link to the correcting identifier record |
| `superseded_at` | timestamp | Yes | Internal | When this record was replaced |
| `verified_at` | timestamp | Yes | Internal | When identity was verified |
| `verification_source` | string | Yes | Internal | Who/what performed verification |
| `effective_from` | date | Yes | Internal | When this identifier became effective |
| `effective_until` | date | Yes | Internal | When this identifier stopped being valid |
| `created_at` | timestamp | No | Internal | Record creation |
| `updated_at` | timestamp | No | Internal | Last update |

#### Identifier Correction Semantics

Correction is **append-only, never overwrite**:

1. A new PersonIdentifier record is created for the corrected value.
2. The old record's `is_current` is set to `false`, `superseded_by_id` is set to the new record's ID, and `superseded_at` is set to now. This is atomic.
3. The old record's `lookup_fingerprint` remains in the table but is no longer `is_current`.
4. The correction must record: actor, source (evidence type), and reason (plain text).
5. Correction history stores record references and metadata—never plaintext identifiers.
6. The Person's surrogate `id` is unchanged. No cascade to profiles or accounts.
7. The historical fingerprint remains reserved so future writes using the same normalized value produce a detectable collision rather than a silent duplicate.

---

## Privacy Classification Matrix

### Classification Levels

| Level | Label | Definition |
|---|---|---|
| 0 | **Internal** | System metadata; no intrinsic sensitivity; freely visible in authorized system contexts. |
| 1 | **Personal** | Information about a real person. Access controlled by role. Visible to institution staff with appropriate grants. |
| 2 | **Restricted** | High-sensitivity PII (government IDs, biometrics). Access requires explicit permission. Not visible in default staff views. |
| 3 | **Restricted Contact** | Phone/email values. Intermediate sensitivity. Used for communication; encrypted; masked by default. |
| 4 | **Security Secret** | Passwords, tokens, challenge values, session secrets. Never readable by any user. Never visible in any response. |

### Field Treatment Matrix

| Field | Storage | Display | Search | Mutation | Logging | Auth Use | Audit | Export | Central Access | Institution-Scoped Access |
|---|---|---|---|---|---|---|---|---|---|---|
| **Person.id** | Plaintext | Visible | Indexed | Immutable | Allowed | Indirect | Reference only | Allowed | Yes | Yes (own institution) |
| **Person.full_name_ar** | Plaintext | Default visible | No index | Authorized update | Name only | No | Allowed | Authorized | Yes | Yes (own institution) |
| **Person.full_name_en** | Plaintext | Default visible | No index | Authorized update | Name only | No | Allowed | Authorized | Yes | Yes (own institution) |
| **Person.birth_date** | Plaintext | Masked (year only by default) | No | Authorized update | Omit | No | Allowed | Restricted | Yes | Restricted |
| **Person.birth_date_precision** | Plaintext | With birth_date | No | With birth_date | Omit | No | Allowed | Restricted | Yes | Restricted |
| **PersonIdentifier.identifier_encrypted** | Laravel Encrypt | Never | Never | Append-only | Never | Never | Never | Never | Explicit reveal only | Never |
| **PersonIdentifier.lookup_fingerprint** | HMAC hash | Never | Exact match only | Append-only | Never | Never | Never | Never | Yes (lookup) | No |
| **PersonIdentifier (masked)** | N/A | Default visible | No | N/A | Masked only | No | Masked only | Masked only | Yes | Yes (own institution) |
| **ContactPoint (encrypted)** | Laravel Encrypt | Never | Never | Append-only | Never | Delivery only | Never | Never | Explicit reveal only | Never |
| **ContactPoint (masked)** | N/A | Default visible | No | N/A | Masked only | No | Masked only | Masked only | Yes | Yes (own institution) |
| **Account.password** | Bcrypt hash | Never | Never | Rotate only | Never | Verification | Never | Never | Never | Never |
| **Challenge.token_hash** | SHA-256 hash | Never | Never | Append-only | Never | One-time | Never | Never | Never | Never |
| **Session secret** | Encrypted | Never | Never | Rotate only | Never | Validation | Never | Never | Never | Never |

---

## Architectural Commitments

### Identity

1. **One Person per real human.** Two Person records must never represent the same individual. Merging requires an explicit workflow with human review—never automatic.
2. **Names are not identity.** Identical names do not trigger automatic merge and are not sufficient to identify a unique person.
3. **Missing national ID is valid.** A Person may exist indefinitely without any PersonIdentifier.
4. **Stable surrogate ID.** The Person's database primary key never changes, regardless of identifier corrections, name changes, or profile updates.
5. **Duplicate detection produces a review condition.** When a normalized identifier fingerprint matches an existing current record, the operation must fail with a domain conflict error. A human must review and resolve it. The system never auto-merges.

### Profiles and Accounts

6. **One Person may later have staff, guardian, student, or beneficiary profiles.** Each profile type is a separate entity—not a discriminator column on Person.
7. **Different portal accounts do not duplicate Person.** A StaffAccount and a GuardianAccount may eventually link to the same Person; Person is shared, accounts are separate.
8. **Profile creation never creates an account.** Account existence is always explicit.
9. **Account creation never creates a Person.** Person must exist independently before a profile or account is attached.

### Civil Registry

10. **Gaza civil-registry data is advisory.** Civil-registry lookup, import, and synchronization are explicitly deferred. No civil-registry table, import pipeline, or API integration is created in F11–F18.
11. **Civil-registry data never silently overwrites a system record.** Any future integration must route through human-reviewed reconciliation.

### Identifiers

12. **Identifier correction is historical.** Old records are superseded, never deleted or overwritten.
13. **Full identifier visibility requires explicit permission.** Masked representation is the default. The raw decrypted value is accessible only through an authorized explicit reveal action.
14. **The IDENTIFIER_LOOKUP_KEY is separate from APP_KEY.** It is a dedicated secret used exclusively for the HMAC fingerprint derivation. If the secret is absent in a production environment, all fingerprint operations must fail clearly—not silently fall back to an insecure value.
15. **Raw identifiers never enter logs, audit payloads, URLs, cache keys, or exceptions.** This applies to normalization errors as well.

### Future Integration Points

16. **F17/F19 will complete authorization integration.** This ADR defines the data model; permission catalogue implementation is deferred.
17. **F16 will add semester positions, responsibilities, and period scopes.** Person and StaffProfile intentionally omit these fields.
18. **F18 will add generic append-only audit infrastructure.** F13–F15 implementations preserve sufficient actor/reason metadata to bridge into F18 without back-migration.

---

## Normalization Test Vectors (F13 Test Fixtures)

Included in `Modules/People/tests/Feature/F13IdentifierTest.php`.

### Palestinian National ID

```
Valid inputs → normalized form:
  "123456789"       → "123456789"
  "123-456-789"     → "123456789"
  "123 456 789"     → "123456789"
  "١٢٣٤٥٦٧٨٩"      → "123456789"
  "١٢٣-٤٥٦-٧٨٩"    → "123456789"
  " 123456789 "     → "123456789"   (trim)

Invalid inputs → rejection:
  "12345678"        → too short
  "1234567890"      → too long
  "12345678A"       → non-digit character
  ""                → empty
  "ABCDEFGHI"       → no digits

Masked display:
  "123456789"       → "XXXXX6789"
```

---

## Consequences

### Positive

- Person stability is guaranteed regardless of identifier changes.
- Privacy classification is documented before implementation; no guesswork.
- Encryption and fingerprint strategy is unambiguous.
- Historical records support future audit and regulatory reporting.
- Separation of Person from profiles/accounts prevents identity confusion.

### Negative / Trade-offs

- Identifier correction is append-only, which increases table row count over time.
- HMAC fingerprints require careful key management; key rotation requires a full re-fingerprint of the table.
- Absence of automatic merging means duplicate people require human workflow (F17+).

### Deferred

- Guardian profile eligibility and linkage (requires separate approval).
- Civil-registry integration (explicitly excluded from F11–F18).
- Checksum validation for Palestinian national IDs (requires approved test specification).
- F17 permission catalogue (explicitly deferred).
- F18 generic audit infrastructure.
