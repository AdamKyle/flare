
@if (!is_null($details['slot']->item->itemPrefix) || !is_null($details['slot']->item->itemSuffix))
    <div class="container">
        <h4 class="text-center">Attached Affixes</h4>

        <div class="row">
            <div class={{!is_null($details['slot']->item->itemSuffix) ? 'col-md-6' : 'col-md-12'}}>
                @if (!is_null($details['slot']->item->itemPrefix))
                    <hr />
                    @include('game.items.partials.item-prefix', ['item' => $details['slot']->item])
                @endif
            </div>
            <div class={{!is_null($details['slot']->item->itemPrefix) ? 'col-md-6' : 'col-md-12'}}>
                @if (!is_null($details['slot']->item->itemSuffix))
                    <hr />
                    @include('game.items.partials.item-suffix', ['item' => $details['slot']->item])
                @endif
            </div>
        </div>
    </div>
    <hr />
@endif

@if (!empty($details['slot']->item->getItemSkills()))

    <h4 class="mt-3">Affects the Following Skills:</h4>
    <hr />
    <div class="row mt-3">
        @php
            $col = (12 / count($details['slot']->item->getItemSkills()));
        @endphp

        @foreach($details['slot']->item->getItemSkills() as $skill)
            <div class="col-md-{{$col}}">
                <dl>
                    <dt>Skill Name:</dt>
                    <dd>{{$skill['skill_name']}}</dd>
                    <dt>Skill XP Bonus (When Training):</dt>
                    <dd class="{{$skill['skill_training_bonus'] > 0.0 ? 'text-success' : ''}}">{{$skill['skill_training_bonus'] * 100}}%</dd>
                    <dt>Skill Bonus (When using)</dt>
                    <dd class="{{$skill['skill_bonus'] > 0.0 ? 'text-success' : ''}}">{{$skill['skill_bonus'] * 100}}%</dd>
                </dl>
            </div>
        @endforeach
    </div>
    <hr />
@endif
<h4 class="mt-3">Position</h4>
<hr />
<dl>
    <dt>Position:</dt>
    <dd>{{title_case(str_replace('-', ' ', $details['slot']->position))}}</dd>
</dl>
<hr />
