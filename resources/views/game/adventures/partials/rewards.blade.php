@if ($topLevelRewards)
  @php $rewards = AdventureRewards::combineRewards($adventureLog->rewards, $character); @endphp
@else
  @php $rewards = $adventureLog->rewards[$level][$monsterName] @endphp
@endif
<dl>
  <dt>Total XP:</dt>
  <dd>{{number_format($rewards['exp'])}}</dd>
  <dt>Total Gold:</dt>
  <dd>{{number_format($rewards['gold'])}}</dd>
  @if (isset($rewards['skill']))
    <dt>Skill (Currently Training):</dt>
    <dd>{{$rewards['skill']['skill_name']}}</dd>
    <dt>Skill Total XP:</dt>
    <dd>{{number_format($rewards['skill']['exp'])}}</dd>
    <dt>Skill XP Towards:</dt>
    <dd>{{$rewards['skill']['exp_towards'] * 100}}%</dd>
  @endif
</dl>
