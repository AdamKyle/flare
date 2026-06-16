@props([
    'model' => null,
    'label',
    'modelKey' => null,
    'name',
    'helpText' => null,
])

@php
    $fieldName = str_replace('[]', '', $name);
    $resolvedModelKey = $modelKey ?? $fieldName;
    $fieldId = $fieldName;
    $hasError = $errors->has($fieldName);
    $describedBy = collect([
        $helpText ? $fieldId . '-help' : null,
        $hasError ? $fieldId . '-error' : null,
    ])->filter()->implode(' ');
    $isChecked = (bool) old($fieldName, !is_null($model) ? $model->{$resolvedModelKey} : false);
@endphp

<div class="mb-5 w-full">
    <input type="hidden" name="{{ $name }}" value="0">
    <div class="flex items-center gap-3">
        <input
            type="checkbox"
            id="{{ $fieldId }}"
            name="{{ $name }}"
            value="1"
            @checked($isChecked)
            @if($hasError) aria-invalid="true" @endif
            @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
            {{ $attributes->class([
                'h-4 w-4 rounded border bg-white text-primary-500 shadow-sm focus:ring-2 focus:ring-primary-500 dark:bg-gray-800',
                'border-gray-300 dark:border-gray-600' => !$hasError,
                'border-red-600 focus:ring-red-600 dark:border-red-500 dark:focus:ring-red-500' => $hasError,
            ]) }}
        >
        <label class="text-sm font-semibold text-gray-900 dark:text-gray-100" for="{{ $fieldId }}">{{ $label }}</label>
    </div>
    @if($helpText)
        <p id="{{ $fieldId }}-help" class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ $helpText }}</p>
    @endif
    @error($fieldName)
        <p id="{{ $fieldId }}-error" class="mt-2 text-sm font-medium text-red-700 dark:text-red-400" role="alert">
            {{ $message }}
        </p>
    @enderror
</div>
