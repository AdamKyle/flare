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
</dl>