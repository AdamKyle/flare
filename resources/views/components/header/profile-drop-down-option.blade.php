@props([
  'href' => '#',
  'icon' => '',
])

<li>
  <a
    href="{{ $href }}"
    {{ $attributes->merge([
      'class' => 'group text-theme-sm flex items-center gap-3 rounded-lg px-3 py-2 font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300',
      'aria-label' => trim($slot),
    ]) }}
  >
    <i class="{{ $icon }}" aria-hidden="true"></i>
    <span>{{ $slot }}</span>
  </a>
</li>
