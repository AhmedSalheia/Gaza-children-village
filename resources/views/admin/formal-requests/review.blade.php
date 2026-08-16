@php
    /**
     * Admin portal — formal request management review screen.
     * Wire model: App\Livewire\Admin\FormalRequests\ManagementReview
     */
@endphp
<div class="mx-auto max-w-3xl space-y-6">
    <div class="flex items-start justify-between">
        <div>
            <a href="{{ route('admin.formal-requests.index') }}" class="text-blue-600 hover:underline text-sm">← Inbox</a>
            <h1 class="mt-1 text-xl font-semibold text-gray-900">{{ $request->title_en }}</h1>
            <p class="text-xs text-gray-500">{{ $request->request_number }} · Institution #{{ $request->institution_id }}</p>
        </div>
        <span class="rounded-full px-3 py-1 text-xs font-medium bg-blue-100 text-blue-700">
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

    {{-- Request details --}}
    <div class="rounded border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="mb-3 font-medium text-gray-800">Request Details</h2>
        <dl class="divide-y divide-gray-100 text-sm">
            <div class="grid grid-cols-3 py-2"><dt class="text-gray-500">Type</dt><dd class="col-span-2">{{ ucwords($request->request_type) }}</dd></div>
            <div class="grid grid-cols-3 py-2"><dt class="text-gray-500">Arabic title</dt><dd class="col-span-2" dir="rtl">{{ $request->title_ar }}</dd></div>
            <div class="grid grid-cols-3 py-2"><dt class="text-gray-500">Priority</dt><dd class="col-span-2">{{ ['', 'Low', 'Medium', 'High', 'Urgent'][$request->priority] ?? '' }}</dd></div>
            <div class="grid grid-cols-3 py-2"><dt class="text-gray-500">Version</dt><dd class="col-span-2">{{ $request->version }}</dd></div>
            @if($request->due_date)
                <div class="grid grid-cols-3 py-2"><dt class="text-gray-500">Due date</dt><dd class="col-span-2">{{ $request->due_date->format('d M Y') }}</dd></div>
            @endif
            @if($request->content_hash)
                <div class="grid grid-cols-3 py-2">
                    <dt class="text-gray-500">Signed</dt>
                    <dd class="col-span-2 font-mono text-xs text-gray-500">Hash: {{ substr($request->content_hash, 0, 16) }}…</dd>
                </div>
            @endif
            <div class="py-2">
                <dt class="mb-1 text-gray-500">Body</dt>
                <dd class="whitespace-pre-wrap text-gray-800">{{ is_array($request->body) ? ($request->body['text'] ?? '') : $request->body }}</dd>
            </div>
        </dl>
    </div>

    {{-- Supporting attachments --}}
    <div class="rounded border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="mb-3 font-medium text-gray-800">Supporting Attachments</h2>
        @if($attachments->isEmpty())
            <p class="text-sm text-gray-500">No attachments submitted with this request.</p>
        @else
            <ul class="divide-y divide-gray-100 text-sm">
                @foreach($attachments as $link)
                    <li class="flex items-center justify-between py-2">
                        <span class="text-gray-700">
                            {{ $link->attachment?->original_filename ?? $link->attachment_id }}
                        </span>
                        <a href="{{ route('admin.attachments.download', $link->attachment_id) }}"
                           class="text-blue-600 hover:underline text-xs">Download</a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    {{-- Management actions --}}
    @if(! $request->isTerminal())
        <div class="rounded border border-gray-200 bg-white p-4 shadow-sm">
            <h2 class="mb-3 font-medium text-gray-800">Actions</h2>
            <div class="flex flex-wrap gap-2">
                @if($request->current_status === 'submitted_to_management')
                    <button wire:click="startReview"
                            class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">
                        Start Review
                    </button>
                @endif

                @if($request->current_status === 'under_management_review')
                    <button wire:click="$set('action', 'accept')"
                            class="rounded bg-green-600 px-4 py-2 text-sm text-white hover:bg-green-700">Accept</button>
                    <button wire:click="$set('action', 'reject')"
                            class="rounded bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700">Reject</button>
                    <button wire:click="$set('action', 'clarify')"
                            class="rounded border border-amber-400 bg-amber-50 px-4 py-2 text-sm text-amber-700">Request Clarification</button>
                @endif

                @if(in_array($request->current_status, ['accepted', 'rejected']))
                    <button wire:click="$set('action', 'respond')"
                            class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">Add Response</button>
                @endif

                @if($request->current_status === 'responded')
                    <button wire:click="close" wire:confirm="Close this request?"
                            class="rounded border border-gray-400 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        Close Request
                    </button>
                @endif
            </div>

            {{-- Action forms --}}
            @if($action === 'accept')
                <div class="mt-4 rounded border border-green-200 bg-green-50 p-3">
                    <h3 class="mb-2 font-medium text-green-800">Accept Request</h3>
                    <textarea wire:model="comment" rows="3"
                              class="w-full rounded border border-green-300 px-3 py-2 text-sm"
                              placeholder="Optional acceptance note…"></textarea>
                    <div class="mt-2 flex gap-2">
                        <button wire:click="accept" class="rounded bg-green-600 px-4 py-2 text-sm text-white hover:bg-green-700">Confirm Accept</button>
                        <button wire:click="$set('action', '')" class="rounded border border-gray-300 px-4 py-2 text-sm text-gray-700">Cancel</button>
                    </div>
                </div>
            @elseif($action === 'reject')
                <div class="mt-4 rounded border border-red-200 bg-red-50 p-3">
                    <h3 class="mb-2 font-medium text-red-800">Reject Request</h3>
                    <textarea wire:model="comment" rows="3"
                              class="w-full rounded border border-red-300 px-3 py-2 text-sm"
                              placeholder="Rejection reason (required)…" required></textarea>
                    <div class="mt-2 flex gap-2">
                        <button wire:click="reject" class="rounded bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700">Confirm Reject</button>
                        <button wire:click="$set('action', '')" class="rounded border border-gray-300 px-4 py-2 text-sm text-gray-700">Cancel</button>
                    </div>
                </div>
            @elseif($action === 'clarify')
                <div class="mt-4 rounded border border-amber-200 bg-amber-50 p-3">
                    <h3 class="mb-2 font-medium text-amber-800">Request Clarification</h3>
                    <textarea wire:model="comment" rows="3"
                              class="w-full rounded border border-amber-300 px-3 py-2 text-sm"
                              placeholder="What needs to be clarified? (required)"></textarea>
                    <div class="mt-2 flex gap-2">
                        <button wire:click="requestClarification" class="rounded bg-amber-600 px-4 py-2 text-sm text-white hover:bg-amber-700">Send</button>
                        <button wire:click="$set('action', '')" class="rounded border border-gray-300 px-4 py-2 text-sm text-gray-700">Cancel</button>
                    </div>
                </div>
            @elseif($action === 'respond')
                <div class="mt-4 rounded border border-blue-200 bg-blue-50 p-3">
                    <h3 class="mb-2 font-medium text-blue-800">Management Response</h3>
                    <textarea wire:model="responseText" rows="5"
                              class="w-full rounded border border-blue-300 px-3 py-2 text-sm"
                              placeholder="Enter the official response…" required></textarea>
                    <div class="mt-2 flex gap-2">
                        <button wire:click="respond" class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">Submit Response</button>
                        <button wire:click="$set('action', '')" class="rounded border border-gray-300 px-4 py-2 text-sm text-gray-700">Cancel</button>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- Comments visible to management --}}
    <div class="rounded border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="mb-3 font-medium text-gray-800">Comments</h2>
        @forelse($comments as $comment)
            <div class="mb-3 rounded border border-gray-100 bg-gray-50 p-3 text-sm">
                <div class="mb-1 flex items-center justify-between">
                    <span class="font-medium text-gray-700">{{ ucwords($comment->commenter_actor_type) }}</span>
                    <span class="text-xs text-gray-400">{{ $comment->created_at->format('d M Y H:i') }}</span>
                </div>
                <p class="whitespace-pre-wrap text-gray-800">{{ $comment->comment_text }}</p>
            </div>
        @empty
            <p class="text-sm text-gray-400">No comments.</p>
        @endforelse

        @if(! $request->isTerminal())
            <div class="mt-4 border-t pt-3">
                <textarea wire:model="comment" rows="2"
                          class="w-full rounded border border-gray-300 px-3 py-2 text-sm"
                          placeholder="Add an internal note…"></textarea>
                <div class="mt-2">
                    <button wire:click="addComment"
                            class="rounded bg-gray-700 px-4 py-1.5 text-sm text-white hover:bg-gray-800">Add Note</button>
                </div>
            </div>
        @endif
    </div>
</div>
