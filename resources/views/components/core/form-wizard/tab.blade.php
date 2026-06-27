@props([
  'target',
  'primaryTitle',
  'secondaryTitle',
  'isActive' => 'false'
])

<button
    class="{{ 'nav-link h5 !ml-0 w-full min-w-0 rounded-md px-3 py-2 text-center text-gray-900 dark:text-gray-100 ' . ($isActive === 'true' ? 'active' : '') }}"
    type="button"
    role="tab"
    id="{{ $target }}-tab"
    aria-controls="{{ $target }}"
    aria-selected="{{ $isActive === 'true' ? 'true' : 'false' }}"
    tabindex="{{ $isActive === 'true' ? '0' : '-1' }}"
    data-toggle="tab"
    data-target="{{ '#' . $target }}"
>
    {{$primaryTitle}}
    <small class="block text-gray-600 dark:text-gray-400">{{$secondaryTitle}}</small>
</button>
