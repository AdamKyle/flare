@extends('layouts.information')

@section('content')
  <x-core.layout.info-container>
    <x-core.page.title
      title="Search Results"
      route="{{ url()->previous() }}"
      color="success"
      link="Back"
    ></x-core.page.title>

    <form id="search-form" method="GET" action="{{ route('info.search') }}">
      @csrf

      <div class="justify-content-center mb-5 flex">
        <input
          id="info_search"
          type="text"
          class="form-control mr-2"
          name="info_search"
          value="{{ $query !== null ? $query : old('info_search') }}"
        />
        <x-core.buttons.primary-button type="submit">
          Search
        </x-core.buttons.primary-button>
      </div>
    </form>

    @if (count($results) > 0)
      <p class="my-4">Your search results for: {{ $query }}</p>
      @foreach ($results as $page)
        @php
          $matchingSections = array_filter($page->page_sections, function ($section) use ($query) {
            return stripos($section['content'], $query) !== false;
          });

          $snippet = '';
          $pos = false;
          $matchingSection = reset($matchingSections);

          if ($matchingSection) {
            $content = strip_tags($matchingSection['content']);
            $content = preg_replace('/[[:^ascii:]]/', '', $content);

            $pos = stripos($content, $query);

            if ($pos !== false) {
              $startPos = max(0, $pos - 120);
              $endPos = min(strlen($content), $pos + 120 + strlen($query));
              $snippet = htmlspecialchars(substr($content, $startPos, $endPos - $startPos)) . '...';
              $snippet = htmlspecialchars_decode($snippet);
              $snippet = preg_replace("/($query)/i", '<strong>$1</strong>', $snippet);
            }
          }
        @endphp

        @if ($matchingSection && $pos !== false)
          <div class="my-4">
            <a
              href="{{ route('info.page', ['pageName' => $page->page_name]) }}#{{ $matchingSection['order'] }}"
              class="text-gray-900 dark:text-white"
            >
              <x-core.cards.card-with-hover>
                <h3
                  class="text-blue-700 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-500"
                >
                  {{ ucfirst(str_replace('-', ' ', $page->page_name)) }}
                </h3>
                <p class="my-4">
                  {!! $snippet !!}
                </p>
              </x-core.cards.card-with-hover>
            </a>
          </div>
        @endif
      @endforeach
    @else
      <x-core.cards.card-with-title css="mb-5" title="Nothing found">
        <p class="my-4">Sorry, we found nothing for the search result:</p>
        <p class="my-4">"{{ $query }}"</p>
      </x-core.cards.card-with-title>
    @endif
  </x-core.layout.info-container>
@endsection
