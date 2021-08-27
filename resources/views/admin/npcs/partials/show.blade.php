<x-cards.card-with-title title="Details">
    <dl>
        <dt>Type</dt>
        <dd>{{$npc->type}}</dd>
        <dt>Plane</dt>
        <dd>{{$npc->game_map_name}}</dd>
        <dt>Coordinates (X/Y)</dt>
        <dd>{{$npc->x_position}}/{{$npc->y_position}} {{$npc->gameMap->name}}</dd>
        <dt>Has to be at same location to interact?</dt>
        <dd>{{$npc->must_be_at_same_location ? 'Yes' : 'No'}}</dd>
        <dt>Moves around the map? (once per hour)</dt>
        <dd>{{$npc->moves_around_map ? 'Yes' : 'No'}}</dd>
        <dt>How to message</dt>
        <dd>{{$npc->text_command_to_message}} {{$npc->commands->first()->command}}</dd>
    </dl>
</x-cards.card-with-title>

@if($npc->commands->isNotEmpty())

    <x-cards.card-with-title title="Available Commands">
        <p class="mb-2">These are the available commands you can message to the NPC. They're type correlates to the action they will
            take when you message them.</p>
        <div class="alert alert-info mb-3">
            When messaging a NPC their command you would type:
            <pre class="mt-2">{{$npc->text_command_to_message}} {{$npc->commands->first()->command}}</pre>
            <p class="mt-3"><strong>Commands must be typed exactly, or they will not work. Copy and paste is your friend.</strong></p>
        </div>


        <dl>
            @php $count = 0; @endphp
            @foreach($npc->commands as $index => $command)
                <dt>Command</dt>
                <dd>{{$command->command}}</dd>
                <dt>Command Type</dt>
                <dd>{{NpcCommandType::statusType($command->command_type)}}</dd>

                @if ($count !== $index)
                    <hr />
                    @php $count++; @endphp
                @endif
            @endforeach
        </dl>

        @auth
            @if(auth()->user()->hasRole('Admin'))
                <button class="btn btn-primary mt-3">Manage Commands</button>
            @endif
        @endauth
    </x-cards.card-with-title>

    <h4>Quests This NPC Offers</h4>
    <hr />
    @livewire('admin.quests.data-table', [
        'forNpc' => $npc->id,
    ])

@endif
