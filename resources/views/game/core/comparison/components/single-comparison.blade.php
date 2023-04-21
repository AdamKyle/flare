@php
    $invalidFields = ['name', 'position', 'is_unique', 'affix_count', 'holy_stacks_applied'];
    $nonFloat      = ['damage_adjustment', 'damage', 'ac_adjustment', 'base_ac_adjustment', 'healing_adjustment'];
@endphp

<dl>
    <dt>Currently Equipped</dt>
    <dd>{{$details['name']}}</dd>
    <dt>In Position</dt>
    <dd>{{$details['position']}}</dd>
</dl>

<div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>

<dl>
    @foreach($details as $key => $value)
        @if (!in_array($key, $invalidFields))
            @if (is_array($value))
                @foreach ($value as $attachedSkill)
                    <dt>Affects Skill:</dt>
                    <dd>{{$attachedSkill['skill_name']}}</dd>
                    <dt>Skill XP Bonus:</dt>
                    <dd class="{{$attachedSkill['skill_training_bonus'] > 0 ? 'text-green-700 dark:text-green-600' : 'text-red-700 dark:text-red-600'}}">{{$attachedSkill['skill_training_bonus'] * 100}}%</dd>
                    <dt>Skill Bonus:</dt>
                    <dd class="{{$attachedSkill['skill_bonus'] > 0 ? 'text-green-700 dark:text-green-600' : 'text-red-700 dark:text-red-600'}}">{{$attachedSkill['skill_bonus'] * 100}}%</dd>
                @endforeach
            @elseif ($value <=> 0)
                <dt>{{ucFirst(str_replace('_', ' ', $key))}}</dt>
                @if (in_array($key, $nonFloat))
                    <dd class="{{$value > 0 ? 'text-green-700 dark:text-green-600' : 'text-red-700 dark:text-red-600'}}">{{number_format($value)}}</dd>
                @else
                    <dd class="{{$value > 0 ? 'text-green-700 dark:text-green-600' : 'text-red-700 dark:text-red-600'}}">{{$value * 100}} %</dd>
                @endif
            @endif
        @endif
    @endforeach
</dl>
