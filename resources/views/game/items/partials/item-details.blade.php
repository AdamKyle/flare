<dl>
    <dt>Base Damage:</dt>
    <dd>{{$item->getTotalDamage()}} <em>(With all modifiers)</em></dd>
    <dt>Base AC:</dt>
    <dd>{{$item->getTotalDefence()}} <em>(With all modifiers)</em></dd>
    <dt>Type:</dt>
    <dd>{{$item->type}}</dd>
    <dt>Effect:</dt>
    <dd>
        @switch($item->effect)
            @case('walk-on-water')
                Walk On Water
                @break
            @default
                N/A
        @endswitch
    </dd>

    @if ($item->type === 'quest')
        <dt>Affects Skill Name:</dt>
        <dd>{{is_null($item->skill_name) ? 'N/A' : $item->skill_name}}</dd>
        <dt>Bonus (XP) When Training:</dt>
        <dd>{{is_null($item->skill_training_bonus) ? 'N/A' : $item->skill_training_bonus * 100}}%</dd>
    @endif
</dl>