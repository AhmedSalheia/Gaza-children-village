<?php

declare(strict_types=1);

namespace Modules\Requests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One proposal attempt within a StudentCorrectionRequest.
 *
 * Multiple rows per request are created when a guardian resubmits after
 * a secretary requests clarification. The latest row (highest submission_sequence)
 * is the active proposal; prior rows are retained for the audit trail.
 *
 * proposed_value and old_value_snapshot may contain service-layer-encrypted
 * ciphertext for sensitive fields (birth_date, identifier_correction).
 * The model does NOT apply an encrypted cast — CorrectionRequestService and
 * CorrectionApplicationService own encryption/decryption so that only
 * sensitive fields pay the encryption cost.
 */
final class CorrectionFieldProposal extends Model
{
    protected $table = 'correction_field_proposals';

    /** All columns excluded from mass assignment — direct property assignment required. */
    protected $guarded = ['*'];

    /** @var array<string, string> */
    protected $casts = [
        'submission_sequence' => 'integer',
    ];

    /** @return BelongsTo<StudentCorrectionRequest, $this> */
    public function correctionRequest(): BelongsTo
    {
        return $this->belongsTo(StudentCorrectionRequest::class, 'correction_request_id');
    }
}
