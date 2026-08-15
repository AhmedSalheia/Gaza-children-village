<div class="space-y-6">

    {{-- Flash --}}
    @if($flashMessage)
        <div class="px-4 py-3 rounded text-sm {{ $flashType === 'success' ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800' }}">
            {{ $flashMessage }}
        </div>
    @endif

    {{-- Generated token panel (shown once after generation) --}}
    @if($generatedPlaintextToken)
        <div class="bg-amber-50 border border-amber-300 rounded-lg p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold text-amber-900">{{ __('New QR Credential — Save Now') }}</h3>
                <button wire:click="dismissGeneratedToken"
                        class="text-amber-600 hover:text-amber-800 text-sm">{{ __('Dismiss') }}</button>
            </div>
            <p class="text-sm text-amber-800">
                {{ __('This token will not be shown again. Print or save the QR card for') }}
                <strong>{{ $generatedStaffName }}</strong>.
            </p>

            {{-- Printable QR card — server-rendered SVG, no JavaScript needed --}}
            <div id="qr-card-print"
                 class="bg-white border border-gray-300 rounded-lg p-6 max-w-xs mx-auto text-center print:border-0">
                <div class="text-sm font-bold tracking-wide uppercase text-teal-700 mb-1">GCV DATA</div>
                <div class="text-xs text-gray-500 mb-4">{{ __('Staff Attendance Card') }}</div>

                {{-- Server-generated SVG QR code (endroid/qr-code — no JS required) --}}
                <div class="flex justify-center mb-4">
                    {!! $generatedQrSvg !!}
                </div>

                <div class="font-semibold text-gray-800 text-sm mb-1">{{ $generatedStaffName }}</div>
                {{-- Token shown as text fallback below QR code --}}
                <div class="text-xs text-gray-400 font-mono break-all mt-2">{{ $generatedPlaintextToken }}</div>
            </div>

            <div class="flex gap-3">
                <button onclick="window.print()"
                        class="px-4 py-2 bg-teal-600 text-white text-sm rounded hover:bg-teal-700">
                    {{ __('Print Card') }}
                </button>
            </div>
        </div>
    @endif

    {{-- Staff list --}}
    <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">{{ __('Name') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">{{ __('Active Credential') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">{{ __('Issued') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($staffList as $member)
                    <tr class="{{ $loop->even ? 'bg-gray-50' : '' }}">
                        <td class="px-4 py-3 font-medium text-right">{{ $member->name }}</td>
                        <td class="px-4 py-3 text-right">
                            @if($member->credential_id)
                                <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700">{{ __('Active') }}</span>
                            @else
                                <span class="text-xs text-gray-400">{{ __('None') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-xs text-gray-500">
                            {{ $member->issued_at ? \Carbon\Carbon::parse($member->issued_at)->format('Y-m-d') : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right space-x-2 rtl:space-x-reverse">
                            <button wire:click="generateCredential({{ $member->staff_profile_id }})"
                                    wire:confirm="{{ $member->credential_id ? __('This will revoke the existing credential. Continue?') : __('Generate a new QR credential?') }}"
                                    class="text-xs px-2 py-1 bg-teal-600 text-white rounded hover:bg-teal-700">
                                {{ $member->credential_id ? __('Regenerate') : __('Generate') }}
                            </button>
                            @if($member->credential_id)
                                <button wire:click="revokeCredential({{ $member->credential_id }})"
                                        wire:confirm="{{ __('Revoke this credential? Pending scans will be rejected.') }}"
                                        class="text-xs px-2 py-1 bg-red-600 text-white rounded hover:bg-red-700">
                                    {{ __('Revoke') }}
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-400">{{ __('No staff found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Manual scan form link --}}
    <div class="bg-white border border-gray-200 rounded-lg p-6 space-y-4">
        <h3 class="font-semibold text-gray-800 text-sm">{{ __('Manual Token Entry (Fallback)') }}</h3>
        <p class="text-xs text-gray-500">
            {{ __('If a scanner is unavailable, the staff member can read their token aloud or enter it here.') }}
        </p>
        <a href="{{ route('staff.attendance.scan-form') }}"
           class="inline-block text-sm text-teal-600 hover:underline">
            {{ __('Open manual scan entry form →') }}
        </a>
    </div>

    {{-- Print styles.
         Use visibility (not display:none) so we can re-show a descendant without
         fighting hidden ancestors. The card is positioned fixed so it renders at
         the top of the page regardless of scroll or layout ancestry. --}}
    <style>
        @media print {
            body * { visibility: hidden !important; }
            #qr-card-print,
            #qr-card-print * { visibility: visible !important; }
            #qr-card-print {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                width: 100% !important;
            }
        }
    </style>
</div>
