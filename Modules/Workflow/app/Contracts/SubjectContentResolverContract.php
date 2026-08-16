<?php

declare(strict_types=1);

namespace Modules\Workflow\Contracts;

/**
 * Domain-owned server-side canonical content resolver.
 *
 * Concrete implementations live in domain modules (Corrections, Documents,
 * FormalRequests) and compute the SHA-256 hash of the current, authoritative
 * subject content by reading the subject record directly from the database.
 *
 * The Workflow module calls this interface at two points:
 *
 *   1. At token issuance time — to capture the content hash the approver is about
 *      to review. The hash is stored server-side in the token row.
 *
 *   2. At approval recording time — to recompute the current content hash and
 *      compare it with the hash stored in the token. If the subject was modified
 *      after the approver loaded the review screen, the hashes differ and the
 *      approval is rejected.
 *
 * This prevents a stale-approval attack: an approver cannot submit a valid token
 * for content they reviewed, if the underlying subject has since changed.
 * Neither the Livewire component nor any other caller can influence the hash by
 * supplying their own string — the content is always read from the database by
 * the domain-owned implementation.
 *
 * Hash contract:
 *   Implementations MUST return exactly 64 lowercase hexadecimal characters
 *   (a canonical SHA-256 hex digest). The Workflow module validates this and
 *   throws if the format is not met.
 */
interface SubjectContentResolverContract
{
    /**
     * Compute and return the SHA-256 hex hash of the current canonical content
     * of the subject identified by ($subjectType, $subjectId).
     *
     * The implementation reads the subject from the database fresh on every call
     * so that changes between load time and submission time are detected.
     *
     * @param  string  $subjectType  Domain model class name (e.g. 'StudentCorrectionRequest')
     * @param  int  $subjectId  Domain model primary key
     * @return string 64 lowercase hex chars (SHA-256 digest)
     */
    public function computeCanonicalHash(string $subjectType, int $subjectId): string;
}
