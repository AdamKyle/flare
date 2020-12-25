<div class="card">
    <div class="card-body">
        <p>{{$adventure->description}}</p>
        <hr />
        <dl>
            <dt>Levels</dt>
            <dd>{{$adventure->levels}}</dd>
            <dt>Time Per Level (Minutes)</dt>
            <dd>{{$adventure->time_per_level}}</dd>
            <dt>Item Find Chance</dt>
            <dd>{{$adventure->item_find_chance * 100}}%</dd>
            <dt>Gold Rush Chance</dt>
            <dd>{{$adventure->gold_rush_chance * 100}}%</dd>
            <dt>Skill Bonus EXP</dt>
            <dd>{{$adventure->skill_exp_bonus * 100}}%</dd>
        </dl>
        <hr />
        @guest
        @else
            @if (auth()->user()->hasRole('Admin'))
                <a href="{{route('adventure.edit', [
                    'adventure' => $adventure->id,
                ])}}" class="btn btn-primary mt-2">Edit Adventure</a>

                @if (!$adventure->published)
                    <x-forms.button-with-form
                        form-route="{{route('adventure.publish', ['adventure' => $adventure])}}"
                        form-id="publish-adventure-{{$adventure->id}}"
                        button-title="Publish"
                        class="btn btn-success mt-2"
                    />
                @endif
            @endif
        @endguest
    </div>
</div>
<h4>Found At:</h4>
@livewire('admin.locations.data-table', [
    'adventureId' => $adventure->id,
])
<h4>With Monsters:</h4>
<p class="text-muted mb-2" style="font-size: 12px;"><em>Monsters are selected at random for each adventure level.</em></p>
@livewire('admin.monsters.data-table', [
    'adventureId' => $adventure->id
])
@if (!is_null($adventure->itemReward))
    <h4>Rewards: {{$adventure->itemReward->name}}</h4>
    <em class="text-muted" style="font-size: 12px;">All quest items are rewarded once for completing the adventure the first time only.</em>
    <div class="card mt-2">
        <div class="card-body">
            <div class="mt-2">
                @if (!is_null($adventure->itemReward))
                    @include('game.items.partials.item-details', ['item' => $adventure->itemReward])
                    @include('game.core.partials.equip.details.item-stat-details', ['item' => $adventure->itemReward])
                @else
                    @guest
                    @else
                        @if (auth()->user->hasRole('Admin'))
                            <div class="alert alert-info"> This adventure has no quest item rewards. <a href="{{route('adventure.edit', [
                                'adventure' => $adventure->id,
                            ])}}">Assign one.</a> </div>
                        @endif
                    @endif
                @endif
            </div>
        </div>
    </div>
@endif