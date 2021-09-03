<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <dl>
                    <dt>str</dt>
                    <dd>{{number_format($monster->str)}}</dd>
                    <dt>dex</dt>
                    <dd>{{number_format($monster->dex)}}</dd>
                    <dt>dur</dt>
                    <dd>{{number_format($monster->dur)}}</dd>
                    <dt>chr</dt>
                    <dd>{{number_format($monster->chr)}}</dd>
                    <dt>int</dt>
                    <dd>{{number_format($monster->int)}}</dd>
                    <dt>agi</dt>
                    <dd>{{number_format($monster->int)}}</dd>
                    <dt>focus</dt>
                    <dd>{{number_format($monster->int)}}</dd>
                    <dt>Damage Stat</dt>
                    <dd>{{$monster->damage_stat}}</dd>
                </dl>
            </div>
            <div class="col-md-4">
                <dl>
                    <dt>Health Range</dt>
                    <dd>{{$monster->health_range}}</dd>
                    <dt>Attack Range</dt>
                    <dd>{{$monster->attack_range}}</dd>
                    <dt>Drop Check</dt>
                    <dd>{{$monster->drop_check * 100}}%</dd>
                    <dt>AC</dt>
                    <dd>{{number_format($monster->ac)}}</dd>
                    <dt>XP</dt>
                    <dd>{{$monster->xp}}</dd>
                    <dt>Max Level<sup>*</sup></dt>
                    <dd>{{$monster->max_level}}</dd>
                    <dt>Gold Reward</dt>
                    <dd>{{number_format($monster->gold)}}</dd>
                </dl>
            </div>
            <div class="col-md-4">
                <dl>
                    <dt>Spell Evasion</dt>
                    <dd>{{$monster->spell_evasion * 100}}%</dd>
                    <dt>Artifact Annulment</dt>
                    <dd>{{$monster->artifact_annulment * 100}}%</dd>
                </dl>
            </div>
            <p class="ml-3 mt-3">
                <span class="text-muted" style="font-size: 12px;"><sup>*</sup> Once a character is at this level or above it, they get 1/3rd the xp</span>
            </p>
        </div>
        @if ($monster->skills->isNotEmpty())
            <hr />
            <h4>Skills</h4>
            <div class="row">
                @php
                    $colSize = 12 / $monster->skills->count();
                @endphp
                @foreach($monster->skills as $skill)
                    <div class="col-xs-12 col-sm-{{$colSize}}">
                        <dl>
                            <dt>Name</dt>
                            <dd>{{$skill->name}}</dd>
                            <dt>Level</dt>
                            <dd>{{$skill->level}}</dd>
                            <dt>Bonus</dt>
                            <dd>{{$skill->skill_bonus * 100}}%</dd>
                        </dl>
                    </div>
                @endforeach
            </div>
        @endif
        @guest
        @elseif (auth()->user()->hasRole('Admin') && $canEdit)
            <a href="{{route('monster.edit', [
                'monster' => $monster->id,
            ])}}" class="btn btn-primary mt-2">Edit Monster</a>
        @endguest
    </div>
</div>

@if (!is_null($monster->quest_item_id))
    <hr />
    <div class="row page-titles">
        <div class="col-md-6 align-self-right">
            <h4 class="mt-2">{{$monster->questitem->affix_name}} (Quest Item)</h4>
            <span style="font-size: 12px;">
                <strong>Drop Chance:</strong> {{$monster->quest_item_drop_chance * 100}}%
                @if(!is_null($quest))
                    , used in: <a href="{{route('information.quests.quest', ['quest' =>$quest->id])}}">{{$quest->name}}</a>
                @endif
            </span>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            @include('game.items.partials.item-details', ['item' => $monster->questItem])
        </div>
    </div>
@endif
