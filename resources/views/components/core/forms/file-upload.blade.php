@props([
    'model' => null,
    'label',
    'modelKey' => null,
    'name',
    'helpText' => null,
])

@php
    $fieldName = str_replace('[]', '', $name);
    $fieldId = $fieldName;
    $hasError = $errors->has($fieldName);
    $describedBy = collect([
        $helpText ? $fieldId . '-help' : null,
        $hasError ? $fieldId . '-error' : null,
    ])->filter()->implode(' ');
@endphp

<div class="mb-5 w-full">
    <label class="mb-2 block text-sm font-semibold text-gray-900 dark:text-gray-100" for="{{ $fieldId }}">{{ $label }}</label>
    <input
        id="{{ $fieldId }}"
        type="file"
        name="{{ $name }}"
        @if($hasError) aria-invalid="true" @endif
        @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->class([
            'block w-full rounded-md border bg-white text-sm text-gray-900 shadow-sm file:mr-4 file:border-0 file:bg-primary-500 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-primary-600 focus:outline-none focus:ring-2 dark:bg-gray-800 dark:text-gray-100',
            'border-gray-300 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600' => !$hasError,
            'border-red-600 focus:border-red-600 focus:ring-red-600 dark:border-red-500 dark:focus:border-red-500 dark:focus:ring-red-500' => $hasError,
        ]) }}
    >
    @if($helpText)
        <p id="{{ $fieldId }}-help" class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ $helpText }}</p>
    @endif
    @error($fieldName)
        <p id="{{ $fieldId }}-error" class="mt-2 text-sm font-medium text-red-700 dark:text-red-400" role="alert">
            {{ $message }}
        </p>
    @enderror
</div>
