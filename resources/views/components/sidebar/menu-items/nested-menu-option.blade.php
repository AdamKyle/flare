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
    class="menu-dropdown-item dark:text-gray-400 group flex items-center gap-2 px-2 py-1 rounded-md focus:outline-none focus:ring-2 focus:ring-danube-500 hover:bg-gray-100 dark:hover:bg-gray-800"
    :class="page === '{{ $pageKey }}'
      ? 'menu-dropdown-item-active'
      : 'menu-dropdown-item-inactive'"
  >
    <span class="w-5 h-5 flex-shrink-0 inline-flex items-center justify-center">
      <i class="{{ $icon }}" aria-hidden="true"></i>
    </span>


    <span>{{ $slot }}</span>
  </a>
</li>
