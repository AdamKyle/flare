@php
    $isAttackMastery = $classSpecial->specialty_damage > 0;
@endphp

<x-core.cards.card-with-title title="{{ $classSpecial->name }}" css="mt-5">
    <div class="-mx-2 mb-4 flex flex-wrap">
        <div class="mb-4 w-full px-2 md:w-1/2">
            @if (! is_null($classSpecial->description))
                <p class="my-2">{{ $classSpecial->description }}</p>
            @endif

            <dl class="my-4">
                <dt>Class</dt>
                <dd>{{ $classSpecial->gameClass->name }}</dd>
                <dt>Type</dt>
                <dd>{{ $isAttackMastery ? 'Attack' : 'Passive' }}</dd>
                <dt>Requires Class Rank Level</dt>
                <dd>{{ $classSpecial->requires_class_rank_level }}</dd>
            </dl>
        </div>

        <div class="mb-4 w-full px-2 md:w-1/2">
            @if ($isAttackMastery)
                <h5 class="mb-2">Attack</h5>
                <dl class="my-4">
                    <dt>Specialty Damage</dt>
                    <dd>{{ $classSpecial->specialty_damage }}</dd>
                    @if (! is_null($classSpecial->increase_specialty_damage_per_level))
                        <dt>Increase Per Level</dt>
                        <dd>{{ $classSpecial->increase_specialty_damage_per_level }}</dd>
                    @endif
                    @if (! is_null($classSpecial->specialty_damage_uses_damage_stat_amount))
                        <dt>Specialty Damage Uses Damage Stat Amount</dt>
                        <dd>{{ $classSpecial->specialty_damage_uses_damage_stat_amount }}</dd>
                    @endif
                    @if (! is_null($classSpecial->attack_type_required))
                        <dt>Attack Type Required</dt>
                        <dd>{{ $classSpecial->attack_type_required }}</dd>
                    @endif
                </dl>
            @endif

            <h5 class="mb-2">Modifiers</h5>
            <dl class="my-4">
                @if (! is_null($classSpecial->base_damage_mod) && $classSpecial->base_damage_mod > 0)
                    <dt>Damage Modifier</dt>
                    <dd>+ {{ $classSpecial->base_damage_mod * 100 }} %</dd>
                @endif
                @if (! is_null($classSpecial->base_ac_mod) && $classSpecial->base_ac_mod > 0)
                    <dt>AC Modifier</dt>
                    <dd>+ {{ $classSpecial->base_ac_mod * 100 }} %</dd>
                @endif
                @if (! is_null($classSpecial->base_healing_mod) && $classSpecial->base_healing_mod > 0)
                    <dt>Healing Modifier</dt>
                    <dd>+ {{ $classSpecial->base_healing_mod * 100 }} %</dd>
                @endif
                @if (! is_null($classSpecial->base_spell_damage_mod) && $classSpecial->base_spell_damage_mod > 0)
                    <dt>Spell Damage Modifier</dt>
                    <dd>+ {{ $classSpecial->base_spell_damage_mod * 100 }} %</dd>
                @endif
                @if (! is_null($classSpecial->health_mod) && $classSpecial->health_mod > 0)
                    <dt>Health Modifier</dt>
                    <dd>+ {{ $classSpecial->health_mod * 100 }} %</dd>
                @endif
                @if (! is_null($classSpecial->base_damage_stat_increase) && $classSpecial->base_damage_stat_increase > 0)
                    <dt>Damage Stat Increase</dt>
                    <dd>+ {{ $classSpecial->base_damage_stat_increase }}</dd>
                @endif
            </dl>

            <h5 class="mb-2">Evasion and Reductions</h5>
            <dl class="my-4">
                @if (! is_null($classSpecial->spell_evasion) && $classSpecial->spell_evasion > 0)
                    <dt>Spell Evasion</dt>
                    <dd>+ {{ $classSpecial->spell_evasion * 100 }} %</dd>
                @endif
                @if (! is_null($classSpecial->affix_damage_reduction) && $classSpecial->affix_damage_reduction > 0)
                    <dt>Affix Damage Reduction</dt>
                    <dd>+ {{ $classSpecial->affix_damage_reduction * 100 }} %</dd>
                @endif
                @if (! is_null($classSpecial->healing_reduction) && $classSpecial->healing_reduction > 0)
                    <dt>Healing Reduction</dt>
                    <dd>+ {{ $classSpecial->healing_reduction * 100 }} %</dd>
                @endif
                @if (! is_null($classSpecial->skill_reduction) && $classSpecial->skill_reduction > 0)
                    <dt>Skill Reduction</dt>
                    <dd>+ {{ $classSpecial->skill_reduction * 100 }} %</dd>
                @endif
                @if (! is_null($classSpecial->resistance_reduction) && $classSpecial->resistance_reduction > 0)
                    <dt>Resistance Reduction</dt>
                    <dd>+ {{ $classSpecial->resistance_reduction * 100 }} %</dd>
                @endif
            </dl>
        </div>
    </div>
</x-core.cards.card-with-title>
