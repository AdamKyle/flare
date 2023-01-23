<x-core.layout.info-container>
    <x-core.page-title
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
    </x-core.page-title>

    <div class="prose dark:prose-invert min-w-full m-auto pb-10">
        @foreach($sections as $section)

            @if (is_null($section['content_image_path']))
                <div class="mt-[30px]">
                    <x-core.cards.card>
                        {!! $section['content'] !!}
                    </x-core.cards.card>
                </div>
            @else
                <div class="grid md:grid-cols-2 md:gap-4 m-auto">
                    <div class="md:mt-[30px]">
                        <x-core.cards.card>
                            {!! $section['content'] !!}
                        </x-core.cards.card>
                    </div>

                    <div>
                        <img src="{{Storage::disk('info-sections-images')->url($section['content_image_path'])}}" class="rounded-sm p-1 bg-white border max-w-[475px] cursor-pointer glightbox md:mt-[30px]" alt="image"/>
                        <div class="relative top-[-30px] text-gray-700 dark:text-white italic">
                            Click/Tap me to make me larger.
                        </div>
                    </div>
                </div>
            @endif

            @if (!is_null($section['live_wire_component']) && $section['live_wire_component'] !== 'null')
                <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>

                @livewire($section['live_wire_component'])
            @endif

            @if (end($sections) !== $section)
                <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
            @endif
        @endforeach
    </div>
</x-core.layout.info-container>
