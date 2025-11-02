@props([
  'href' => '#',
  'title' => 'Admin Options',
])

<div
  role="banner"
  aria-label="{{ $title }} sidebar header"
  class="sidebar-header flex items-center gap-3 bg-white px-6 py-4 py-8 dark:bg-gray-800"
>
  <a
    href="{{ $href }}"
    class="group focus:ring-danube-500 flex items-center rounded focus:ring-2 focus:outline-none"
  >
    <h1
      class="text-xl font-extrabold tracking-tight text-gray-900 dark:text-gray-100"
    >
      {{ $title }}
    </h1>
  </a>
</div>
