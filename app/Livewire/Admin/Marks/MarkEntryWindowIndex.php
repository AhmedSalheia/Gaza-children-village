<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Marks;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Modules\AcademicManagement\Actions\CreateMarkEntryWindow;
use Modules\AcademicManagement\Exceptions\MarksException;
use Modules\AcademicManagement\Models\MarkEntryWindow;
use Modules\Authorization\Data\PermissionKey;

/**
 * Admin UI for managing mark-entry windows.
 */
final class MarkEntryWindowIndex extends Component
{
    use HasAdminAuth;

    #[Url]
    public int $semesterId = 0;

    public bool   $showForm          = false;
    public string $nameAr            = '';
    public string $nameEn            = '';
    public int    $classGroupId      = 0;
    public int    $subjectOfferingId = 0;
    public string $opensAt           = '';
    public string $closesAt          = '';

    public string $flashMessage = '';
    public string $flashType    = '';

    public function mount(): void
    {
        $this->requirePermission(PermissionKey::MARK_WINDOW_MANAGE);
    }

    public function openSemesters(): Collection
    {
        return DB::table('institution_semesters as is')
            ->join('institutions as i', 'i.id', '=', 'is.institution_id')
            ->join('semesters as s', 's.id', '=', 'is.semester_id')
            ->where('is.status', 'open')
            ->orderBy('i.name_ar')
            ->get(['is.id', 'i.name_ar as institution_name', 's.name_ar as semester_name']);
    }

    public function windows(): Collection
    {
        if ($this->semesterId === 0) {
            return collect();
        }

        return DB::table('mark_entry_windows as mew')
            ->leftJoin('class_groups as cg', 'cg.id', '=', 'mew.class_group_id')
            ->leftJoin('institution_subject_offerings as iso', 'iso.id', '=', 'mew.subject_offering_id')
            ->leftJoin('subjects as s', 's.id', '=', 'iso.subject_id')
            ->where('mew.institution_semester_id', $this->semesterId)
            ->orderBy('mew.opens_at')
            ->get([
                'mew.id', 'mew.name_ar', 'mew.opens_at', 'mew.closes_at', 'mew.status',
                'cg.name_ar as class_group_name',
                's.name_ar as subject_name',
            ]);
    }

    public function classGroups(): Collection
    {
        if ($this->semesterId === 0) {
            return collect();
        }

        return DB::table('class_groups')
            ->where('institution_semester_id', $this->semesterId)
            ->where('lifecycle_status', 'active')
            ->orderBy('name_ar')
            ->get(['id', 'name_ar']);
    }

    public function subjectOfferings(): Collection
    {
        if ($this->semesterId === 0) {
            return collect();
        }

        return DB::table('institution_subject_offerings as iso')
            ->join('subjects as s', 's.id', '=', 'iso.subject_id')
            ->where('iso.institution_semester_id', $this->semesterId)
            ->orderBy('s.name_ar')
            ->get(['iso.id', 's.name_ar']);
    }

    public function save(): void
    {
        $this->requirePermission(PermissionKey::MARK_WINDOW_MANAGE);

        $this->validate([
            'semesterId' => ['required', 'integer', 'min:1'],
            'opensAt'    => ['required', 'date'],
            'closesAt'   => ['required', 'date', 'after:opensAt'],
        ]);

        try {
            app(CreateMarkEntryWindow::class)(
                institutionSemesterId: $this->semesterId,
                opensAt: new \DateTimeImmutable($this->opensAt),
                closesAt: new \DateTimeImmutable($this->closesAt),
                classGroupId: $this->classGroupId > 0 ? $this->classGroupId : null,
                subjectOfferingId: $this->subjectOfferingId > 0 ? $this->subjectOfferingId : null,
                nameAr: $this->nameAr ?: null,
                nameEn: $this->nameEn ?: null,
            );

            $this->reset(['nameAr', 'nameEn', 'opensAt', 'closesAt', 'classGroupId', 'subjectOfferingId', 'showForm']);
            $this->flash('success', 'Window created.');
        } catch (MarksException $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    public function openWindow(int $id): void
    {
        $this->requirePermission(PermissionKey::MARK_WINDOW_MANAGE);

        $window = MarkEntryWindow::findOrFail($id);

        if (! $window->status->canOpen()) {
            $this->flash('error', 'Window cannot be opened from its current status.');

            return;
        }

        $window->status = 'open';
        $window->save();
        $this->flash('success', 'Window opened.');
    }

    public function closeWindow(int $id): void
    {
        $this->requirePermission(PermissionKey::MARK_WINDOW_MANAGE);

        $window = MarkEntryWindow::findOrFail($id);

        if (! $window->status->canClose()) {
            $this->flash('error', 'Window cannot be closed from its current status.');

            return;
        }

        $window->status = 'closed';
        $window->save();
        $this->flash('success', 'Window closed.');
    }

    public function cancelWindow(int $id): void
    {
        $this->requirePermission(PermissionKey::MARK_WINDOW_MANAGE);

        MarkEntryWindow::where('id', $id)
            ->whereNotIn('status', ['closed', 'cancelled'])
            ->update(['status' => 'cancelled']);
        $this->flash('success', 'Window cancelled.');
    }

    public function cancelForm(): void
    {
        $this->reset(['nameAr', 'nameEn', 'opensAt', 'closesAt', 'classGroupId', 'subjectOfferingId', 'showForm']);
    }

    public function render(): View
    {
        return view('livewire.admin.marks.mark-entry-windows', [
            'openSemesters'    => $this->openSemesters(),
            'windows'          => $this->windows(),
            'classGroups'      => $this->classGroups(),
            'subjectOfferings' => $this->subjectOfferings(),
        ])->layout('layouts.admin');
    }

    private function flash(string $type, string $message): void
    {
        $this->flashType    = $type;
        $this->flashMessage = $message;
    }
}
