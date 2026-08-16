@title(__('documents.template_list_title', [], null, 'Document Templates'))

<div class="page-container">
    <div class="page-header">
        <h1 class="page-title">{{ __('documents.template_list_title', [], null, 'Document Templates') }}</h1>
    </div>

    @if($flashMessage)
        <div class="alert alert--success" role="status">{{ $flashMessage }}</div>
    @endif
    @if(!empty($errors))
        <div class="alert alert--danger">
            @foreach($errors as $err)<p>{{ $err }}</p>@endforeach
        </div>
    @endif

    {{-- Filter --}}
    <div class="filter-bar">
        <div class="filter-bar__item">
            <label class="form-label">{{ __('documents.type_filter_label', [], null, 'Document Type') }}</label>
            <select wire:model.live="typeFilter" class="form-control form-control--sm">
                <option value="">{{ __('ui.all') }}</option>
                @foreach($typeOptions as $type)
                    <option value="{{ $type->code }}">{{ $type->label_ar }} ({{ $type->label_en }})</option>
                @endforeach
            </select>
        </div>
    </div>

    @if($templates->isEmpty())
        <div class="empty-state">
            <p>{{ __('documents.no_templates', [], null, 'No document templates configured yet.') }}</p>
        </div>
    @else
    <table class="data-table" role="grid">
        <thead>
            <tr>
                <th scope="col">{{ __('documents.col_type', [], null, 'Document Type') }}</th>
                <th scope="col">{{ __('documents.col_institution', [], null, 'Institution') }}</th>
                <th scope="col">{{ __('documents.col_locales', [], null, 'Locales') }}</th>
                <th scope="col">{{ __('documents.col_active_version', [], null, 'Active Version') }}</th>
                <th scope="col">{{ __('documents.col_approval', [], null, 'Approval') }}</th>
                <th scope="col"><span class="sr-only">{{ __('ui.actions') }}</span></th>
            </tr>
        </thead>
        <tbody>
            @foreach($templates as $tpl)
            <tr>
                <td>
                    <code class="text-sm">{{ $tpl->document_type_code }}</code>
                </td>
                <td>{{ $tpl->institution_name_ar ?? '— ('.__('documents.org_wide', [], null, 'Org-wide').')' }}</td>
                <td>
                    @if($tpl->ar_available) <span class="badge badge--info">AR</span> @endif
                    @if($tpl->en_available) <span class="badge badge--secondary">EN</span> @endif
                </td>
                <td>
                    @if($tpl->active_version_id)
                        <span class="status-badge status-badge--active">
                            v{{ $tpl->active_version_number }} ({{ $tpl->active_version_locale }})
                        </span>
                    @else
                        <span class="text-muted text-sm">{{ __('documents.no_active_version', [], null, 'No active version') }}</span>
                    @endif
                </td>
                <td>
                    @if($tpl->approval_required)
                        <span class="badge badge--warning">{{ __('documents.approval_required', [], null, 'Required') }}</span>
                    @else
                        <span class="text-muted text-sm">{{ __('documents.no_approval', [], null, 'Not required') }}</span>
                    @endif
                </td>
                <td>
                    <a href="{{ route('admin.documents.template-versions', $tpl->id) }}" class="btn btn--sm btn--ghost">
                        {{ __('documents.manage_btn', [], null, 'Manage') }}
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>
