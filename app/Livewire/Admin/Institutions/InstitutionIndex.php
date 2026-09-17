<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Institutions;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Organization\Models\Institution;
use Modules\Organization\Models\Scopes\ActiveInstitutionScope;
use Modules\Organization\Models\InstitutionType;

/**
 * Searchable, filterable list of all GCV institutions.
 */
final class InstitutionIndex extends Component
{
    use HasAdminAuth;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $typeFilter = '';

    #[Url]
    public string $statusFilter = 'all';

    /*
    |--------------------------------------------------------------------------
    | Create institution form
    |--------------------------------------------------------------------------
    */

    public bool $showCreateForm = false;

    public string $organizationId = '';

    public string $institutionTypeId = '';

    /*
     * Institution code is entered manually by the user.
     */
    public string $code = '';

    public string $nameAr = '';

    public string $nameEn = '';

    public bool $isActive = true;

    public ?string $successMessage = null;

    public function mount(): void
    {
        $this->requirePermission('institution.view');

        /*
         * All GCV institutions belong to the same organization.
         * Get the organization automatically from an existing institution.
         */
        $organizationId = Institution::withoutGlobalScope(ActiveInstitutionScope::class)
            ->orderBy('id')
            ->value('organization_id');

        if ($organizationId !== null) {
            $this->organizationId = (string) $organizationId;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Filters
    |--------------------------------------------------------------------------
    */

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    /*
    |--------------------------------------------------------------------------
    | Institution list
    |--------------------------------------------------------------------------
    */

    public function institutions(): LengthAwarePaginator
    {
        return Institution::withoutGlobalScope(ActiveInstitutionScope::class)
            ->with('institutionType')
            ->when($this->search !== '', function ($q): void {
                $s = "%{$this->search}%";

                $q->where(function ($inner) use ($s): void {
                    $inner->where('name_ar', 'like', $s)
                        ->orWhere('name_en', 'like', $s)
                        ->orWhere('code', 'like', $s);
                });
            })
            ->when(
                $this->typeFilter !== '',
                fn ($q) => $q->where(
                    'institution_type_id',
                    $this->typeFilter
                )
            )
            ->when($this->statusFilter !== 'all', function ($q): void {
                $q->where(
                    'is_active',
                    $this->statusFilter === 'active'
                );
            })
            ->orderBy('name_ar')
            ->paginate(20);
    }

    /*
    |--------------------------------------------------------------------------
    | Institution types
    |--------------------------------------------------------------------------
    */

    public function institutionTypes(): Collection
    {
        return DB::table('institution_types')
            ->orderBy('name_ar')
            ->get([
                'id',
                'name_ar',
                'name_en',
                'code',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Create form
    |--------------------------------------------------------------------------
    */

    public function openCreateForm(): void
    {
        $this->requirePermission('institution.create');

        $this->resetValidation();
        $this->successMessage = null;

        /*
         * Refresh organization ID in case the component was opened
         * after data changed.
         */
        $organizationId = Institution::withoutGlobalScope(ActiveInstitutionScope::class)
            ->orderBy('id')
            ->value('organization_id');

        if ($organizationId !== null) {
            $this->organizationId = (string) $organizationId;
        }

        $this->showCreateForm = true;
    }

    public function closeCreateForm(): void
    {
        $this->showCreateForm = false;

        $this->resetCreateForm();
        $this->resetValidation();
    }

    protected function rules(): array
    {
        return [
            'organizationId' => [
                'required',
                'integer',
                'exists:organizations,id',
            ],

            'institutionTypeId' => [
                'required',
                'integer',
                'exists:institution_types,id',
            ],

            /*
             * Code must now be entered manually.
             */
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('institutions', 'code'),
            ],

            'nameAr' => [
                'required',
                'string',
                'max:255',
            ],

            'nameEn' => [
                'nullable',
                'string',
                'max:255',
            ],

            'isActive' => [
                'boolean',
            ],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'organizationId' => 'المنظمة',
            'institutionTypeId' => 'نوع المؤسسة',
            'code' => 'كود المؤسسة',
            'nameAr' => 'اسم المؤسسة بالعربي',
            'nameEn' => 'اسم المؤسسة بالإنجليزي',
            'isActive' => 'حالة المؤسسة',
        ];
    }

    public function save(): void
    {
        $this->requirePermission('institution.create');

        $this->successMessage = null;

        $this->validate();

        /*
         * Do not use Institution::create() here because the model
         * intentionally excludes "code" from $fillable.
         */
        $institution = new Institution();

        $institution->organization_id = (int) $this->organizationId;

        $institution->institution_type_id = (int) $this->institutionTypeId;

        $institution->name_ar = trim($this->nameAr);

        $institution->name_en = trim($this->nameEn) !== ''
            ? trim($this->nameEn)
            : null;

        $institution->is_active = $this->isActive;

        /*
         * Code is entered manually by the user.
         */
        $institution->code = trim($this->code);

        $institution->save();

        $this->successMessage = 'تمت إضافة المؤسسة بنجاح.';

        $this->showCreateForm = false;

        $this->resetCreateForm();

        $this->resetPage();
    }

    private function resetCreateForm(): void
    {
        /*
         * Keep organization ID because all GCV institutions belong
         * to the same organization.
         */
        $organizationId = Institution::withoutGlobalScope(ActiveInstitutionScope::class)
            ->orderBy('id')
            ->value('organization_id');

        $this->institutionTypeId = '';

        /*
         * Clear manually entered code after saving/closing.
         */
        $this->code = '';

        $this->nameAr = '';

        $this->nameEn = '';

        $this->isActive = true;

        if ($organizationId !== null) {
            $this->organizationId = (string) $organizationId;
        }
    }

    public function render(): View
    {
        return view('livewire.admin.institutions.index', [
            'institutions' => $this->institutions(),
            'institutionTypes' => $this->institutionTypes(),
        ])->layout('layouts.admin');
    }
}
