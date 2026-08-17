@title(__('documents.version_detail_title', [], null, 'Template Versions') . ' — ' . $template->document_type_code)

<div class="page-container">
    <div class="page-header">
        <a href="{{ route('admin.documents.templates') }}" class="btn btn--ghost btn--sm">
            ← {{ __('documents.back_to_list', [], null, 'Back to templates') }}
        </a>
        <h1 class="page-title">
            {{ __('documents.version_detail_title', [], null, 'Template Versions') }}
            — <code>{{ $template->document_type_code }}</code>
            @if($template->institution_id)
                <span class="text-muted text-sm">({{ __('documents.institution_id', [], null, 'Institution #') }}{{ $template->institution_id }})</span>
            @else
                <span class="badge badge--secondary">{{ __('documents.org_wide', [], null, 'Org-wide') }}</span>
            @endif
        </h1>
    </div>

    @if($flashMessage)
        <div class="alert alert--success" role="status">{{ $flashMessage }}</div>
    @endif
    @if(!empty($errors))
        <div class="alert alert--danger">
            @foreach($errors as $err)<p>{{ $err }}</p>@endforeach
        </div>
    @endif

    {{-- Preview panel --}}
    @if($previewHtml !== null)
    <div class="preview-panel" role="region" aria-label="{{ __('documents.preview_label', [], null, 'Template Preview') }}">
        <div class="preview-panel__header">
            <h2 class="preview-panel__title">{{ __('documents.preview_label', [], null, 'Template Preview (Synthetic Data)') }}</h2>
            <button type="button" wire:click="closePreview" class="btn btn--ghost btn--sm">
                {{ __('ui.close') }}
            </button>
        </div>
        <div class="preview-panel__body" dir="auto">
            {!! $previewHtml !!}
        </div>
    </div>
    @endif

    {{-- Version list --}}
    @if($versions->isEmpty())
        <div class="empty-state">
            <p>{{ __('documents.no_versions', [], null, 'No versions yet. Create a draft to get started.') }}</p>
        </div>
    @else
    <table class="data-table" role="grid">
        <thead>
            <tr>
                <th scope="col">{{ __('documents.col_version', [], null, 'Version') }}</th>
                <th scope="col">{{ __('documents.col_locale', [], null, 'Locale') }}</th>
                <th scope="col">{{ __('documents.col_status', [], null, 'Status') }}</th>
                <th scope="col">{{ __('documents.col_placeholders', [], null, 'Placeholders') }}</th>
                <th scope="col">{{ __('documents.col_created', [], null, 'Created') }}</th>
                <th scope="col"><span class="sr-only">{{ __('ui.actions') }}</span></th>
            </tr>
        </thead>
        <tbody>
            @foreach($versions as $version)
            <tr>
                <td>v{{ $version->version_number }}</td>
                <td><span class="badge badge--info">{{ strtoupper($version->locale) }}</span></td>
                <td>
                    <span class="status-badge status-badge--{{ $version->status }}">
                        {{ __('documents.status_' . $version->status, [], null, $version->status) }}
                    </span>
                </td>
                <td>
                    <span class="text-sm text-muted">
                        {{ count($version->placeholder_catalogue ?? []) }} {{ __('documents.placeholders_count', [], null, 'placeholders') }}
                    </span>
                </td>
                <td>{{ \Carbon\Carbon::parse($version->created_at)->format('Y-m-d') }}</td>
                <td class="action-cell">
                    <button type="button" wire:click="previewVersion({{ $version->id }})" class="btn btn--sm btn--ghost">
                        {{ __('documents.preview_btn', [], null, 'Preview') }}
                    </button>

                    @if($version->isDraft() && $canActivate)
                        <button
                            type="button"
                            wire:click="activate({{ $version->id }})"
                            wire:confirm="{{ __('documents.activate_confirm', [], null, 'Activate this template version? The current active version will be archived.') }}"
                            class="btn btn--sm btn--success"
                        >
                            {{ __('documents.activate_btn', [], null, 'Activate') }}
                        </button>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- Create draft form --}}
    @if($canManage)
    <div class="form-section mt-4">
        @if(!$showDraftForm)
            <button type="button" wire:click="$set('showDraftForm', true)" class="btn btn--primary">
                {{ __('documents.create_draft_btn', [], null, '+ New Draft Version') }}
            </button>
        @else
        <h2 class="form-section__title">{{ __('documents.create_draft_title', [], null, 'Create Draft Version') }}</h2>

        <div class="form-group">
            <label for="draftLocale" class="form-label">{{ __('documents.locale_label', [], null, 'Locale') }}</label>
            <select id="draftLocale" wire:model="draftLocale" class="form-control form-control--sm">
                <option value="ar">{{ __('documents.locale_option_ar') }}</option>
                <option value="en">{{ __('documents.locale_option_en') }}</option>
            </select>
        </div>

        <div class="form-group">
            <label for="draftBody" class="form-label">
                {{ __('documents.body_label', [], null, 'Template Body (HTML with placeholders)') }}
            </label>
            <textarea
                id="draftBody"
                wire:model="draftBody"
                class="form-control form-control--code"
                rows="15"
                dir="auto"
                placeholder="{{ __('documents.body_placeholder', [], null, '<p dir=&quot;rtl&quot;>الطالب: [[student.full_name_ar]]</p>') }}"
            ></textarea>
        </div>

        <div class="form-group">
            <label for="draftHeaderHtml" class="form-label">
                {{ __('documents.header_html_label', [], null, 'Page Header HTML (optional)') }}
            </label>
            <textarea
                id="draftHeaderHtml"
                wire:model="draftHeaderHtml"
                class="form-control form-control--code"
                rows="3"
            ></textarea>
        </div>

        <div class="form-group">
            <label for="draftFooterHtml" class="form-label">
                {{ __('documents.footer_html_label', [], null, 'Page Footer HTML (optional)') }}
            </label>
            <textarea
                id="draftFooterHtml"
                wire:model="draftFooterHtml"
                class="form-control form-control--code"
                rows="3"
            ></textarea>
        </div>

        <div class="form-actions">
            <button type="button" wire:click="$set('showDraftForm', false)" class="btn btn--ghost">
                {{ __('ui.cancel') }}
            </button>
            <button type="button" wire:click="createDraft" class="btn btn--primary" wire:loading.attr="disabled">
                <span wire:loading.remove>{{ __('documents.save_draft_btn', [], null, 'Save Draft') }}</span>
                <span wire:loading>{{ __('ui.saving') }}</span>
            </button>
        </div>
        @endif
    </div>
    @endif
</div>
