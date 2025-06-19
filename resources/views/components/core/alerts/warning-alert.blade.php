@props([
    'title' => 'Warning',
    'icon' => 'far fa-question-circle',
])

<div
  x-data="{ show: true }"
  x-show="show"
  role="alert"
  class="flex w-full mx-auto items-start bg-mango-tango-100 dark:bg-mango-tango-200 border border-mango-tango-400 dark:border-mango-tango-500 text-mango-tango-800 dark:text-mango-tango-900 p-4 rounded-lg shadow-md mb-6"
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
