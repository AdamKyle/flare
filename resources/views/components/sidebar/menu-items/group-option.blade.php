@props([
  'href',
  'itemKey',
  'pageKey',
  'icon' => 'fas fa-file',
])

<li role="none">
  <a
    href="{{ $href }}"
    role="menuitem"
    @click.prevent="selected = selected === '{{ $itemKey }}' ? '' : '{{ $itemKey }}'"
    :class="(
      selected === '{{ $itemKey }}'
      && page === '{{ $pageKey }}'
    )
      ? 'menu-item-active'
      : 'menu-item-inactive'"
    class="menu-item group flex items-center gap-2"
    :aria-current="
      (selected === '{{ $itemKey }}' && page === '{{ $pageKey }}') ? 'page' : false
    "
  >
    <i
      :class="(
        selected === '{{ $itemKey }}'
        && page === '{{ $pageKey }}'
      )
        ? '{{ $icon }} text-danube-500 dark:text-danube-400'
        : '{{ $icon }} text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300'"
      aria-hidden="true"
    ></i>

    <span class="menu-item-text">
      {{ $slot }}
    </span>
  </a>
</li>
