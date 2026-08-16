<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Documents;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Modules\Authorization\Data\PermissionKey;

/**
 * Admin portal: document approval queue.
 *
 * Shows all requests in 'awaiting_approval' status across all institutions.
 * Gated on DOCUMENT_APPROVE permission.
 */
final class DocumentApprovalQueue extends Component
{
    use HasAdminAuth;

    public function mount(): void
    {
        $this->requirePermission(PermissionKey::DOCUMENT_APPROVE);
    }

    public function render(): View
    {
        $requests = DB::table('student_document_requests as dr')
            ->join('student_profiles as sp', 'sp.id', '=', 'dr.student_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->join('institutions as inst', 'inst.id', '=', 'dr.institution_id')
            ->where('dr.status', 'awaiting_approval')
            ->select(
                'dr.id',
                'dr.document_type_code',
                'dr.locale',
                'dr.status',
                'dr.created_at',
                'dr.submitted_at',
                'p.full_name_ar as student_name_ar',
                'inst.name_ar as institution_name_ar',
            )
            ->orderBy('dr.submitted_at')
            ->get();

        return view('livewire.admin.documents.document-approval-queue', [
            'requests' => $requests,
        ])->layout('layouts.admin');
    }
}
