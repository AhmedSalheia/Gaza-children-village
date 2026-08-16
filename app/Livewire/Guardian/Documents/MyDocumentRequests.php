<?php

declare(strict_types=1);

namespace App\Livewire\Guardian\Documents;

use App\Livewire\Guardian\Concerns\HasGuardianAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Guardian portal: list of this guardian's document requests.
 *
 * Shows all requests for all of the guardian's portal-eligible students,
 * ordered newest first. Each row shows the document type, student name,
 * request status, and a link to the detail page.
 */
final class MyDocumentRequests extends Component
{
    use HasGuardianAuth;

    public function mount(): void
    {
        if (! $this->hasGuardianProfile()) {
            abort(403, 'No guardian profile linked to this account.');
        }
    }

    public function render(): View
    {
        $guardianAccountId = (int) auth('guardian')->id();
        $requests          = $this->loadRequests($guardianAccountId);

        return view('livewire.guardian.documents.my-document-requests', [
            'requests' => $requests,
        ])->layout('layouts.guardian');
    }

    private function loadRequests(int $guardianAccountId): \Illuminate\Support\Collection
    {
        return DB::table('student_document_requests as dr')
            ->join('student_profiles as sp', 'sp.id', '=', 'dr.student_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->leftJoin('issued_documents as id', function ($j): void {
                $j->on('id.request_id', '=', 'dr.id')->whereNull('id.cancelled_at');
            })
            ->where('dr.requested_by_account_id', $guardianAccountId)
            ->where('dr.requested_by_actor_type', 'guardian')
            ->select(
                'dr.id',
                'dr.document_type_code',
                'dr.locale',
                'dr.status',
                'dr.created_at',
                'p.full_name_ar as student_name_ar',
                'id.id as issued_document_id',
                'id.document_number',
            )
            ->orderByDesc('dr.created_at')
            ->get();
    }
}
