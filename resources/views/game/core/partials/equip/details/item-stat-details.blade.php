<dl class="mt-2">
    <dt>Attack <sup>*</sup></sup>:</dt>
    <dd><span class={{$item->getTotalDamage() > 0 ? 'text-success' : ''}}>{{$item->getTotalDamage()}}</span></dd>
    <dt>AC:</dt>
    <dd><span class={{$item->getTotalDefence() > 0 ? 'text-success' : ''}}>{{$item->getTotalDefence()}}</span></dd>
    <dt>Healing:</dt>
    <dd><span class={{$item->getTotalHealing() > 0 ? 'text-success' : ''}}>{{$item->getTotalHealing()}}</span></dd>
    <dt>Base Attack Modifier:</dt>
    <dd class={{$item->base_damage_mod > 0.0 ? 'text-success' : ''}}>{{$item->base_damage_mod * 100}}%</dd>
    <dt>Fight Timeout Modifier <sup>**</sup>:</dt>
    <dd class={{$item->getTotalFightTimeOutMod() > 0.0 ? 'text-success' : ''}}>{{$item->getTotalFightTimeOutMod() * 100}}%</dd>
    <dt>Base Damage Modifier<sup>**</sup>:</dt>
    <dd class={{$item->getTotalBaseDamageMod() > 0.0 ? 'text-success' : ''}}>{{$item->getTotalBaseDamageMod() * 100}}%</dd>
    <dt>AC Modifier:</dt>
    <dd class={{$item->base_ac_mod > 0.0 ? 'text-success' : ''}}>{{$item->base_ac_mod * 100}}%</dd>
    <dt>Spell Evasion Modifier:</dt>
    <dd class={{$item->spell_evasion > 0.0 ? 'text-success' : ''}}>{{$item->spell_evasion * 100}}%</dd>
    <dt>Artifact Annulment Modifier:</dt>
    <dd class={{$item->artifact_annulment > 0.0 ? 'text-success' : ''}}>{{$item->artifact_annulment * 100}}%</dd>
    @if ($item->can_resurrect)
        <dt>Resurrection Chance <sup>rc</sup>:</dt>
        <dd class={{$item->resurrection_chance > 0.0 ? 'text-success' : ''}}>{{$item->resurrection_chance * 100}}%</dd>
    @endif
    <dt>Str Modifier:</dt>
    <dd><span class={{$item->getTotalPercentageForStat('str') > 0.0 ? 'text-success' : ''}}>{{number_format($item->getTotalPercentageForStat('str') * 100)}}% </span></dd>
    <dt>Dur Modifier:</dt>
    <dd><span class={{$item->getTotalPercentageForStat('dur') > 0.0 ? 'text-success' : ''}}>{{number_format($item->getTotalPercentageForStat('dur') * 100)}}% </span></dd>
    <dt>Dex Modifier:</dt>
    <dd><span class={{$item->getTotalPercentageForStat('dex') > 0.0 ? 'text-success' : ''}}>{{number_format($item->getTotalPercentageForStat('dex') * 100)}}% </span></dd>
    <dt>Chr Modifier:</dt>
    <dd><span class={{$item->getTotalPercentageForStat('chr') > 0.0 ? 'text-success' : ''}}>{{number_format($item->getTotalPercentageForStat('chr') * 100)}}% </span></dd>
    <dt>Int Modifier:</dt>
    <dd><span class={{$item->getTotalPercentageForStat('int') > 0.0 ? 'text-success' : ''}}>{{number_format($item->getTotalPercentageForStat('int') * 100)}}% </span></dd>
    <dt>Agi Modifier:</dt>
    <dd><span class={{$item->getTotalPercentageForStat('agi') > 0.0 ? 'text-success' : ''}}>{{number_format($item->getTotalPercentageForStat('agi') * 100)}}% </span></dd>
    <dt>Focus Modifier:</dt>
    <dd><span class={{$item->getTotalPercentageForStat('focus') > 0.0 ? 'text-success' : ''}}>{{number_format($item->getTotalPercentageForStat('focus') * 100)}}% </span></dd>
</dl>

<p class="mt-3 mb-3">
    <sup>*</sup> Attack includes Base Attack Modifier applied automatically, rounded to the nearest whole number.
</p>
<p>
    <sup>**</sup> Applies to all skills that increase this modifier.
</p>
@if ($item->can_resurrect)
    <p>
        <sup>rc</sup> Used to determine, upon death in either battle or adventure, if your character can automatically resurrect and heal.
    </p>
@endif

