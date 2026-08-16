<?php

declare(strict_types=1);

namespace Modules\Documents\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Per-(type, institution, year) sequential document number counter.
 *
 * `current_sequence` is incremented atomically via a pessimistic `lockForUpdate`
 * in DocumentNumberService::next() to prevent duplicate numbers when concurrent
 * requests race to issue documents of the same type in the same institution/year.
 *
 * Rows are created lazily on the first issuance of a given (type, institution, year)
 * combination; subsequent calls increment.
 */
final class DocumentTypeSequence extends Model
{
    /** @var list<string> All columns excluded from mass assignment. */
    protected $guarded = ['*'];

    /** @var array<string, string> */
    protected $casts = [
        'institution_id' => 'integer',
        'year' => 'integer',
        'current_sequence' => 'integer',
    ];
}
