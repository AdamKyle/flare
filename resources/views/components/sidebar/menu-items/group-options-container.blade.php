@props([
    'headerTitle',
])

<div>
  <h3 class="mb-4 text-xs uppercase leading-[20px] text-gray-600 dark:text-gray-400">
    <span class="menu-group-title">{{$headerTitle}}</span>
  </h3>
  <ul class="mb-6 flex flex-col gap-4">
    {{ $slot  }}
  </ul>
</div>