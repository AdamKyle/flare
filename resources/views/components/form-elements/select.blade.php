@props([
  'name',
  'label',
  'model' => null,
  'modelKey' => null,
  'options' => [],
])

@php
  $current = old($name, optional($model)->{$modelKey});
@endphp

<div class="mb-5">
  <label
    for="{{ $name }}"
    class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
  >
    {{ $label }}
  </label>

  <div class="relative">
    <select
      id="{{ $name }}"
      name="{{ $name }}"
      aria-labelledby="{{ $name }}-label"
      class="focus:ring-danube-500 focus:border-danube-500 dark:focus:ring-danube-400 dark:focus:border-danube-400 block w-full appearance-none rounded-md border border-gray-300 bg-white px-4 py-2 pr-10 text-sm text-gray-900 hover:bg-gray-100 focus:bg-gray-100 focus:ring-2 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:hover:bg-gray-700 dark:focus:bg-gray-700"
    >
      <option value="" disabled>{{ __('Please select') }}</option>
      @foreach ($options as $option)
        <option
          value="{{ $option }}"
          {{ $current === $option ? 'selected' : '' }}
        >
          {{ $option }}
        </option>
      @endforeach
    </select>

    <div
      class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3"
    >
      <i
        class="fas fa-chevron-down text-gray-500 dark:text-gray-400"
        aria-hidden="true"
      ></i>
    </div>
  </div>
</div>
