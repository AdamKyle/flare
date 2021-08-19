<dl>
    <dt>Max Health:</dt>

    <dd>{{$characterInfo['maxHealth']}}</dd>
    <dt>Max Attack:</dt>
    <dd>{{number_format($character->getInformation()->buildTotalAttack())}}</dd>
    <dt>Max Heal For:</dt>
    <dd>{{$characterInfo['maxHeal']}}</dd>
    <dt>Max AC:</dt>
    <dd>{{$characterInfo['maxAC']}}</dd>
</dl>
