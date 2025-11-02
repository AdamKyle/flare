@props([
  'title' => 'Warning',
  'icon' => 'far fa-question-circle',
])

<div
  x-data="{ show: true }"
  x-show="show"
  role="alert"
  class="bg-mango-tango-100 dark:bg-mango-tango-200 border-mango-tango-400 dark:border-mango-tango-500 text-mango-tango-800 dark:text-mango-tango-900 mx-auto mb-6 flex w-full items-start rounded-lg border p-4 shadow-md"
>
  <div class="flex-shrink-0">
    <i class="{{ $icon }} text-2xl" aria-hidden="true"></i>
  </div>
  <div class="ml-4">
    <h3 class="text-lg font-semibold">{{ $title }}</h3>
    <div class="mt-1 text-sm leading-relaxed">
      {{ $slot }}
    </div>
  </div>
</div>
