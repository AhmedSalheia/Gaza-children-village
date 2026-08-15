@php /** @var \App\Livewire\Guardian\Dashboard $this */ @endphp

<div>
    <div style="margin-block-end:var(--space-6)">
        <h1 style="font-size:var(--text-2xl);font-weight:700;color:var(--text-primary);margin:0">
            {{ __('ui.my_children', [], null, 'My Children') }}
        </h1>
        <p style="color:var(--text-secondary);margin-block-start:var(--space-1);font-size:var(--text-sm)">
            {{ __('ui.guardian_dashboard_subtitle', [], null, 'Select a child to view their academic information.') }}
        </p>
    </div>

    @if(! $hasChildren)
        {{-- No eligible relationships --}}
        <div class="empty-state" role="status" aria-live="polite">
            <div class="empty-state__icon" aria-hidden="true">👤</div>
            <h2 class="empty-state__title">{{ __('ui.no_children_linked', [], null, 'No children linked to your account') }}</h2>
            <p class="empty-state__body">
                {{ __('ui.no_children_linked_body', [], null, 'Contact the school administration to link your account to your children\'s records.') }}
            </p>
        </div>
    @else
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:var(--space-5)">
            @foreach($children as $child)
                @php
                    $placement = $placements[$child->id] ?? null;
                @endphp
                <a
                    href="{{ route('guardian.students.detail', ['studentProfileId' => $child->id]) }}"
                    wire:navigate
                    style="display:block;text-decoration:none;color:inherit"
                >
                    <div class="card" style="height:100%;display:flex;flex-direction:column;gap:var(--space-3);transition:box-shadow var(--transition-fast)">
                        <div style="display:flex;align-items:center;gap:var(--space-3)">
                            <div style="width:48px;height:48px;border-radius:50%;background:var(--brand-primary,#1a56db);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                <span style="color:white;font-size:var(--text-xl);font-weight:700;line-height:1">
                                    {{ mb_substr($child->full_name_ar, 0, 1) }}
                                </span>
                            </div>
                            <div style="min-width:0">
                                <div style="font-weight:700;font-size:var(--text-lg);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                    {{ $child->full_name_ar }}
                                </div>
                                @if($child->full_name_en)
                                <div style="font-size:var(--text-sm);color:var(--text-secondary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                    {{ $child->full_name_en }}
                                </div>
                                @endif
                            </div>
                        </div>

                        @if($placement)
                        <div style="background:var(--neutral-50,#f9fafb);border-radius:var(--radius-sm);padding:var(--space-3);font-size:var(--text-sm)">
                            <div style="display:flex;align-items:center;gap:var(--space-2);color:var(--text-secondary)">
                                <span aria-hidden="true">🏫</span>
                                <span>{{ $placement->institution_name }}</span>
                            </div>
                            <div style="display:flex;align-items:center;gap:var(--space-2);color:var(--text-secondary);margin-block-start:var(--space-1)">
                                <span aria-hidden="true">📚</span>
                                <span>{{ $placement->level_name }} — {{ $placement->class_group_name }}</span>
                            </div>
                        </div>
                        @else
                        <div style="font-size:var(--text-sm);color:var(--text-secondary);font-style:italic">
                            {{ __('ui.no_active_placement', [], null, 'No active placement this semester') }}
                        </div>
                        @endif

                        <div style="margin-block-start:auto;text-align:end">
                            <span style="font-size:var(--text-sm);color:var(--interactive-primary,#1a56db);font-weight:500">
                                {{ __('ui.view_details', [], null, 'View details') }} →
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>

<style>
.card:hover{box-shadow:0 4px 12px rgba(0,0,0,0.12);}
.card{background:var(--surface-card);border:1px solid var(--card-border);border-radius:var(--card-radius);padding:var(--card-padding);box-shadow:var(--card-shadow)}
</style>
