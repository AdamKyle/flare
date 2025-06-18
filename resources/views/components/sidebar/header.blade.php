@props([
    'href' => '#',
    'title' => 'Admin Options',
])

<div
  role="banner"
  aria-label="{{ $title }} sidebar header"
  class="sidebar-header flex items-center gap-3 px-6 py-8 bg-gray-100 dark:bg-gray-900 py-4"
>
  <a
    href="{{ $href }}"
    class="group flex items-center focus:outline-none focus:ring-2 focus:ring-danube-500 rounded"
  >
    <h1 class="text-xl font-extrabold tracking-tight text-gray-900 dark:text-gray-100">
      {{ $title }}
    </h1>
  </a>
</div>
