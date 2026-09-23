<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Medical Staff
        |--------------------------------------------------------------------------
        */
        Schema::create('medical_staff', function (Blueprint $table) {
            $table->id();

            $table->foreignId('staff_profile_id')
                ->constrained('staff_profiles');

            $table->foreignId('institution_id')
                ->constrained('institutions');

            $table->string('profession', 32);
            $table->string('license_number', 120)->nullable();
            $table->string('specialization', 255)->nullable();

            $table->string('status', 24)
                ->default('active');

            $table->date('started_on')->nullable();
            $table->date('ended_on')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(
                'staff_profile_id',
                'med_staff_profile_unique'
            );

            $table->index(
                ['institution_id', 'profession', 'status'],
                'med_staff_inst_prof_status_idx'
            );
        });


        /*
        |--------------------------------------------------------------------------
        | Medical Patients
        |--------------------------------------------------------------------------
        */
        Schema::create('medical_patients', function (Blueprint $table) {
            $table->id();

            $table->string('patient_code', 80)
                ->unique('med_patient_code_unique');

            $table->string('patient_type', 32)
                ->default('student');

            $table->foreignId('student_profile_id')
                ->nullable()
                ->constrained('student_profiles');

            $table->foreignId('person_id')
                ->nullable()
                ->constrained('people');

            $table->foreignId('institution_id')
                ->nullable()
                ->constrained('institutions');

            $table->string('status', 24)
                ->default('active');

            $table->text('allergies')->nullable();
            $table->text('chronic_conditions')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(
                'student_profile_id',
                'med_patient_student_unique'
            );

            $table->index(
                ['institution_id', 'status'],
                'med_patient_inst_status_idx'
            );
        });


        /*
        |--------------------------------------------------------------------------
        | Medical Patient Conditions
        |--------------------------------------------------------------------------
        */
        Schema::create('medical_patient_conditions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_id')
                ->constrained('medical_patients')
                ->cascadeOnDelete();

            $table->string('condition_name', 255);
            $table->date('diagnosed_on')->nullable();
            $table->text('notes')->nullable();

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();

            $table->index(
                ['patient_id', 'is_active'],
                'med_condition_patient_active_idx'
            );
        });


        /*
        |--------------------------------------------------------------------------
        | Medical Patient Allergies
        |--------------------------------------------------------------------------
        */
        Schema::create('medical_patient_allergies', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_id')
                ->constrained('medical_patients')
                ->cascadeOnDelete();

            $table->string('allergen', 255);
            $table->string('reaction', 255)->nullable();
            $table->string('severity', 32)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(
                ['patient_id', 'severity'],
                'med_allergy_patient_severity_idx'
            );
        });


        /*
        |--------------------------------------------------------------------------
        | Medical Visits
        |--------------------------------------------------------------------------
        */
        Schema::create('medical_visits', function (Blueprint $table) {
            $table->id();

            $table->string('visit_number', 80)
                ->unique('med_visit_number_unique');

            $table->foreignId('patient_id')
                ->constrained('medical_patients');

            $table->foreignId('institution_id')
                ->constrained('institutions');

            $table->foreignId('doctor_id')
                ->nullable()
                ->constrained('medical_staff');

            $table->foreignId('nurse_id')
                ->nullable()
                ->constrained('medical_staff');

            $table->dateTime('visited_at');

            $table->string('status', 24)
                ->default('open');

            $table->text('chief_complaint')->nullable();

            $table->string('temperature', 32)->nullable();
            $table->string('blood_pressure', 32)->nullable();
            $table->string('pulse', 32)->nullable();
            $table->string('weight', 32)->nullable();

            $table->text('diagnosis')->nullable();
            $table->text('treatment_plan')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(
                ['patient_id', 'visited_at'],
                'med_visit_patient_date_idx'
            );

            $table->index(
                ['institution_id', 'visited_at'],
                'med_visit_inst_date_idx'
            );

            $table->index(
                ['doctor_id', 'visited_at'],
                'med_visit_doctor_date_idx'
            );

            $table->index(
                ['nurse_id', 'visited_at'],
                'med_visit_nurse_date_idx'
            );

            $table->index(
                ['status', 'visited_at'],
                'med_visit_status_date_idx'
            );
        });


        /*
        |--------------------------------------------------------------------------
        | Medical Visit Diagnoses
        |--------------------------------------------------------------------------
        */
        Schema::create('medical_visit_diagnoses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('visit_id')
                ->constrained('medical_visits')
                ->cascadeOnDelete();

            $table->string('diagnosis_code', 80)->nullable();
            $table->string('diagnosis_name', 255);

            $table->boolean('is_primary')
                ->default(false);

            $table->timestamps();

            $table->index(
                ['visit_id', 'is_primary'],
                'med_diag_visit_primary_idx'
            );

            $table->index(
                'diagnosis_code',
                'med_diag_code_idx'
            );
        });


        /*
        |--------------------------------------------------------------------------
        | Medical Medicines
        |--------------------------------------------------------------------------
        */
        Schema::create('medical_medicines', function (Blueprint $table) {
            $table->id();

            $table->string('code', 80)
                ->unique('med_medicine_code_unique');

            $table->string('name_ar', 255);
            $table->string('name_en', 255);

            $table->string('generic_name', 255)->nullable();
            $table->string('form', 120)->nullable();
            $table->string('strength', 120)->nullable();

            $table->string('unit', 80)
                ->default('unit');

            $table->string('manufacturer', 255)->nullable();

            $table->decimal('minimum_stock', 14, 3)
                ->default(0);

            $table->decimal('maximum_stock', 14, 3)
                ->nullable();

            $table->decimal('reorder_level', 14, 3)
                ->default(0);

            $table->boolean('is_active')
                ->default(true);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(
                ['name_ar', 'is_active'],
                'med_medicine_name_active_idx'
            );

            $table->index(
                ['name_en', 'is_active'],
                'med_medicine_en_active_idx'
            );

            $table->index(
                ['generic_name', 'is_active'],
                'med_medicine_generic_active_idx'
            );
        });


        /*
        |--------------------------------------------------------------------------
        | Medical Medicine Batches
        |--------------------------------------------------------------------------
        */
        Schema::create('medical_medicine_batches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('medicine_id')
                ->constrained('medical_medicines');

            $table->foreignId('institution_id')
                ->constrained('institutions');

            $table->string('batch_number', 120);
            $table->date('expiry_date');

            $table->decimal('received_quantity', 14, 3)
                ->default(0);

            $table->decimal('current_quantity', 14, 3)
                ->default(0);

            $table->decimal('unit_cost', 14, 4)
                ->default(0);

            $table->string('supplier', 255)->nullable();
            $table->dateTime('received_at')->nullable();

            $table->string('status', 24)
                ->default('active');

            $table->text('notes')->nullable();

            $table->timestamps();

            /*
             * FEFO / stock lookup.
             *
             * Explicit short name is important for MySQL.
             */
            $table->index(
                ['medicine_id', 'expiry_date', 'current_quantity'],
                'med_batch_stock_idx'
            );

            $table->index(
                ['institution_id', 'medicine_id'],
                'med_batch_inst_med_idx'
            );

            $table->index(
                ['institution_id', 'expiry_date'],
                'med_batch_inst_expiry_idx'
            );

            $table->index(
                ['status', 'expiry_date'],
                'med_batch_status_expiry_idx'
            );

            $table->unique(
                ['medicine_id', 'institution_id', 'batch_number'],
                'med_batch_unique'
            );
        });


        /*
        |--------------------------------------------------------------------------
        | Medical Medicine Transactions
        |--------------------------------------------------------------------------
        */
        Schema::create('medical_medicine_transactions', function (Blueprint $table) {
            $table->id();

            $table->string('transaction_number', 100)
                ->unique('med_tx_number_unique');

            $table->foreignId('medicine_id')
                ->constrained('medical_medicines');

            $table->foreignId('batch_id')
                ->nullable()
                ->constrained('medical_medicine_batches');

            $table->foreignId('institution_id')
                ->constrained('institutions');

            $table->foreignId('patient_id')
                ->nullable()
                ->constrained('medical_patients');

            $table->foreignId('visit_id')
                ->nullable()
                ->constrained('medical_visits');

            $table->string('type', 32);

            $table->decimal('quantity', 14, 3);

            $table->decimal('unit_cost', 14, 4)
                ->default(0);

            $table->string('reference_number', 100)
                ->nullable();

            $table->text('reason')->nullable();

            $table->unsignedBigInteger('actor_account_id')
                ->nullable();

            $table->dateTime('occurred_at');

            $table->timestamps();

            $table->index(
                ['medicine_id', 'type', 'occurred_at'],
                'med_tx_med_type_date_idx'
            );

            $table->index(
                ['institution_id', 'occurred_at'],
                'med_tx_inst_date_idx'
            );

            $table->index(
                ['batch_id', 'occurred_at'],
                'med_tx_batch_date_idx'
            );

            $table->index(
                ['patient_id', 'occurred_at'],
                'med_tx_patient_date_idx'
            );

            $table->index(
                ['visit_id', 'occurred_at'],
                'med_tx_visit_date_idx'
            );

            $table->index(
                ['type', 'occurred_at'],
                'med_tx_type_date_idx'
            );
        });


        /*
        |--------------------------------------------------------------------------
        | Medical Prescriptions
        |--------------------------------------------------------------------------
        */
        Schema::create('medical_prescriptions', function (Blueprint $table) {
            $table->id();

            $table->string('prescription_number', 100)
                ->unique('med_prescription_number_unique');

            $table->foreignId('patient_id')
                ->constrained('medical_patients');

            $table->foreignId('visit_id')
                ->nullable()
                ->constrained('medical_visits');

            $table->foreignId('doctor_id')
                ->nullable()
                ->constrained('medical_staff');

            $table->dateTime('prescribed_at');

            $table->string('status', 32)
                ->default('draft');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(
                ['patient_id', 'prescribed_at'],
                'med_rx_patient_date_idx'
            );

            $table->index(
                ['doctor_id', 'prescribed_at'],
                'med_rx_doctor_date_idx'
            );

            $table->index(
                ['status', 'prescribed_at'],
                'med_rx_status_date_idx'
            );
        });


        /*
        |--------------------------------------------------------------------------
        | Medical Prescription Lines
        |--------------------------------------------------------------------------
        */
        Schema::create('medical_prescription_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('prescription_id')
                ->constrained('medical_prescriptions')
                ->cascadeOnDelete();

            $table->foreignId('medicine_id')
                ->constrained('medical_medicines');

            $table->string('dose', 120)->nullable();
            $table->string('frequency', 120)->nullable();
            $table->string('duration', 120)->nullable();

            $table->decimal('quantity', 14, 3)
                ->default(0);

            $table->text('instructions')->nullable();

            $table->timestamps();

            $table->index(
                ['prescription_id', 'medicine_id'],
                'med_rx_line_pres_med_idx'
            );

            $table->index(
                'medicine_id',
                'med_rx_line_medicine_idx'
            );
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('medical_prescription_lines');
        Schema::dropIfExists('medical_prescriptions');
        Schema::dropIfExists('medical_medicine_transactions');
        Schema::dropIfExists('medical_medicine_batches');
        Schema::dropIfExists('medical_medicines');
        Schema::dropIfExists('medical_visit_diagnoses');
        Schema::dropIfExists('medical_visits');
        Schema::dropIfExists('medical_patient_allergies');
        Schema::dropIfExists('medical_patient_conditions');
        Schema::dropIfExists('medical_patients');
        Schema::dropIfExists('medical_staff');
    }
};
