<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Admin portal landing dashboard.
 *
 * Each metric is gated by the corresponding permission so that role-restricted
 * admins (calendar_manager, account_manager, etc.) do not receive data they are
 * not permitted to view. The view receives a permission map alongside each
 * metric so cards can be conditionally rendered.
 *
 * Data access rules:
 *  student.*     → student.view
 *  enrollment.*  → enrollment.view
 *  promotion.*   → enrollment.promote
 *  import.*      → import.review
 *  institution.* → institution.view
 *  semester.*    → institution_semester.view (calendar-related permissions)
 */
final class Dashboard extends Component
{
    use HasAdminAuth;

    /**
     * The dashboard is accessible to every authenticated admin; no specific
     * permission is required to reach the landing page. The auth:admin
     * middleware on the route group already enforces authentication.
     * Individual metric sections are gated by their own permissions below.
     */
    public function mount(): void
    {
        if (auth('admin')->guest()) {
            abort(403);
        }
    }

    /** @return array<string, int> lifecycle_status → count */
    public function studentCounts(): array
    {
        if (! $this->adminCan('student.view')) {
            return [];
        }

        return DB::table('student_profiles')
            ->select('lifecycle_status', DB::raw('count(*) as total'))
            ->groupBy('lifecycle_status')
            ->pluck('total', 'lifecycle_status')
            ->toArray();
    }

    public function activeEnrollmentCount(): int
    {
        if (! $this->adminCan('enrollment.view')) {
            return 0;
        }

        return (int) DB::table('student_enrollments')
            ->where('enrollment_status', 'active')
            ->count();
    }

    public function pendingPromotionCount(): int
    {
        if (! $this->adminCan('enrollment.promote')) {
            return 0;
        }

        return (int) DB::table('promotion_proposals')
            ->where('review_status', 'pending')
            ->count();
    }

    public function activeImportCount(): int
    {
        if (! $this->adminCan('import.review')) {
            return 0;
        }

        return (int) DB::table('import_batches')
            ->whereNotIn('status', ['completed', 'completed_with_errors', 'cancelled'])
            ->count();
    }

    public function institutionCount(): int
    {
        if (! $this->adminCan('institution.view')) {
            return 0;
        }

        return (int) DB::table('institutions')->where('is_active', true)->count();
    }

    public function openSemesterCount(): int
    {
        if (! $this->adminCan('institution_semester.view')) {
            return 0;
        }

        return (int) DB::table('institution_semesters')->where('status', 'open')->count();
    }

    /** Recent import batches, visible only to admins with import.review permission. */
    public function recentBatches(): Collection
    {
        if (! $this->adminCan('import.review')) {
            return collect();
        }

        return DB::table('import_batches')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'status', 'created_at', 'original_filename']);
    }

    public function render(): View
    {
        return view('livewire.admin.dashboard', [
            'studentCounts' => $this->studentCounts(),
            'activeEnrollmentCount' => $this->activeEnrollmentCount(),
            'pendingPromotionCount' => $this->pendingPromotionCount(),
            'activeImportCount' => $this->activeImportCount(),
            'institutionCount' => $this->institutionCount(),
            'openSemesterCount' => $this->openSemesterCount(),
            'recentBatches' => $this->recentBatches(),
            // Permission flags for conditional rendering in the view
            'canViewStudents' => $this->adminCan('student.view'),
            'canCreateStudents' => $this->adminCan('student.create'),
            'canViewEnrollments' => $this->adminCan('enrollment.view'),
            'canPromote' => $this->adminCan('enrollment.promote'),
            'canUploadImports' => $this->adminCan('import.upload'),
            'canReviewImports' => $this->adminCan('import.review'),
            'canViewInstitutions' => $this->adminCan('institution.view'),
            'canViewSemesters' => $this->adminCan('institution_semester.view'),
        ])->layout('layouts.admin');
    }
}
