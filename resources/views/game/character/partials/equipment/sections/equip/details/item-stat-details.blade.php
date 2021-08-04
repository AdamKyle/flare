<dl class="mt-2">
    <dt>Attack:</dt>
    <dd><span class='{{$item->getTotalDamage() > 0 ? 'text-success' : ''}}'>+{{$item->getTotalDamage()}} </span></dd>
    <dt>AC:</dt>
    <dd><span class='{{$item->getTotalDefence() > 0 ? 'text-success' : ''}}'>+{{$item->getTotalDefence()}} </span></dd>
    <dt>Healing:</dt>
    <dd><span class='{{$item->getTotalHealing() > 0 ? 'text-success' : ''}}'>+{{$item->getTotalHealing()}} </span></dd>
    <dt>Str Modifier:</dt>
    <dd><span class='{{$item->getTotalPercentageForStat('str') > 0 ? 'text-success' : ''}}'>+{{$item->getTotalPercentageForStat('str') * 100}}% </span></dd>
    <dt>Dur Modifier:</dt>
    <dd><span class='{{$item->getTotalPercentageForStat('dur') > 0 ? 'text-success' : ''}}'>+{{$item->getTotalPercentageForStat('dur') * 100}}% </span></dd>
    <dt>Dex Modifier:</dt>
    <dd><span class='{{$item->getTotalPercentageForStat('dex') > 0 ? 'text-success' : ''}}'>+{{$item->getTotalPercentageForStat('dex') * 100}}% </span></dd>
    <dt>Chr Modifier:</dt>
    <dd><span class='{{$item->getTotalPercentageForStat('chr') > 0 ? 'text-success' : ''}}'>+{{$item->getTotalPercentageForStat('chr') * 100}}% </span></dd>
    <dt>Int Modifier:</dt>
    <dd><span class='{{$item->getTotalPercentageForStat('int') > 0 ? 'text-success' : ''}}'>+{{$item->getTotalPercentageForStat('int') * 100}}% </span></dd>
    <dt>Agi Modifier:</dt>
    <dd><span class='{{$item->getTotalPercentageForStat('agi') > 0 ? 'text-success' : ''}}'>+{{$item->getTotalPercentageForStat('agi') * 100}}% </span></dd>
    <dt>Focus Modifier:</dt>
    <dd><span class='{{$item->getTotalPercentageForStat('focus') > 0 ? 'text-success' : ''}}'>+{{$item->getTotalPercentageForStat('focus') * 100}}% </span></dd>
</dl>
