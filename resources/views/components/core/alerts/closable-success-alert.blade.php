@props([
  'title' => 'Oh Christ Child!',
  'icon' => 'fas fa-exclamation-triangle',
])

<div
  x-data="{ show: true }"
  x-show="show"
  class="relative mx-auto flex w-full items-center justify-between rounded-md border-2 border-solid border-green-700 bg-green-100 px-3 py-3 text-green-700 shadow-sm shadow-green-200 dark:border-green-600 dark:bg-green-200 dark:text-green-700 dark:shadow-gray-900"
  role="alert"
>
  <div>
    <p class="dark:text-green-800-800 mb-5 font-bold text-green-700">
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
