<dl class="mt-2">
    <dt>Attack:</dt>
    <dd><span class='text-success'>{{$item->getTotalDamage()}}</span></dd>
    <dt>AC:</dt>
    <dd><span class='text-success'>{{$item->getTotalDefence()}}</span></dd>
    <dt>Healing:</dt>
    <dd><span class='text-success'>{{$item->getTotalHealing()}}</span></dd>
    <dt>Damage Modifier:</dt>
    <dd class="text-success">{{$item->base_damage_mod * 100}}%</dd>
    <dt>AC Modifier:</dt>
    <dd class="text-success">{{$item->base_ac_mod * 100}}%</dd>
    <dt>Healing Modifier:</dt>
    <dd class="text-success">{{$item->base_healing_mod * 100}}%</dd>
    <dt>Str Modifier:</dt>
    <dd><span class='text-success'>{{$item->getTotalPercentageForStat('str') * 100}}% </span></dd>
    <dt>Dur Modifier:</dt>
    <dd><span class='text-success'>{{$item->getTotalPercentageForStat('dur') * 100}}% </span></dd>
    <dt>Dex Modifier:</dt>
    <dd><span class='text-success'>{{$item->getTotalPercentageForStat('dex') * 100}}% </span></dd>
    <dt>Chr Modifier:</dt>
    <dd><span class='text-success'>{{$item->getTotalPercentageForStat('chr') * 100}}% </span></dd>
    <dt>Int Modifier:</dt>
    <dd><span class='text-success'>{{$item->getTotalPercentageForStat('int') * 100}}% </span></dd>
</dl>
