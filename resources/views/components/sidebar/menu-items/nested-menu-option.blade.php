@props([
  'href',
  'pageKey',
  'icon' => 'fas fa-file',
])

<li role="none">
  <a
    href="{{ $href }}"
    role="menuitem"
    :aria-current="page === '{{ $pageKey }}' ? 'page' : false"
    class="menu-dropdown-item group focus:ring-danube-500 flex items-center gap-2 rounded-md px-2 py-1 hover:bg-gray-100 focus:ring-2 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-800"
    :class="page === '{{ $pageKey }}'
      ? 'menu-dropdown-item-active'
      : 'menu-dropdown-item-inactive'"
  >
    <span class="inline-flex h-5 w-5 flex-shrink-0 items-center justify-center">
      <i class="{{ $icon }}" aria-hidden="true"></i>
    </span>

    <span>{{ $slot }}</span>
  </a>
</li>
