@props([
  'title' => 'Oh Christ Child!',
  'icon' => 'fas fa-exclamation-triangle',
])

<div
  x-data="{ show: true }"
  x-show="show"
  class="relative mx-auto mt-4 mb-5 flex w-full items-center justify-between rounded-md border-2 border-solid border-blue-300 bg-blue-100 px-3 py-3 text-blue-700 shadow-sm shadow-blue-200 dark:border-blue-400 dark:bg-blue-200 dark:text-blue-700 dark:shadow-gray-900"
  role="alert"
>
  <div>
    <p class="dark:text-blue-800-800 mb-5 font-bold text-blue-700">
      <i class="{{ $icon }}"></i>
      {{ $title }}
    </p>
    {{ $slot }}
  </div>
</div>
