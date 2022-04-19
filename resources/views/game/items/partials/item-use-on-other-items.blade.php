<x-cards.card-with-title title="Usable Item">
    <div class="alert alert-info mb-3 mt-2">
        <p>
            This item can be used on other items. That means, by visiting the Purgatory Smiths house, in Purgatory,
            you can use these items on other items (with or without affixes) to add stacks of Holy to them.
        </p>
        <p>
            These stacks come in 5 level types with the first level offering the least and the last offering the most. These items can
            add small % boost to your items as well as devoidance % boosts. These boosts are rand between a set range for each level.
        </p>
        <p>
            Finally, these items can also increase what's known as Devoidance and Voidance Resistance, this allows you to resist an enemy's attempt to
            void or devoid you. The more stacks of holy you have, the more resistance you can build up.
        </p>
    </div>

    <dl>
        <dt>Holy Level</dt>
        <dd>{{$item->holy_level}}</dd>
    </dl>
</x-cards.card-with-title>
