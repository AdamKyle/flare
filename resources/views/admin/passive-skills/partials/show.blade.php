<x-core.cards.card>
  <p class="my-5">{!! nl2br(e($skill->description)) !!}</p>
  <dl>
    <dt>Max Level:</dt>
    <dd>{{$skill->max_level}}</dd>
    <dt>Hours Per Level <sup>*</sup>:</dt>
    <dd>{{$skill->hours_per_level}}</dd>
    <dt>Bonus Per Level:</dt>
    <dd>{{$skill->bonus_per_level * 100}}%</dd>
    <dt>Bonus Resources Per Level:</dt>
    <dd>{{number_format($skill->resource_bonus_per_level)}}</dd>
    <dt>Effects:</dt>
    <dd>{{$skill->passiveType()->getNamedValue()}}</dd>
    @if (!is_null($skill->parent_skill_id))
      <dt>Parent Skill:</dt>
      <dd>{{$skill->parent->name}}</dd>
      <dt>Unlocks when parent is level:</dt>
      <dd>{{$skill->unlocks_at_level}}</dd>
      <dt>Is Locked?</dt>
      <dd>{{$skill->is_locked ? 'Yes' : 'No'}}</dd>
    @endif
  </dl>
  <p class="my-5"><sup>*</sup> There is a formula to figure out the new time, Let's assume the skill at level 0, takes 2 hours.
    At level 1, the new time requirement to get to level 2 will be <code>1 + 1 * 2 = 4 hours</code>. Let me break that down: <code>New Skill Level (1) + 1 * Total Hours at level 0 (2) = 4 Hours</code>.
  </p>
</x-core.cards.card>
<h2 class="font-light my-5">Child Skills</h2>
<p class="my-5">
  These skills will unlock at specific levels of this skill.
</p>

{{--@livewire('admin.passive-skills.passive-skill-table', [--}}
{{--    'skillId' => $skill->id--}}
{{--])--}}
