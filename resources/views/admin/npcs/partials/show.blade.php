<x-core.cards.card-with-title title="Details" css="mb-5">
    <dl>
        <dt>Type</dt>
        <dd>{{ $npc->type()->getNamedValue() }}</dd>
        <dt>Plane</dt>
        <dd>{{ $npc->gameMap->name }}</dd>
        <dt>Coordinates (X/Y)</dt>
        <dd>{{ $npc->x_position }}/{{ $npc->y_position }}</dd>
        <dt>How to message</dt>
        <dd><code>/m {{ $npc->name }}: any message here</code></dd>
    </dl>
</x-core.cards.card-with-title>
