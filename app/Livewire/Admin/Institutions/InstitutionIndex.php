<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Institutions;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Organization\Models\Institution;
use Modules\Organization\Models\Scopes\ActiveInstitutionScope;

final class InstitutionIndex extends Component
{
    use HasAdminAuth;
    use WithPagination;

    /*
    |--------------------------------------------------------------------------
    | Filters
    |--------------------------------------------------------------------------
    */

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $typeFilter = '';

    #[Url]
    public string $statusFilter = 'all';

    /*
    |--------------------------------------------------------------------------
    | Create Form
    |--------------------------------------------------------------------------
    */

    public bool $showCreateForm = false;

    public string $organizationId = '';

    public string $institutionTypeId = '';

    /**
     * هذا الحقل للعرض فقط أثناء الإضافة.
     * الكود النهائي يتم إنشاؤه بعد الحصول على ID الحقيقي.
     */
    public string $code = '';

    public string $nameAr = '';

    public string $nameEn = '';

    public bool $isActive = true;

    public ?string $successMessage = null;

    /*
    |--------------------------------------------------------------------------
    | Mount
    |--------------------------------------------------------------------------
    */

    public function mount(): void
    {
        $this->requirePermission('institution.view');

        /*
         * جميع مؤسسات GCV مرتبطة بالمنظمة الرئيسية.
         *
         * نأخذ organization_id من مؤسسة موجودة مسبقًا.
         */
        $existingInstitution = Institution::withoutGlobalScope(
            ActiveInstitutionScope::class
        )->first();

        if ($existingInstitution) {
            $this->organizationId = (string) $existingInstitution->organization_id;
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
    | Create Form
    |--------------------------------------------------------------------------
    */

    public function openCreateForm(): void
    {
        $this->requirePermission('institution.create');

        $this->successMessage = null;

        $this->resetCreateForm();

        /*
         * إعادة تحميل organization_id بعد reset.
         */
        $existingInstitution = Institution::withoutGlobalScope(
            ActiveInstitutionScope::class
        )->first();

        if ($existingInstitution) {
            $this->organizationId = (string) $existingInstitution->organization_id;
        }

        $this->showCreateForm = true;
    }

    public function closeCreateForm(): void
    {
        $this->showCreateForm = false;

        $this->resetCreateForm();

        $this->resetValidation();
    }

    /*
    |--------------------------------------------------------------------------
    | Name
    |--------------------------------------------------------------------------
    */

    public function updatedNameEn(): void
    {
        /*
         * لا يتم إنشاء الكود النهائي هنا.
         *
         * السبب:
         * ID الحقيقي للمؤسسة الجديدة غير معروف
         * إلا بعد تنفيذ INSERT.
         */
    }

    /*
    |--------------------------------------------------------------------------
    | Code Preview
    |--------------------------------------------------------------------------
    */

    public function generateCodePreview(): void
    {
        /*
         * لا يتم توليد الكود النهائي قبل الحفظ.
         *
         * لأن الـ ID النهائي يتم تحديده بواسطة قاعدة البيانات
         * عند إنشاء السجل.
         *
         * لذلك يبقى الحقل:
         *
         * سيتم إنشاؤه تلقائيًا بعد الحفظ
         */
        $this->code = '';
    }

    /*
    |--------------------------------------------------------------------------
    | Save
    |--------------------------------------------------------------------------
    */

    public function save(): void
    {
        $this->requirePermission('institution.create');

        $validated = $this->validate([
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

            'nameAr' => [
                'required',
                'string',
                'max:255',
            ],

            'nameEn' => [
                'required',
                'string',
                'max:255',
            ],

            'isActive' => [
                'boolean',
            ],
        ]);

        DB::transaction(function () use ($validated): void {

            /*
             * ---------------------------------------------------------
             * 1. إنشاء المؤسسة أولًا
             * ---------------------------------------------------------
             *
             * لا نضع code هنا لأننا نحتاج إلى ID الحقيقي.
             */

            $institution = new Institution();

            $institution->organization_id = (int) $validated['organizationId'];

            $institution->institution_type_id = (int) $validated['institutionTypeId'];

            $institution->name_ar = trim($validated['nameAr']);

            $institution->name_en = trim($validated['nameEn']);

            $institution->is_active = (bool) $validated['isActive'];

            $institution->save();

            /*
             * ---------------------------------------------------------
             * 2. بعد الحفظ أصبح لدينا ID حقيقي
             * ---------------------------------------------------------
             */

            $institutionId = (int) $institution->id;

            /*
             * ---------------------------------------------------------
             * 3. إنشاء الكود باستخدام:
             *
             *    نوع المؤسسة + ID الحقيقي
             * ---------------------------------------------------------
             */

            $institution->code = $this->generateInstitutionCodeFromId(
                $institutionId
            );

            /*
             * ---------------------------------------------------------
             * 4. حفظ الكود النهائي
             * ---------------------------------------------------------
             */

            $institution->save();

            /*
             * حفظ الكود لعرضه إذا لزم الأمر.
             */
            $this->code = $institution->code;
        });

        /*
         * رسالة النجاح
         */
        $this->successMessage = 'تمت إضافة المؤسسة بنجاح.';

        /*
         * إغلاق النموذج
         */
        $this->showCreateForm = false;

        /*
         * تنظيف الحقول
         */
        $this->resetCreateForm();

        /*
         * إعادة الصفحة الأولى
         */
        $this->resetPage();
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Institution Code
    |--------------------------------------------------------------------------
    */

    private function generateInstitutionCodeFromId(
        int $institutionId
    ): string {
        /*
         * جلب نوع المؤسسة المختار.
         */
        $institutionType = DB::table('institution_types')
            ->where('id', (int) $this->institutionTypeId)
            ->first([
                'id',
                'code',
                'name_en',
            ]);

        if (!$institutionType) {
            throw new \RuntimeException(
                'نوع المؤسسة غير موجود.'
            );
        }

        /*
         * استخدام code الخاص بنوع المؤسسة.
         *
         * مثال:
         * academy
         * school
         * clinic
         * warehouse
         */
        $typeCode = trim(
            (string) $institutionType->code
        );

        /*
         * في حال كان code فارغًا،
         * نستخدم name_en كبديل.
         */
        if ($typeCode === '') {
            $typeCode = Str::slug(
                (string) $institutionType->name_en
            );
        }

        $typeCode = Str::lower($typeCode);

        /*
         * fallback نهائي.
         */
        if ($typeCode === '') {
            $typeCode = 'institution';
        }

        /*
         * النتيجة:
         *
         * GCV-academy-026
         * GCV-school-027
         * GCV-clinic-028
         */
        return sprintf(
            'GCV-%s-%03d',
            $typeCode,
            $institutionId
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Reset Create Form
    |--------------------------------------------------------------------------
    */

    private function resetCreateForm(): void
    {
        $this->institutionTypeId = '';

        $this->code = '';

        $this->nameAr = '';

        $this->nameEn = '';

        $this->isActive = true;

        $this->resetValidation();
    }

    /*
    |--------------------------------------------------------------------------
    | Institutions
    |--------------------------------------------------------------------------
    */

    public function institutions(): LengthAwarePaginator
    {
        return Institution::withoutGlobalScope(
            ActiveInstitutionScope::class
        )
            ->with('institutionType')

            ->when(
                $this->search !== '',
                function ($q): void {
                    $s = "%{$this->search}%";

                    $q->where(function ($inner) use ($s): void {
                        $inner
                            ->where('name_ar', 'like', $s)
                            ->orWhere('name_en', 'like', $s)
                            ->orWhere('code', 'like', $s);
                    });
                }
            )

            ->when(
                $this->typeFilter !== '',
                fn ($q) => $q->where(
                    'institution_type_id',
                    $this->typeFilter
                )
            )

            ->when(
                $this->statusFilter !== 'all',
                function ($q): void {
                    $q->where(
                        'is_active',
                        $this->statusFilter === 'active'
                    );
                }
            )

            /*
             * عرض المؤسسات حسب ID من الأحدث إلى الأقدم.
             *
             * وبذلك تكون آخر مؤسسة منشأة في الأعلى.
             */
            ->orderByDesc('id')

            ->paginate(20);
    }

    /*
    |--------------------------------------------------------------------------
    | Institution Types
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
    | Render
    |--------------------------------------------------------------------------
    */

    public function render(): View
    {
        return view(
            'livewire.admin.institutions.index',
            [
                'institutions' => $this->institutions(),

                'institutionTypes' => $this->institutionTypes(),
            ]
        )->layout('layouts.admin');
    }
}

