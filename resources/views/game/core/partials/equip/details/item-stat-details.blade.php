<dl class="mt-2">
    <dt>Attack:</dt>
    <dd><span class='text-success'>{{$item->getTotalDamage()}}</span></dd>
    <dt>AC:</dt>
    <dd><span class='text-success'>{{$item->getTotalDefence()}}</span></dd>
    <dt>Healing:</dt>
    <dd><span class='text-success'>{{$item->getTotalHealing()}}</span></dd>
    <dt>Str:</dt>
    <dd><span class='text-success'>{{$item->getTotalPercentageForStat('str') * 100}}%</span></dd>
    <dt>Dur:</dt>
    <dd><span class='text-success'>{{$item->getTotalPercentageForStat('dur') * 100}}%</span></dd>
    <dt>Dex:</dt>
    <dd><span class='text-success'>{{$item->getTotalPercentageForStat('dex') * 100}}%</span></dd>
    <dt>Chr:</dt>
    <dd><span class='text-success'>{{$item->getTotalPercentageForStat('chr') * 100}}%</span></dd>
    <dt>Int:</dt>
    <dd><span class='text-success'>{{$item->getTotalPercentageForStat('int') * 100}}%</span></dd>
</dl>