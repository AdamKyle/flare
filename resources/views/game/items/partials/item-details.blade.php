<dl>
    <dt>Base Damage:</dt>
    <dd class="{{$item->getTotalDamage() > 0 ? 'text-success' : ''}}">{{$item->getTotalDamage()}} <em>(With all modifiers)</em></dd>
    <dt>Base AC:</dt>
    <dd class="{{$item->getTotalDefence() > 0 ? 'text-success' : ''}}">{{$item->getTotalDefence()}} <em>(With all modifiers)</em></dd>
    <dt>Base Healing:</dt>
    <dd class="{{$item->getTotalHealing() > 0 ? 'text-success' : ''}}">{{$item->getTotalHealing()}} <em>(With all modifiers)</em></dd>
    <dt>Type:</dt>
    <dd>{{$item->type}}</dd>

    @if (!is_null($item->effect))
        <dt>Effect:</dt>
        <dd>
            @switch($item->effect)
                @case('walk-on-water')
                    Walk On Water
                    @break
                @case('walk-on-magma')
                  Walk On Magma in Hell
                  @break
                @case('labyrinth')
                    Access Labyrinth Plane
                    @break
                @case('dungeon')
                    Access Dungeons Plane
                @case('shadow-plane')
                    Access Shadow Plane
                    @break
                @case('hell')
                    Access to Hell
                    @break
                @case('purgatory')
                    Access to Purgatory
                    @break
                @case('walk-on-death-water')
                    Walk on Death Water in Dungeons
                    @break
                @case('teleport-to-celestial')
                    Teleport to the Celestial Entity on which ever plane they are located on. You must have access to that plane.
                    @break
                @case('affixes-irresistible')
                    Enemies cannot resist your affixes (unless they void you first)
                    @break
                @case('continue-leveling')
                    Lets you continue leveling past level 1000 up to the new Level cap which is increased by 100 levels per month.
                    @break
                @case('gold-dust-rush')
                    Gives you a 25% chance to increase your (current total) gold dust by your disenchanting skill bonus. The more gold dust, the more of a rush you get.
                    @break
                @case('mass-embezzle')
                    Lets you mass embezzle, while standing in a kingdom you own.
                    @break
                @case('speak-to-queen-of-hearts')
                    Let's you, while standing at her location in hell, speak with the Queen of Hearts.
                    @break
                @default
                    N/A
            @endswitch
        </dd>
    @endif
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
        <dt>Gold Cost:</dt>
        <dd>{{number_format($item->cost)}}</dd>
        <dt>Gold Dust Cost:</dt>
        <dd>{{!is_null($item->gold_dust_cost) ? number_format($item->gold_dust_cost) : 0}}</dd>
        <dt>Shards Cost:</dt>
        <dd>{{!is_null($item->shards_cost) ? $item->shards_cost : 0}}</dd>
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

