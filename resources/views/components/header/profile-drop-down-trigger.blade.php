@props([
  'image' => '',
  'alt'   => 'User',
])

<button
  type="button"
  @click.prevent="dropdownOpen = ! dropdownOpen"
  aria-haspopup="menu"
  :aria-expanded="dropdownOpen.toString()"
  aria-label="Toggle user menu for {{ trim($slot) }}"
  class="flex items-center text-gray-700 dark:text-gray-400"
>
  <span class="mr-3 h-11 w-11 overflow-hidden rounded-full">
    <img src="{{ $image }}" alt="{{ $alt }}" class="h-full w-full object-cover" />
  </span>

  <span class="text-theme-sm mr-1 block font-medium">
    {{ $slot }}
  </span>

  <i
    aria-hidden="true"
    :class="dropdownOpen && 'rotate-180'"
    class="fas fa-chevron-down stroke-gray-500 dark:stroke-gray-400 transition-transform"
  ></i>
</button>
