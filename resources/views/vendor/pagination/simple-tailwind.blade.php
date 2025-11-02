@if ($paginator->hasPages())
  <nav
    role="navigation"
    aria-label="Pagination Navigation"
    class="flex justify-between"
  >
    {{-- Previous Page Link --}}
    @if ($paginator->onFirstPage())
      <span
        class="relative inline-flex cursor-default items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm leading-5 font-medium text-gray-500"
      >
        {!! __('pagination.previous') !!}
      </span>
    @else
      <a
        href="{{ $paginator->previousPageUrl() }}"
        rel="prev"
        class="focus:shadow-outline-blue relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm leading-5 font-medium text-gray-700 transition duration-150 ease-in-out hover:text-gray-500 focus:border-blue-300 focus:outline-none active:bg-gray-100 active:text-gray-700"
      >
        {!! __('pagination.previous') !!}
      </a>
    @endif

    {{-- Next Page Link --}}
    @if ($paginator->hasMorePages())
      <a
        href="{{ $paginator->nextPageUrl() }}"
        rel="next"
        class="focus:shadow-outline-blue relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm leading-5 font-medium text-gray-700 transition duration-150 ease-in-out hover:text-gray-500 focus:border-blue-300 focus:outline-none active:bg-gray-100 active:text-gray-700"
      >
        {!! __('pagination.next') !!}
      </a>
    @else
      <span
        class="relative inline-flex cursor-default items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm leading-5 font-medium text-gray-500"
      >
        {!! __('pagination.next') !!}
      </span>
    @endif
  </nav>
@endif
