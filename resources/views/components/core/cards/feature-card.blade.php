<x-core.cards.card-with-hover>
    <div class="flex justify-center items-center">
        <div class="w-1/5 text-5xl lg:text-7xl">
            {{ $icon }}
        </div>
        <div class="w-4/5">
            <h5>
                {{ $title }}
            </h5>
            {{ $slot }}
        </div>
    </div>
</x-core.cards.card-with-hover>
