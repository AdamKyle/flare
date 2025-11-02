<x-core.cards.card-with-title
  css="mb-5"
  title="Version: {{ $release->version }}, {{ $release->name }}, Published on: {{ $release->release_date->format('M d Y') }}"
>
  <h3 class="mt-2 mb-3"></h3>
  <div class="prose dark:prose-dark mb-20 max-w-7xl dark:text-white">
    {!! $content !!}
  </div>
</x-core.cards.card-with-title>
