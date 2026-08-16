@php
    /**
     * Staff portal — new formal request form.
     * Wire model: App\Livewire\Staff\FormalRequests\NewFormalRequest
     */
@endphp
<div class="mx-auto max-w-2xl">
    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('staff.formal-requests.index') }}" class="text-blue-600 hover:underline text-sm">← Requests</a>
        <h1 class="text-xl font-semibold text-gray-900">New Formal Request</h1>
    </div>

    @if(count($errors) > 0)
        <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            @foreach($errors as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form wire:submit="save" class="space-y-5 rounded border border-gray-200 bg-white p-6 shadow-sm">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Request Type *</label>
                <select wire:model="requestType" class="w-full rounded border border-gray-300 px-3 py-2 text-sm">
                    @foreach($requestTypes as $type)
                        <option value="{{ $type }}">{{ ucwords($type) }}</option>
                    @endforeach
                </select>
                @error('requestType') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Priority *</label>
                <select wire:model="priority" class="w-full rounded border border-gray-300 px-3 py-2 text-sm">
                    @foreach($priorityOptions as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Title (Arabic) *</label>
            <input wire:model="titleAr" type="text" dir="rtl"
                   class="w-full rounded border border-gray-300 px-3 py-2 text-sm"
                   placeholder="عنوان الطلب">
            @error('titleAr') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Title (English) *</label>
            <input wire:model="titleEn" type="text"
                   class="w-full rounded border border-gray-300 px-3 py-2 text-sm"
                   placeholder="Request title">
            @error('titleEn') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Request Body *</label>
            <textarea wire:model="bodyText" rows="8"
                      class="w-full rounded border border-gray-300 px-3 py-2 text-sm"
                      placeholder="Describe your request in detail…"></textarea>
            @error('bodyText') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Due Date (optional)</label>
            <input wire:model="dueDate" type="date" class="rounded border border-gray-300 px-3 py-2 text-sm">
            @error('dueDate') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex justify-end gap-3 border-t pt-4">
            <a href="{{ route('staff.formal-requests.index') }}"
               class="rounded border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                Cancel
            </a>
            <button type="submit"
                    class="rounded bg-blue-600 px-5 py-2 text-sm text-white hover:bg-blue-700">
                Save as Draft
            </button>
        </div>
    </form>
</div>
