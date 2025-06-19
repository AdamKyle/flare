@props([
    'name',
    'label',
    'model'    => null,
    'modelKey' => null,
    'rows'     => 4,
])

@php
    $value   = old($name, optional($model)->{$modelKey});
    $errorId = $name . '-error';
@endphp

<div class="mb-5">
    <label
      for="{{ $name }}"
      class="block mb-2 text-sm font-medium text-gray-600 dark:text-gray-300"
    >
        {{ $label }}
    </label>

    <textarea
      id="{{ $name }}"
      name="{{ $name }}"
      rows="{{ $rows }}"
      class="block w-full h-[200px] px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 rounded focus:outline-none focus:ring-2 focus:ring-danube-500 focus:border-danube-500"
      aria-describedby="{{ $errorId }}"
      @error($name) aria-invalid="true" @enderror
    >{{ trim($value ?? '') }}</textarea>

    @error($name)
    <p
      id="{{ $errorId }}"
      class="mt-1 text-sm text-red-600 dark:text-red-400"
    >
        {{ $message }}
    </p>
    @enderror
</div>
