<?php

declare(strict_types=1);

namespace App\Livewire\Staff\Marks;

use App\Livewire\Staff\Concerns\HasStaffAuth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Modules\AcademicManagement\Actions\ExtendMarkWindow;
use Modules\AcademicManagement\Exceptions\MarksException;
use Modules\AcademicManagement\Models\MarkEntryWindow;
use Modules\Authorization\Data\PermissionKey;

/**
 * Principal/Deputy: extend an open mark-entry window.
 */
final class MarkWindowExtension extends Component
{
    use HasStaffAuth;

    public int $extendingId = 0;

    public string $newClosesAt = '';

    public string $extendReason = '';

    public string $flashMessage = '';

    public string $flashType = '';

    public function mount(): void
    {
        $this->requirePermission(PermissionKey::MARK_WINDOW_EXTEND);
    }

    public function openWindows(): Collection
    {
        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            return collect();
        }

        return DB::table('mark_entry_windows as mew')
            ->leftJoin('class_groups as cg', 'cg.id', '=', 'mew.class_group_id')
            ->leftJoin('institution_subject_offerings as iso', 'iso.id', '=', 'mew.subject_offering_id')
            ->leftJoin('subjects as s', 's.id', '=', 'iso.subject_id')
            ->where('mew.institution_semester_id', $scope['institution_semester_id'])
            ->whereIn('mew.status', ['open', 'extended'])
            ->orderBy('mew.closes_at')
            ->get([
                'mew.id', 'mew.name_ar', 'mew.opens_at', 'mew.closes_at', 'mew.status', 'mew.extension_history',
                'cg.name_ar as class_group_name',
                's.name_ar as subject_name',
            ]);
    }

    public function startExtend(int $windowId): void
    {
        $this->requirePermission(PermissionKey::MARK_WINDOW_EXTEND);
        $this->extendingId = $windowId;
        $this->newClosesAt = '';
        $this->extendReason = '';
    }

    public function confirmExtend(): void
    {
        $this->requirePermission(PermissionKey::MARK_WINDOW_EXTEND);

        $this->validate([
            'newClosesAt' => ['required', 'date'],
            'extendReason' => ['required', 'string', 'min:5'],
        ]);

        $scope = $this->staffScope();
        $window = MarkEntryWindow::where('id', $this->extendingId)
            ->where('institution_semester_id', $scope['institution_semester_id'])
            ->first();

        if (! $window) {
            abort(404);
        }

        try {
            app(ExtendMarkWindow::class)(
                window: $window,
                newClosesAt: new \DateTimeImmutable($this->newClosesAt),
                reason: $this->extendReason,
                actorRef: $this->staffActorReference(),
            );
            $this->extendingId = 0;
            $this->newClosesAt = '';
            $this->extendReason = '';
            $this->flash('Window extended.', 'success');
        } catch (MarksException $e) {
            $this->flash($e->getMessage(), 'error');
        }
    }

    public function cancelExtend(): void
    {
        $this->extendingId = 0;
        $this->newClosesAt = '';
        $this->extendReason = '';
    }

    public function render(): View
    {
        return view('livewire.staff.marks.window-extension', [
            'openWindows' => $this->openWindows(),
        ])->layout('layouts.staff');
    }

    private function flash(string $message, string $type = 'success'): void
    {
        $this->flashMessage = $message;
        $this->flashType = $type;
    }
}
