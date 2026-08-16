@title(__('requests.staff_inbox_title', [], null, 'Correction Requests'))

<div class="page-container">
    <div class="page-header">
        <h1 class="page-title">{{ __('requests.staff_inbox_title', [], null, 'Correction Requests') }}</h1>
    </div>

    @if($requests->isEmpty())
        <div class="empty-state">
            <p>{{ __('requests.no_pending', [], null, 'No pending correction requests.') }}</p>
        </div>
    @else
        <table class="data-table" role="grid">
            <thead>
                <tr>
                    <th scope="col">{{ __('requests.col_student', [], null, 'Student') }}</th>
                    <th scope="col">{{ __('requests.col_field', [], null, 'Field') }}</th>
                    <th scope="col">{{ __('requests.col_status', [], null, 'Status') }}</th>
                    <th scope="col">{{ __('requests.col_date', [], null, 'Submitted') }}</th>
                    <th scope="col"><span class="sr-only">{{ __('ui.actions') }}</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach($requests as $req)
                <tr>
                    <td>{{ $req->student_name }}</td>
                    <td>
                        @php $field = \Modules\Requests\Enums\CorrectionFieldCatalogue::tryFrom($req->field_catalogue_code); @endphp
                        {{ $field?->labelAr() ?? $req->field_catalogue_code }}
                        @if($req->classification === 'sensitive')
                            <span class="badge badge--warning">{{ __('requests.sensitive', [], null, 'Sensitive') }}</span>
                        @endif
                        @if($req->conflict_flag)
                            <span class="badge badge--danger">{{ __('requests.conflict', [], null, 'Conflict') }}</span>
                        @endif
                    </td>
                    <td>
                        <span class="status-badge status-badge--{{ $req->current_state }}">
                            {{ __('workflow.state.' . $req->current_state, [], null, $req->current_state) }}
                        </span>
                    </td>
                    <td>{{ \Carbon\Carbon::parse($req->created_at)->format('Y-m-d') }}</td>
                    <td>
                        <a href="{{ route('staff.corrections.review', $req->id) }}" class="btn btn--sm btn--outline">
                            {{ __('ui.review') }}
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
