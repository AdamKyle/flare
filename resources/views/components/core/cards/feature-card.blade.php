<x-core.cards.card-with-hover>
    <div class="flex flex-col md:flex-row justify-center items-center space-y-4 md:space-y-0 md:space-x-4">
        <div class="w-1/5 md:w-1/5 text-7xl">
            {{ $icon }}
        </div>
        <div class="w-4/5 md:w-4/5">
            <h5 class="text-lg md:text-xl lg:text-2xl">
                {{ $title }}
            </h5>
            <div class="text-sm md:text-base lg:text-lg">
                {{ $slot }}
            </div>
        </div>
    </div>
</x-core.cards.card-with-hover>
