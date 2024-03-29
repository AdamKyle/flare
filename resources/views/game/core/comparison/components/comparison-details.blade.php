@if (count($details) > 0 && count($details) === 2)
    <div class="grid md:grid-cols-2 gap-2">
        <div>
            @include('game.core.comparison.components.single-comparison', ['details' => $details[0]])
        </div>
        <div class='block md:hidden border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
        <div>
            @include('game.core.comparison.components.single-comparison', ['details' => $details[1]])
        </div>
    </div>
@elseif (count($details) > 0 && count($details) === 1)
    <div class="mt-auto">
        @include('game.core.comparison.components.single-comparison', ['details' => $details[0]])
    </div>
@else
    <div class="mt-auto text-center">
        Nothing to compare. Anything is better than nothing.
    </div>
@endif
