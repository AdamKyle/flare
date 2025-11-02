@props([
  'title' => 'Warning',
  'icon' => 'far fa-question-circle',
])

<div
  x-data="{ show: true }"
  x-show="show"
  class="relative mx-auto flex w-full items-center justify-between rounded-md border-2 border-solid border-yellow-400 bg-yellow-100 px-3 py-3 text-yellow-700 shadow-sm shadow-red-200 dark:border-yellow-500 dark:bg-yellow-200 dark:text-yellow-700 dark:shadow-gray-900"
  role="alert"
>
  <div>
    <p class="dark:text-yellow-800-800 mb-5 font-bold text-yellow-700">
      <i class="{{ $icon }}"></i>
      {{ $title }}
    </p>
    {{ $slot }}
  </div>
  <div>
    <button type="button" @click="show = false" class="text-gray-800">
      <span class="text-2xl">&times;</span>
    </button>
  </div>
</div>
