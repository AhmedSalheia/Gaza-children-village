@extends('layouts.staff')

@section('title', __('Manual Scan Entry'))

@section('content')
<div class="max-w-md mx-auto mt-16 p-8 bg-white rounded-xl shadow-lg space-y-6">
    <div class="text-center">
        <div class="text-2xl font-bold text-teal-700 mb-1">GCV DATA</div>
        <div class="text-gray-500 text-sm">{{ __('Manual Attendance Check-In') }}</div>
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
                {{ __('Your attendance token') }}
            </label>
            <input type="text"
                   name="token"
                   required
                   autocomplete="off"
                   class="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-teal-400"
                   placeholder="{{ __('Enter your token here') }}">
            @error('token')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Period') }}</label>
            <select name="operational_period"
                    required
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-400">
                @foreach($periods ?? [] as $period)
                    <option value="{{ $period->id }}">{{ $period->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('I am') }}</label>
            <div class="grid grid-cols-2 gap-3">
                <label class="flex items-center gap-2 border rounded-lg px-4 py-3 cursor-pointer hover:bg-teal-50">
                    <input type="radio" name="direction" value="arrival" checked>
                    <span class="text-sm">{{ __('Arriving') }}</span>
                </label>
                <label class="flex items-center gap-2 border rounded-lg px-4 py-3 cursor-pointer hover:bg-teal-50">
                    <input type="radio" name="direction" value="departure">
                    <span class="text-sm">{{ __('Departing') }}</span>
                </label>
            </div>
        </div>

        <button type="submit"
                class="w-full py-3 bg-teal-600 text-white text-sm font-semibold rounded-lg hover:bg-teal-700">
            {{ __('Check In / Out') }}
        </button>
    </form>

    <p class="text-center text-xs text-gray-400">
        {{ __('Your check-in must be confirmed by a secretary to be official.') }}
    </p>
</div>
@endsection
