<?php
use Illuminate\Support\Facades\Route;
use App\Livewire\Admin\Medical\MedicalDashboard;
use App\Livewire\Admin\Medical\Clinics\ClinicIndex;
use App\Livewire\Admin\Medical\Staff\MedicalStaffIndex;
use App\Livewire\Admin\Medical\Patients\PatientIndex;
use App\Livewire\Admin\Medical\Patients\PatientProfile;
use App\Livewire\Admin\Medical\Visits\VisitIndex;
use App\Livewire\Admin\Medical\Visits\VisitForm;
use App\Livewire\Admin\Medical\Medicines\MedicineIndex;
use App\Livewire\Admin\Medical\Batches\MedicineBatchIndex;
use App\Livewire\Admin\Medical\Receipts\MedicineReceiptForm;
use App\Livewire\Admin\Medical\Issues\MedicineIssueForm;
use App\Livewire\Admin\Medical\Movements\MedicineMovementIndex;
use App\Livewire\Admin\Medical\Prescriptions\PrescriptionIndex;
use App\Livewire\Admin\Medical\Reports\MedicalReports;

Route::middleware(['web'])->prefix('admin/medical')->name('admin.medical.')->group(function () {
    Route::get('/', MedicalDashboard::class)->name('index');
    Route::get('/clinics', ClinicIndex::class)->name('clinics.index');
    Route::get('/staff', MedicalStaffIndex::class)->name('staff.index');
    Route::get('/patients', PatientIndex::class)->name('patients.index');
    Route::get('/patients/{patient}', PatientProfile::class)->name('patients.show');
    Route::get('/visits', VisitIndex::class)->name('visits.index');
    Route::get('/visits/create', VisitForm::class)->name('visits.create');
    Route::get('/medicines', MedicineIndex::class)->name('medicines.index');
    Route::get('/batches', MedicineBatchIndex::class)->name('batches.index');
    Route::get('/receipts/create', MedicineReceiptForm::class)->name('receipts.create');
    Route::get('/issues/create', MedicineIssueForm::class)->name('issues.create');
    Route::get('/movements', MedicineMovementIndex::class)->name('movements.index');
    Route::get('/prescriptions', PrescriptionIndex::class)->name('prescriptions.index');
    Route::get('/reports', MedicalReports::class)->name('reports.index');
});
