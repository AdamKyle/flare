<dl>
    <dt>Base Damage:</dt>
    <dd>{{$item->getTotalDamage()}} <em>(With all modifiers)</em></dd>
    <dt>Base AC:</dt>
    <dd>{{$item->getTotalDefence()}} <em>(With all modifiers)</em></dd>
    <dt>Base Healing:</dt>
    <dd>{{$item->getTotalHealing()}} <em>(With all modifiers)</em></dd>
    <dt>Type:</dt>
    <dd>{{$item->type}}</dd>

    @if (!is_null($item->effect))
        <dt>Effect:</dt>
        <dd>
            @switch($item->effect)
                @case('walk-on-water')
                    Walk On Water
                    @break
                @case('labyrinth')
                    Access Labyrinth Plane
                    @break
                @default
                    N/A
            @endswitch
        </dd>
    @endif

    <dt>Skill Name:</dt>
    <dd>{{is_null($item->skill_name) ? 'N/A' : $item->skill_name}}</dd>
    <dt>Skill XP Bonus (When Training):</dt>
    <dd>{{is_null($item->skill_name) ? 0 : $item->skill_training_bonus * 100}}%</dd>
    <dt>Skill Bonus (When using)</dt>
    <dd>{{is_null($item->skill_training_bonus) ? 0 : $item->skill_bonus * 100}}%</dd>
</dl>

@if ($item->can_craft)
<h4 class="mt-3">Crafting Info</h4>
<hr />
<dl>
    <dt>Crafting Type</dt>
    <dd>{{$item->crafting_type}}</dd>
    <dt>Skill Level Required</dt>
    <dd>{{$item->skill_level_required}}</dd>
    <dt>Skill Level Trivial</dt>
    <dd>{{$item->skill_level_trivial}}</dd>
</dl>
@endif

@if (!empty($item->getItemSkills()))
    <h4 class="mt-3">Affects the Following Skills:</h4>
    <hr />
    <div class="row mt-3">
        @php
            $col = (12 / count($item->getItemSkills()));
        @endphp

        @foreach($item->getItemSkills() as $skill)
            <div class="col-md-{{$col}}">
                <dl>
                    <dt>Skill Name:</dt>
                    <dd>{{$skill['skill_name']}}</dd>
                    <dt>Skill XP Bonus (When Training):</dt>
                    <dd>{{$skill['skill_training_bonus'] * 100}}%</dd>
                    <dt>Skill Bonus (When using)</dt>
                    <dd>{{$skill['skill_bonus'] * 100}}%</dd>
                </dl>
            </div>
        @endforeach
    </div>
@endif
