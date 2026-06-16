@props([
    'label',
    'name',
    'value',
    'key',
    'options',
    'relationIds' => [],
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
    $selectedValues = old($fieldName, $relationIds);
    $selectedValues = is_array($selectedValues) ? $selectedValues : [$selectedValues];
@endphp

<div class="mb-5 w-full">
    <label class="mb-2 block text-sm font-semibold text-gray-900 dark:text-gray-100" for="{{ $fieldId }}">{{ $label }}</label>
    <select
        name="{{ $name }}"
        id="{{ $fieldId }}"
        multiple
        @if($hasError) aria-invalid="true" @endif
        @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->class([
            'block w-full rounded-md border bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2 dark:bg-gray-800 dark:text-gray-100',
            'border-gray-300 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600' => !$hasError,
            'border-red-600 focus:border-red-600 focus:ring-red-600 dark:border-red-500 dark:focus:border-red-500 dark:focus:ring-red-500' => $hasError,
        ]) }}
    >
        @foreach($options as $option)
            @php
                $optionValue = $option->{$value};
                $optionTitle = $option->{$key};
            @endphp
            <option value="{{ $optionValue }}" @selected(in_array((string) $optionValue, array_map('strval', $selectedValues), true))>{{ $optionTitle }}</option>
        @endforeach
    </select>
    @if($helpText)
        <p id="{{ $fieldId }}-help" class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ $helpText }}</p>
    @endif
    @error($fieldName)
        <p id="{{ $fieldId }}-error" class="mt-2 text-sm font-medium text-red-700 dark:text-red-400" role="alert">
            {{ $message }}
        </p>
    @enderror
</div>
