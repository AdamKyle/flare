@extends('layouts.app')

@section('content')
    <div class="mx-auto w-full px-4">
        <div class="flex flex-col gap-3 py-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h4 class="mt-3">Reason For Ban</h4>
            </div>
            <div>
                <a href="{{ url()->previous() }}" class="inline-flex w-full items-center justify-center rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2 dark:bg-red-700 dark:hover:bg-red-600 dark:focus:ring-offset-gray-900 md:w-auto">Cancel</a>
            </div>
        </div>

        <div class="card">
            <div class="p-5 md:p-10">
                <form method="POST"
                    action="{{ route('ban.user.with.reason', [
                        'user' => $user,
                    ]) }}">
                    @csrf

                    <input type="hidden" name="for" value="{{ $for }}" />

                    @php
                        $reasonHasError = $errors->has('reason');
                    @endphp
                    <div class="mb-5 w-full">
                        <label for="reason"
                            class="mb-2 block text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('Reason for ban') }}</label>

                        <div class="w-full">
                            <textarea
                                id="reason"
                                class="block w-full rounded-md border bg-white px-3 py-2 text-sm text-gray-900 shadow-sm placeholder:text-gray-500 focus:outline-none focus:ring-2 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-400 {{ $reasonHasError ? 'border-red-600 focus:border-red-600 focus:ring-red-600 dark:border-red-500 dark:focus:border-red-500 dark:focus:ring-red-500' : 'border-gray-300 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600' }}"
                                name="reason"
                                required
                                autofocus
                                aria-describedby="reason-help{{ $reasonHasError ? ' reason-error' : '' }}"
                                @if($reasonHasError) aria-invalid="true" @endif
                            >{{ old('reason', $email ?? '') }}</textarea>
                            <small id="reason-help" class="mt-2 block text-sm text-gray-600 dark:text-gray-400">This reason will be emailed to the user as a
                                reason why.</small>

                            @error('reason')
                                <p id="reason-error" class="mt-2 text-sm font-medium text-red-700 dark:text-red-400" role="alert">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-0 flex flex-col md:flex-row md:justify-end">
                        <div>
                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-md bg-primary-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:bg-primary-600 dark:hover:bg-primary-500 dark:focus:ring-offset-gray-900 md:w-auto">
                                {{ __('Submit Request') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
