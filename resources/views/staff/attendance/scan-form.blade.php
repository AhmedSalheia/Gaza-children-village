@extends('layouts.staff')

@section('title', __('ui.attend_manual_scan_entry'))

@section('content')
<div class="max-w-md mx-auto mt-16 p-8 bg-white rounded-xl shadow-lg space-y-6">
    <div class="text-center">
        <div class="text-2xl font-bold text-teal-700 mb-1">GCV DATA</div>
        <div class="text-gray-500 text-sm">{{ __('ui.attend_manual_checkin') }}</div>
    </div>

    @if(session('scan_success'))
        <div class="bg-green-50 border border-green-200 rounded p-4 text-green-800 text-sm text-center">
            {{ session('scan_success') }}
        </div>
    @endif

    @if(session('scan_error'))
        <div class="bg-red-50 border border-red-200 rounded p-4 text-red-800 text-sm text-center">
            {{ session('scan_error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('attend') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ __('ui.attend_your_token') }}
            </label>
            <input type="text"
                   name="token"
                   required
                   autocomplete="off"
                   class="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-teal-400"
                   placeholder="{{ __('ui.attend_enter_token') }}">
            @error('token')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('ui.period') }}</label>
            <select name="operational_period"
                    required
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-400">
                @foreach($periods ?? [] as $period)
                    <option value="{{ $period->id }}">{{ $period->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.attend_i_am') }}</label>
            <div class="grid grid-cols-2 gap-3">
                <label class="flex items-center gap-2 border rounded-lg px-4 py-3 cursor-pointer hover:bg-teal-50">
                    <input type="radio" name="direction" value="arrival" checked>
                    <span class="text-sm">{{ __('ui.attend_arriving') }}</span>
                </label>
                <label class="flex items-center gap-2 border rounded-lg px-4 py-3 cursor-pointer hover:bg-teal-50">
                    <input type="radio" name="direction" value="departure">
                    <span class="text-sm">{{ __('ui.attend_departing') }}</span>
                </label>
            </div>
        </div>

        <button type="submit"
                class="w-full py-3 bg-teal-600 text-white text-sm font-semibold rounded-lg hover:bg-teal-700">
            {{ __('ui.attend_check_in_out') }}
        </button>
    </form>

    <p class="text-center text-xs text-gray-400">
        {{ __('ui.attend_checkin_confirm_notice') }}
    </p>
</div>
@endsection
