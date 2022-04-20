<div class="row page-titles">
    <div class="col-md-6 align-self-right">
        <h4 class="mt-2">{{$monster->name}} ({{$monster->gameMap->name}})</h4>
    </div>
    <div class="col-md-6 align-self-right">
        <a href="{{url()->previous()}}" class="btn btn-primary float-right ml-2">Back</a>
    </div>
</div>

@include('admin.monsters.partials.details', [
    'monster' => $monster,
    'quest'   => $quest,
    'canEdit' => true,
])

@if (!is_null($monster->quest_item_id))
    <hr />
    <div class="row page-titles">
        <div class="col-md-6 align-self-right">
            <h4 class="mt-2">{{$monster->questItem->affix_name}} (Quest Item)</h4>
            <span style="font-size: 12px;">
                <strong>Drop Chance:</strong> {{$monster->quest_item_drop_chance * 100}}%
                @if(!is_null($quest))
                    , used in: <a href="{{route('info.page.quest', ['quest' =>$quest->id])}}">{{$quest->name}}</a>
                @endif
            </span>
        </div>
    </div>
    <x-core.cards.card>
        @include('game.items.partials.item-details', ['item' => $monster->questItem])
    </x-core.cards.card>
@endif
