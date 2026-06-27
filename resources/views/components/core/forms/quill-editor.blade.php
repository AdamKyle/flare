@push('head')
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
@endpush

@props([
    'type',
    'model' => null,
    'label',
    'modelKey' => null,
    'name',
    'quillId',
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
    $fieldValue = old($fieldName, !is_null($model) ? $model->{$resolvedModelKey} : '');
@endphp

<div class="mb-5 w-full">
    <label id="{{ $quillId }}-label" class="mb-2 block text-sm font-semibold text-gray-900 dark:text-gray-100" for="{{ $quillId }}">{{ $label }}</label>
    <input type="hidden" name="{{ $name }}" value="{{ $fieldValue }}" id="{{ $fieldId }}">
    <div
        id="{{ $quillId }}"
        role="textbox"
        aria-multiline="true"
        aria-labelledby="{{ $quillId }}-label"
        @if($hasError) aria-invalid="true" @endif
        @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->class([
            'min-h-32 w-full rounded-md border bg-white text-sm text-gray-900 shadow-sm dark:bg-gray-800 dark:text-gray-100',
            'border-gray-300 focus-within:border-primary-500 focus-within:ring-2 focus-within:ring-primary-500 dark:border-gray-600' => !$hasError,
            'border-red-600 focus-within:border-red-600 focus-within:ring-2 focus-within:ring-red-600 dark:border-red-500 dark:focus-within:border-red-500 dark:focus-within:ring-red-500' => $hasError,
        ]) }}
    >{!! nl2br($fieldValue) !!}</div>
    @if($helpText)
        <p id="{{ $fieldId }}-help" class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ $helpText }}</p>
    @endif
    @error($fieldName)
        <p id="{{ $fieldId }}-error" class="mt-2 text-sm font-medium text-red-700 dark:text-red-400" role="alert">
            {{ $message }}
        </p>
    @enderror
</div>

@push('scripts')
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

    <script>

        window['{{$name}}'] = new Quill('#{{$quillId}}', {
            theme: 'snow'
        });

        window['{{$name}}'].on('text-change', function() {
            @if ($type === 'html')
                document.getElementById('{{$name}}').value = window['{{$name}}'].root.innerHTML;
            @else
                document.getElementById('{{$name}}').value = window['{{$name}}'].getText();
            @endif
        });
    </script>
@endpush
