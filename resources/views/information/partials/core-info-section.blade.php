<x-core.layout.info-container>
  <x-core.page.title
    title="{{$pageTitle}}"
    route="{{url()->previous()}}"
    color="success"
    link="Back"
  >
    @auth
      @if (auth()->user()->hasRole('Admin'))
        <x-core.buttons.link-buttons.primary-button
          href="{{route('admin.info-management.up-page', ['infoPage' => $pageId,])}}"
          css="tw-ml-2"
        >
          Edit Page
        </x-core.buttons.link-buttons.primary-button>
      @endif
    @endauth
  </x-core.page.title>

  <div class="m-auto min-w-full pb-10">
    @foreach ($sections as $section)
      @if (is_null($section['content_image_path']))
        <div
          class="prose dark:prose-invert mt-[30px] max-w-[100%]"
          id="{{ $section['order'] }}"
        >
          <x-core.cards.card>
            {!! $section['content'] !!}
          </x-core.cards.card>
        </div>
      @else
        <div
          class="m-auto grid md:grid-cols-2 md:gap-4"
          id="{{ $section['order'] }}"
        >
          <div class="prose dark:prose-invert max-w-[100%] md:mt-[30px]">
            <x-core.cards.card>
              {!! $section['content'] !!}
            </x-core.cards.card>
          </div>

          <div class="mb-4">
            <img
              src="{{ Storage::disk('info-sections-images')->url($section['content_image_path']) }}"
              class="glightbox max-w-full cursor-pointer rounded-sm border bg-white p-1 md:mt-[30px] md:max-w-[475px]"
              alt="image"
            />
            <div
              class="relative top-[10px] w-1/2 text-gray-700 italic dark:text-white"
            >
              Click/Tap me to make me larger.
            </div>
          </div>
        </div>
      @endif

      @if (! is_null($section['live_wire_component']) && $section['live_wire_component'] !== 'null' && ($section['item_table_type'] === null || $section['item_table_type'] === 'null'))
        <div
          class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
        ></div>

        @livewire($section['live_wire_component'])
      @endif

      @if (! is_null($section['item_table_type']) && $section['item_table_type'] !== 'undefined')
        <div
          id="items-table"
          data-item-table-type="{{ $section['item_table_type'] }}"
        ></div>

        @push('scripts')
          @vite('resources/js/items-table-component.ts')
        @endpush
      @endif

      @if (end($sections) !== $section)
        <div
          class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
        ></div>
      @endif
    @endforeach
  </div>
</x-core.layout.info-container>
@push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      if (window.location.hash) {
        var target = document.querySelector(window.location.hash);
        if (target) {
          target.scrollIntoView();
        }
      }
    });
  </script>
@endpush
