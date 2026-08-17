@php
    /**
     * Staff portal — formal request detail / action screen.
     * Wire model: App\Livewire\Staff\FormalRequests\FormalRequestDetail
     */
@endphp
<div class="mx-auto max-w-3xl space-y-6">
    {{-- Header --}}
    <div class="flex items-start justify-between">
        <div>
            <a href="{{ route('staff.formal-requests.index') }}" class="text-blue-600 hover:underline text-sm">← {{ __('ui.formal_requests') }}</a>
            <h1 class="mt-1 text-xl font-semibold text-gray-900">{{ $request->title_en }}</h1>
            <p class="text-xs text-gray-500">{{ $request->request_number }} · v{{ $request->version }}</p>
            @if($request->branched_from_id)
                <p class="mt-0.5 text-xs text-amber-600">
                    {{ __('requests.branched_from', ['id' => $request->branched_from_id]) }}
                </p>
            @endif
        </div>
        <span class="rounded-full px-3 py-1 text-xs font-medium
            @if(in_array($request->current_status, ['closed','cancelled','superseded'])) bg-gray-100 text-gray-600
            @elseif($request->current_status === 'signed') bg-green-100 text-green-700
            @else bg-blue-100 text-blue-700 @endif">
            {{ ucwords(str_replace('_', ' ', $request->current_status)) }}
        </span>
    </div>

    @if($flashMessage)
        <div class="rounded border border-green-300 bg-green-50 px-4 py-2 text-sm text-green-800">
            {{ $flashMessage }}
        </div>
    @endif

    @if(count($errors) > 0)
        <div class="rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            @foreach($errors as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    {{-- Request details / edit form --}}
    <div class="rounded border border-gray-200 bg-white p-5 shadow-sm">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="font-medium text-gray-800">{{ __('requests.request_details') }}</h2>
            @if($canPrepare && $request->isEditable())
                <button wire:click="toggleEdit" class="text-sm text-blue-600 hover:underline">
                    {{ $editMode ? __('requests.cancel_edit') : __('ui.edit') }}
                </button>
            @endif
        </div>

        @if($editMode)
            <div class="space-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-700">{{ __('requests.arabic_title') }}</label>
                    <input wire:model="titleAr" type="text" dir="rtl"
                           class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">{{ __('requests.english_title') }}</label>
                    <input wire:model="titleEn" type="text"
                           class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">{{ __('requests.body') }}</label>
                    <textarea wire:model="bodyText" rows="6"
                              class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700">{{ __('requests.priority') }}</label>
                        <select wire:model="priority" class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm">
                            <option value="1">{{ __('requests.priority_low') }}</option>
                            <option value="2">{{ __('requests.priority_medium') }}</option>
                            <option value="3">{{ __('requests.priority_high') }}</option>
                            <option value="4">{{ __('requests.priority_urgent') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">{{ __('requests.due_date') }}</label>
                        <input wire:model="dueDate" type="date"
                               class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm">
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t pt-3">
                    <button wire:click="toggleEdit"
                            class="rounded border border-gray-300 px-4 py-2 text-sm text-gray-700">{{ __('ui.cancel') }}</button>
                    <button wire:click="saveEdit"
                            class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">{{ __('ui.save') }}</button>
                </div>
            </div>
        @else
            <dl class="divide-y divide-gray-100 text-sm">
                <div class="grid grid-cols-3 py-2"><dt class="text-gray-500">{{ __('ui.type') }}</dt><dd class="col-span-2">{{ ucwords($request->request_type) }}</dd></div>
                <div class="grid grid-cols-3 py-2"><dt class="text-gray-500">{{ __('requests.arabic_title') }}</dt><dd class="col-span-2" dir="rtl">{{ $request->title_ar }}</dd></div>
                <div class="grid grid-cols-3 py-2"><dt class="text-gray-500">{{ __('requests.priority') }}</dt><dd class="col-span-2">{{ ['', __('requests.priority_low'), __('requests.priority_medium'), __('requests.priority_high'), __('requests.priority_urgent')][$request->priority] ?? '' }}</dd></div>
                @if($request->due_date)
                    <div class="grid grid-cols-3 py-2"><dt class="text-gray-500">{{ __('requests.due_date') }}</dt><dd class="col-span-2">{{ $request->due_date->format('d M Y') }}</dd></div>
                @endif
                <div class="py-2">
                    <dt class="mb-1 text-gray-500">{{ __('requests.body') }}</dt>
                    <dd class="whitespace-pre-wrap text-gray-800">{{ is_array($request->body) ? ($request->body['text'] ?? '') : $request->body }}</dd>
                </div>
                @if($request->response_body)
                    <div class="py-2 bg-blue-50 px-2 rounded">
                        <dt class="mb-1 font-medium text-blue-700">{{ __('requests.management_response') }}</dt>
                        <dd class="whitespace-pre-wrap text-gray-800">{{ is_array($request->response_body) ? ($request->response_body['text'] ?? '') : $request->response_body }}</dd>
                        <p class="mt-1 text-xs text-gray-500">{{ __('requests.received') }} {{ $request->response_at?->format('d M Y') }}</p>
                    </div>
                @endif
            </dl>
        @endif
    </div>

    {{-- Attachments --}}
    <div class="rounded border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="mb-3 font-medium text-gray-800">{{ __('requests.attachments') }}</h2>

        @forelse($attachments as $link)
            @php $att = $link->attachment; @endphp
            @if($att && $att->status === 'available')
                <div class="mb-2 flex items-center justify-between rounded border border-gray-100 bg-gray-50 px-3 py-2 text-sm">
                    <span class="truncate text-gray-700" title="{{ $att->original_filename }}">
                        {{ $att->original_filename }}
                        <span class="ml-1 text-xs text-gray-400">({{ number_format($att->size_bytes / 1024, 1) }} KB)</span>
                    </span>
                    <a href="{{ route('staff.attachments.download', $att->id) }}"
                       class="ml-4 shrink-0 text-blue-600 hover:underline text-xs"
                       target="_blank">{{ __('ui.download') }}</a>
                </div>
            @endif
        @empty
            <p class="text-sm text-gray-400">{{ __('requests.no_attachments_yet') }}</p>
        @endforelse

        @if($canPrepare && $request->isEditable())
            <div class="mt-4 border-t pt-3">
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('requests.upload_attachment_label') }}</label>
                <input wire:model="attachmentFile" type="file" accept=".pdf,.jpg,.jpeg,.png"
                       class="block text-sm text-gray-600">
                @error('attachmentFile') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                <button wire:click="uploadAttachment" wire:loading.attr="disabled"
                        class="mt-2 rounded bg-gray-600 px-4 py-1.5 text-sm text-white hover:bg-gray-700 disabled:opacity-50">
                    {{ __('ui.upload') }}
                </button>
            </div>
        @endif
    </div>

    {{-- Action buttons --}}
    @if(! $editMode)
        <div class="rounded border border-gray-200 bg-white p-4 shadow-sm">
            <h2 class="mb-3 font-medium text-gray-800">{{ __('ui.actions') }}</h2>
            <div class="flex flex-wrap gap-2">
                @if($canPrepare && $request->current_status === 'draft')
                    <button wire:click="submitForReview"
                            class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">
                        {{ __('requests.submit_for_internal_review') }}
                    </button>
                @endif

                @if($canPrepare && $request->current_status === 'returned_to_preparer')
                    <button wire:click="resubmit"
                            class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">
                        {{ __('requests.resubmit_for_review') }}
                    </button>
                @endif

                @if($canSubmit && $request->current_status === 'signed')
                    <button wire:click="submitToManagement"
                            class="rounded bg-green-600 px-4 py-2 text-sm text-white hover:bg-green-700">
                        {{ __('requests.submit_to_management') }}
                    </button>
                @endif

                @if($canReview && $request->current_status === 'internal_review')
                    <button wire:click="showReturn"
                            class="rounded border border-amber-400 bg-amber-50 px-4 py-2 text-sm text-amber-700 hover:bg-amber-100">
                        {{ __('requests.return_to_preparer') }}
                    </button>
                @endif

                @if($canSign && $request->current_status === 'internal_review')
                    <button wire:click="showSign"
                            class="rounded bg-emerald-600 px-4 py-2 text-sm text-white hover:bg-emerald-700">
                        {{ __('requests.sign_request') }}
                    </button>
                @endif

                @if($canPrepare && $request->current_status === 'clarification_requested')
                    {{-- Clarification response uses the comment form below --}}
                @endif

                @if($canSupersede && ! $showSupersedeForm)
                    <button wire:click="showSupersede"
                            class="rounded border border-violet-400 bg-violet-50 px-4 py-2 text-sm text-violet-700 hover:bg-violet-100">
                        {{ __('requests.create_followup') }}
                    </button>
                @endif
            </div>
        </div>
    @endif

    {{-- Return-to-preparer form --}}
    @if($showReturnForm)
        <div class="rounded border border-amber-200 bg-amber-50 p-4">
            <h3 class="mb-2 font-medium text-amber-800">{{ __('requests.return_to_preparer') }}</h3>
            <textarea wire:model="returnReason" rows="3"
                      class="w-full rounded border border-amber-300 px-3 py-2 text-sm"
                      placeholder="{{ __('requests.return_reason_placeholder') }}"></textarea>
            <div class="mt-2 flex gap-2">
                <button wire:click="returnToPreparer"
                        class="rounded bg-amber-600 px-4 py-2 text-sm text-white hover:bg-amber-700">
                    {{ __('requests.confirm_return') }}
                </button>
                <button wire:click="$set('showReturnForm', false)"
                        class="rounded border border-gray-300 px-4 py-2 text-sm text-gray-700">{{ __('ui.cancel') }}</button>
            </div>
        </div>
    @endif

    {{-- Signing form --}}
    @if($showSignForm)
        <div class="rounded border border-emerald-200 bg-emerald-50 p-4">
            <h3 class="mb-2 font-medium text-emerald-800">{{ __('requests.electronic_signature') }}</h3>
            <p class="mb-3 text-sm text-emerald-700">
                {{ __('requests.sign_intro') }}
            </p>

            @if($pendingTokenId === null)
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('requests.enter_password_confirm') }}</label>
                    <input wire:model="credential" type="password"
                           class="w-full rounded border border-gray-300 px-3 py-2 text-sm"
                           placeholder="{{ __('requests.portal_password_label') }}">
                    <div class="mt-2 flex gap-2">
                        <button wire:click="issueSigningToken"
                                class="rounded bg-emerald-600 px-4 py-2 text-sm text-white hover:bg-emerald-700">
                            {{ __('requests.verify_proceed') }}
                        </button>
                        <button wire:click="$set('showSignForm', false)"
                                class="rounded border border-gray-300 px-4 py-2 text-sm text-gray-700">{{ __('ui.cancel') }}</button>
                    </div>
                </div>
            @else
                <div class="rounded border border-emerald-300 bg-white p-3 text-sm">
                    <p class="font-medium text-emerald-700">✓ {{ __('requests.identity_verified') }}</p>
                    <p class="mt-1 text-gray-600">{{ __('requests.sign_permanent_notice') }}</p>
                    <div class="mt-3 flex gap-2">
                        <button wire:click="confirmSign"
                                class="rounded bg-emerald-600 px-4 py-2 text-sm text-white hover:bg-emerald-700">
                            {{ __('requests.confirm_signature') }}
                        </button>
                        <button wire:click="$set('showSignForm', false); $set('pendingTokenId', null)"
                                class="rounded border border-gray-300 px-4 py-2 text-sm text-gray-700">{{ __('ui.cancel') }}</button>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- Supersede form --}}
    @if($showSupersedeForm)
        <div class="rounded border border-violet-200 bg-violet-50 p-5">
            <h3 class="mb-1 font-medium text-violet-800">{{ __('requests.create_followup_request') }}</h3>
            <p class="mb-4 text-sm text-violet-600">
                {{ __('requests.supersede_intro') }}
            </p>
            <div class="space-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-700">{{ __('requests.arabic_title') }}</label>
                    <input wire:model="supersedeTitleAr" type="text" dir="rtl"
                           class="mt-1 w-full rounded border border-violet-300 px-3 py-2 text-sm"
                           placeholder="{{ __('requests.supersede_title_ar_placeholder') }}">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">{{ __('requests.english_title') }}</label>
                    <input wire:model="supersedeTitleEn" type="text"
                           class="mt-1 w-full rounded border border-violet-300 px-3 py-2 text-sm"
                           placeholder="{{ __('requests.supersede_title_en_placeholder') }}">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">{{ __('requests.body') }}</label>
                    <textarea wire:model="supersedeBodyText" rows="5"
                              class="mt-1 w-full rounded border border-violet-300 px-3 py-2 text-sm"
                              placeholder="{{ __('requests.supersede_body_placeholder') }}"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700">{{ __('requests.priority') }}</label>
                        <select wire:model="supersedePriority"
                                class="mt-1 w-full rounded border border-violet-300 px-3 py-2 text-sm">
                            <option value="1">{{ __('requests.priority_low') }}</option>
                            <option value="2">{{ __('requests.priority_medium') }}</option>
                            <option value="3">{{ __('requests.priority_high') }}</option>
                            <option value="4">{{ __('requests.priority_urgent') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">{{ __('requests.due_date_optional') }}</label>
                        <input wire:model="supersedeDueDate" type="date"
                               class="mt-1 w-full rounded border border-violet-300 px-3 py-2 text-sm">
                    </div>
                </div>
                <div class="flex gap-3 border-t border-violet-200 pt-3">
                    <button wire:click="supersede"
                            class="rounded bg-violet-600 px-4 py-2 text-sm text-white hover:bg-violet-700">
                        {{ __('requests.create_replacement_draft') }}
                    </button>
                    <button wire:click="cancelSupersede"
                            class="rounded border border-gray-300 px-4 py-2 text-sm text-gray-700">
                        {{ __('ui.cancel') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Clarification response (for secretary when clarification requested) --}}
    @if($request->current_status === 'clarification_requested' && $canPrepare)
        <div class="rounded border border-blue-200 bg-blue-50 p-4">
            <h3 class="mb-2 font-medium text-blue-800">{{ __('requests.respond_clarification_title') }}</h3>
            <textarea wire:model="newComment" rows="4"
                      class="w-full rounded border border-blue-300 px-3 py-2 text-sm"
                      placeholder="{{ __('requests.clarification_response_placeholder') }}"></textarea>
            <div class="mt-2">
                <button wire:click="respondToClarification"
                        class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">
                    {{ __('requests.submit_clarification') }}
                </button>
            </div>
        </div>
    @endif

    {{-- Comments --}}
    <div class="rounded border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="mb-3 font-medium text-gray-800">{{ __('requests.comments') }}</h2>

        @forelse($comments as $comment)
            <div class="mb-3 rounded border border-gray-100 bg-gray-50 p-3 text-sm">
                <div class="mb-1 flex items-center justify-between">
                    <span class="font-medium text-gray-700">{{ ucwords($comment->commenter_actor_type) }}</span>
                    <span class="text-xs text-gray-400">{{ $comment->created_at->format('d M Y H:i') }}</span>
                </div>
                <p class="whitespace-pre-wrap text-gray-800">{{ $comment->comment_text }}</p>
            </div>
        @empty
            <p class="text-sm text-gray-400">{{ __('requests.no_comments_yet') }}</p>
        @endforelse

        @if(! $request->isTerminal() && $request->current_status !== 'clarification_requested')
            <div class="mt-4 border-t pt-3">
                <textarea wire:model="newComment" rows="3"
                          class="w-full rounded border border-gray-300 px-3 py-2 text-sm"
                          placeholder="{{ __('requests.add_comment_placeholder') }}"></textarea>
                <div class="mt-2 flex items-center gap-2">
                    @if($canReview)
                        <select wire:model="commentAudience" class="rounded border border-gray-300 px-2 py-1.5 text-xs">
                            <option value="internal">{{ __('requests.internal_only') }}</option>
                            <option value="all">{{ __('requests.all_parties') }}</option>
                        </select>
                    @endif
                    <button wire:click="addComment"
                            class="rounded bg-gray-700 px-4 py-1.5 text-sm text-white hover:bg-gray-800">
                        {{ __('requests.add_comment') }}
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>
