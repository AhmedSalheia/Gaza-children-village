@title(__('requests.new_request', [], null, 'New Correction Request'))

<div class="page-container">
    <div class="page-header">
        <h1 class="page-title">{{ __('requests.new_request', [], null, 'New Correction Request') }}</h1>
    </div>

    {{-- Validation errors --}}
    @if(!empty($validationErrors))
        <div class="alert alert--danger" role="alert">
            <ul class="mb-0">
                @foreach($validationErrors as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Success confirmation --}}
    @if($submitted)
        <div class="alert alert--success" role="alert">
            {{ __('requests.submitted_success', [], null, 'Your correction request has been submitted successfully.') }}
            <a href="{{ route('guardian.corrections.detail', $createdRequestId) }}" class="alert__link">
                {{ __('requests.view_request', [], null, 'View request') }}
            </a>
        </div>
    @else

    {{-- Step indicator --}}
    <div class="step-indicator" aria-label="{{ __('requests.steps', [], null, 'Progress') }}">
        <div class="step-indicator__step {{ $step >= 1 ? 'step-indicator__step--active' : '' }}">
            <span class="step-indicator__number">1</span>
            <span class="step-indicator__label">{{ __('requests.step_student', [], null, 'Select Student') }}</span>
        </div>
        <div class="step-indicator__step {{ $step >= 2 ? 'step-indicator__step--active' : '' }}">
            <span class="step-indicator__number">2</span>
            <span class="step-indicator__label">{{ __('requests.step_field', [], null, 'Enter Correction') }}</span>
        </div>
        <div class="step-indicator__step {{ $step >= 3 ? 'step-indicator__step--active' : '' }}">
            <span class="step-indicator__number">3</span>
            <span class="step-indicator__label">{{ __('requests.step_review', [], null, 'Review & Submit') }}</span>
        </div>
    </div>

    {{-- Step 1: Student selection --}}
    @if($step === 1)
    <div class="form-section">
        <h2 class="form-section__title">{{ __('requests.select_student', [], null, 'Select the student to correct') }}</h2>

        @if($eligibleStudents->isEmpty())
            <p class="text-muted">{{ __('requests.no_eligible_students', [], null, 'No portal-eligible students found.') }}</p>
        @else
            <ul class="student-picker">
                @foreach($eligibleStudents as $student)
                <li class="student-picker__item">
                    <button
                        type="button"
                        class="student-picker__btn btn btn--outline"
                        wire:click="selectStudent({{ $student->id }})"
                    >
                        <span class="student-picker__name">{{ $student->name }}</span>
                        @if($student->name_en)
                            <span class="student-picker__name-en text-muted">{{ $student->name_en }}</span>
                        @endif
                    </button>
                </li>
                @endforeach
            </ul>
        @endif
    </div>
    @endif

    {{-- Step 2: Field and value entry --}}
    @if($step === 2)
    <div class="form-section">
        <h2 class="form-section__title">{{ __('requests.enter_correction', [], null, 'Enter correction details') }}</h2>

        @if($selectedStudent)
            <p class="form-context">
                {{ __('requests.correcting_for', [], null, 'Correcting for') }}: <strong>{{ $selectedStudent->name }}</strong>
            </p>
        @endif

        <div class="form-group">
            <label for="fieldCode" class="form-label">{{ __('requests.field_label', [], null, 'Field to correct') }}</label>
            <select
                id="fieldCode"
                wire:model.live="fieldCode"
                class="form-control"
                required
            >
                <option value="">{{ __('requests.select_field_placeholder', [], null, '— Select a field —') }}</option>
                @foreach($catalogueFields as $field)
                    <option value="{{ $field->value }}">
                        {{ $field->labelAr() }}
                        @if($field->classification() === \Modules\Requests\Enums\CorrectionClassification::Sensitive)
                            ({{ __('requests.requires_principal', [], null, 'requires principal approval') }})
                        @endif
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Relationship selector — only shown for relationship-type corrections --}}
        @php
            $selectedField = \Modules\Requests\Enums\CorrectionFieldCatalogue::tryFrom($fieldCode);
            $isRelationshipField = $selectedField !== null && in_array($selectedField, [
                \Modules\Requests\Enums\CorrectionFieldCatalogue::GuardianRelationshipType,
                \Modules\Requests\Enums\CorrectionFieldCatalogue::GuardianLegalAuthority,
            ], true);
        @endphp
        @if($isRelationshipField && !empty($guardianRelationships))
        <div class="form-group">
            <label for="relationshipRefId" class="form-label">
                {{ __('requests.relationship_label', [], null, 'Which relationship to correct') }}
                <span class="text-muted text-sm">({{ __('requests.relationship_hint', [], null, 'Select the specific guardian–student link') }})</span>
            </label>
            <select
                id="relationshipRefId"
                wire:model="relationshipRefId"
                class="form-control"
                required
            >
                <option value="">{{ __('requests.select_relationship_placeholder', [], null, '— Select a relationship —') }}</option>
                @foreach($guardianRelationships as $rel)
                    <option value="{{ $rel->id }}">
                        {{ __('requests.relationship_option', [], null, ':type (verified)', ['type' => $rel->relationship_type]) }}
                    </option>
                @endforeach
            </select>
        </div>
        @elseif($isRelationshipField && empty($guardianRelationships))
        <div class="alert alert--warning">
            {{ __('requests.no_relationships', [], null, 'No verified relationships found for this student. Relationship corrections are unavailable.') }}
        </div>
        @endif

        <div class="form-group">
            <label for="proposedValue" class="form-label">{{ __('requests.proposed_value_label', [], null, 'Proposed new value') }}</label>
            <input
                type="text"
                id="proposedValue"
                wire:model="proposedValue"
                class="form-control"
                required
                autocomplete="off"
            >
        </div>

        <div class="form-group">
            <label for="explanation" class="form-label">{{ __('requests.explanation_label', [], null, 'Explanation (optional)') }}</label>
            <textarea
                id="explanation"
                wire:model="explanation"
                class="form-control"
                rows="3"
                maxlength="1000"
            ></textarea>
        </div>

        <div class="form-actions">
            <button type="button" wire:click="backToStep(1)" class="btn btn--ghost">
                {{ __('ui.back') }}
            </button>
            <button type="button" wire:click="proceedToReview" class="btn btn--primary">
                {{ __('ui.next') }}
            </button>
        </div>
    </div>
    @endif

    {{-- Step 3: Review and submit --}}
    @if($step === 3)
    <div class="form-section">
        <h2 class="form-section__title">{{ __('requests.review_and_submit', [], null, 'Review your correction request') }}</h2>

        @php $field = \Modules\Requests\Enums\CorrectionFieldCatalogue::tryFrom($fieldCode); @endphp

        <dl class="review-list">
            <dt>{{ __('requests.col_student', [], null, 'Student') }}</dt>
            <dd>{{ $selectedStudent?->name }}</dd>

            <dt>{{ __('requests.col_field', [], null, 'Field') }}</dt>
            <dd>
                {{ $field?->labelAr() }}
                @if($field?->classification() === \Modules\Requests\Enums\CorrectionClassification::Sensitive)
                    <span class="badge badge--warning">{{ __('requests.sensitive', [], null, 'Sensitive') }}</span>
                @endif
            </dd>

            <dt>{{ __('requests.proposed_value_label', [], null, 'Proposed value') }}</dt>
            <dd>{{ $proposedValue }}</dd>

            @if($explanation)
            <dt>{{ __('requests.explanation_label', [], null, 'Explanation') }}</dt>
            <dd>{{ $explanation }}</dd>
            @endif
        </dl>

        @if($field?->classification() === \Modules\Requests\Enums\CorrectionClassification::Sensitive)
            <div class="alert alert--info">
                {{ __('requests.sensitive_notice', [], null, 'This field requires principal approval. Your request will be reviewed by the school principal before any changes are applied.') }}
            </div>
        @endif

        <div class="form-actions">
            <button type="button" wire:click="backToStep(2)" class="btn btn--ghost">
                {{ __('ui.back') }}
            </button>
            <button type="button" wire:click="submit" class="btn btn--primary" wire:loading.attr="disabled">
                <span wire:loading.remove>{{ __('requests.submit_btn', [], null, 'Submit Correction Request') }}</span>
                <span wire:loading>{{ __('ui.saving') }}</span>
            </button>
        </div>
    </div>
    @endif

    @endif {{-- end not submitted --}}
</div>
