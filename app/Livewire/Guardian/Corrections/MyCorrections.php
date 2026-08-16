<?php

declare(strict_types=1);

namespace App\Livewire\Guardian\Corrections;

use App\Livewire\Guardian\Concerns\HasGuardianAuth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Guardian portal: list of all correction requests for the guardian's students.
 *
 * Shows requests across all portal-eligible children, grouped by student.
 * Each row links to CorrectionDetail for the full timeline.
 *
 * Access: any authenticated guardian with at least one portal-eligible student.
 */
final class MyCorrections extends Component
{
    use HasGuardianAuth;

    public function mount(): void
    {
        if (! $this->hasGuardianProfile()) {
            abort(403, 'No guardian profile linked.');
        }
    }

    public function render(): View
    {
        $guardianAccountId = (int) auth('guardian')->id();
        $requests = $this->loadRequests($guardianAccountId);

        return view('livewire.guardian.corrections.my-corrections', [
            'requests' => $requests,
        ])->layout('layouts.guardian');
    }

    private function loadRequests(int $guardianAccountId): Collection
    {
        return DB::table('student_correction_requests as scr')
            ->join('workflow_instances as wi', 'wi.id', '=', 'scr.workflow_instance_id')
            ->join('student_profiles as sp', 'sp.id', '=', 'scr.student_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->leftJoin('correction_field_proposals as cfp', function ($join): void {
                $join->on('cfp.correction_request_id', '=', 'scr.id')
                    ->whereRaw('cfp.submission_sequence = (SELECT MAX(cfp2.submission_sequence) FROM correction_field_proposals cfp2 WHERE cfp2.correction_request_id = scr.id)');
            })
            ->where('scr.guardian_account_id', $guardianAccountId)
            ->select(
                'scr.id',
                'scr.field_catalogue_code',
                'scr.classification',
                'scr.conflict_flag',
                'scr.applied_at',
                'scr.created_at',
                'wi.current_state',
                'p.full_name_ar as student_name',
                'sp.id as student_profile_id',
            )
            ->orderByDesc('scr.created_at')
            ->get();
    }
}
