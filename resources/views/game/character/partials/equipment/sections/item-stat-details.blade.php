<dl class="mt-2">
    <dt>Attack <sup>*</sup>:</dt>
    <dd><span class={{$item->getTotalDamage() > 0 ? 'text-success' : ''}}>{{$item->getTotalDamage()}} </span></dd>
    <dt>AC:</dt>
    <dd><span class={{$item->getTotalDefence() > 0 ? 'text-success' : ''}}>{{$item->getTotalDefence()}} </span></dd>
    <dt>Healing:</dt>
    <dd><span class={{$item->getTotalHealing() > 0 ? 'text-success' : ''}}>{{$item->getTotalHealing()}} </span></dd>
    <dt>Base Attack Mod:</dt>
    <dd><span class='{{$item->base_damage_mod > 0.0 ? 'text-success' : ''}}'>{{$item->base_damage_mod * 100}}% </span></dd>
    <dt>Fight Timeout Mod <sup>**</sup>:</dt>
    <dd><span class='{{$item->getTotalFightTimeOutMod() > 0.0 ? 'text-success' : ''}}'>{{$item->getTotalFightTimeOutMod() * 100}}% </span></dd>
    <dt>Base Damage Mod <sup>**</sup>:</dt>
    <dd><span class='{{$item->getTotalBaseDamageMod() > 0.0 ? 'text-success' : ''}}'>{{$item->getTotalBaseDamageMod() * 100}}% </span></dd>
    <dt>Spell Evasion Modifier:</dt>
    <dd class={{$item->spell_evasion > 0.0 ? 'text-success' : ''}}>{{$item->spell_evasion * 100}}%</dd>
    <dt>Artifact Annulment Modifier:</dt>
    <dd class={{$item->artifact_annulment > 0.0 ? 'text-success' : ''}}>{{$item->artifact_annulment * 100}}%</dd>
    @if ($item->can_resurrect)
        <dt>Resurrection Chance <sup>rc</sup>:</dt>
        <dd class={{$item->resurrection_chance > 0.0 ? 'text-success' : ''}}>{{$item->resurrection_chance * 100}}%</dd>
    @endif
    <dt>Str:</dt>
    <dd><span class={{$item->getTotalPercentageForStat('str') > 0.0 ? 'text-success' : ''}}>{{$item->getTotalPercentageForStat('str') * 100}}% </span></dd>
    <dt>Dur:</dt>
    <dd><span class={{$item->getTotalPercentageForStat('dur') > 0.0 ? 'text-success' : ''}}>{{$item->getTotalPercentageForStat('dur') * 100}}% </span></dd>
    <dt>Dex:</dt>
    <dd><span class={{$item->getTotalPercentageForStat('dex') > 0.0 ? 'text-success' : ''}}>{{$item->getTotalPercentageForStat('dex') * 100}}% </span></dd>
    <dt>Chr:</dt>
    <dd><span class={{$item->getTotalPercentageForStat('chr') > 0.0 ? 'text-success' : ''}}>{{$item->getTotalPercentageForStat('chr') * 100}}% </span></dd>
    <dt>Int:</dt>
    <dd><span class={{$item->getTotalPercentageForStat('int') > 0.0 ? 'text-success' : ''}}>{{$item->getTotalPercentageForStat('int') * 100}}% </span></dd>
    <dt>Agi:</dt>
    <dd><span class={{$item->getTotalPercentageForStat('agi') > 0.0 ? 'text-success' : ''}}>{{$item->getTotalPercentageForStat('agi') * 100}}% </span></dd>
    <dt>Focus:</dt>
    <dd><span class={{$item->getTotalPercentageForStat('focus') > 0.0 ? 'text-success' : ''}}>{{$item->getTotalPercentageForStat('focus') * 100}}% </span></dd>
</dl>
