@props([
  'name',
  'key',
  'activePages' => [],
  'page',
  'selected',
  'icon' => 'fas fa-file',
])

<li>
  <a
    href="#"
    @click.prevent="selected = selected === '{{ $key }}' ? '' : '{{ $key }}'"
    class="menu-item group"
    :class="(
      selected === '{{ $key }}'
      || @json($activePages).includes(page)
    )
      ? 'menu-item-active'
      : 'menu-item-inactive'"
  >
    <i
      :class="selected === '{{ $key }}'
        ? '{{ $icon }} text-danube-500 dark:text-danube-400'
        : '{{ $icon }} text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300'"
    ></i>

    <span class="menu-item-text dark:text-gray-400">{{ $name }}</span>

    <i
      :class="selected === '{{ $key }}'
        ? 'fas fa-chevron-down ml-auto text-danube-500 dark:text-danube-400'
        : 'fas fa-chevron-right ml-auto text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300'"
    ></i>
  </a>

  <div
    :class="selected === '{{ $key }}'
      ? 'block translate transform overflow-hidden'
      : 'hidden translate transform overflow-hidden'"
  >
    <ul class="menu-dropdown mt-2 flex flex-col gap-1 pl-2 pr-2">
      {{ $slot }}
    </ul>
  </div>
</li>
