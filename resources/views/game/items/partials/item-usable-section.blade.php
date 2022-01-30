<x-cards.card-with-title title="Usable Item">
    <div class="alert alert-info mb-3 mt-2">
        <p>
            When using this item, it's effects will be applied for a specified amount of time in minutes. Usable items, accept those that are used
            to attack kingdoms, are used from the character inventory section. These items can only be crafted and <strong>can be sold on the market</strong>.
        </p>
        <p>
            Upon being used, you can see the "applied boons" under the Active Boons tab on the character sheet where you can see the boon
            and even cancel its effects early if you so desire.
        </p>
        <p>
            These items can crafted via

            @if (isset($skill))
                @auth
                    @if (auth()->user()->hasRole('Admin'))
                        <a href="{{route('skills.skill', ['skill' => $skill->id])}}">Alchemy</a>.
                    @elseif (auth()->user())
                        <a href="{{route('skill.character.info', ['skill' => $skill->id])}}">Alchemy</a>.
                    @endif
                @else
                    <a href="{{route('info.page.skill', ['skill' => $skill->id])}}">Alchemy</a>.
                @endif
            @endif
        </p>
    </div>

    @if ($item->damages_kingdoms)
        <div class="alert alert-warning mb-3">
            </p>
                This is a single use item that can only be used when attacking kingdoms. The way this works is you move to a kingdom you want to attack.
                Then you click <strong>Attack Kingdom</strong>. from here, instead of selecting a kingdom to attack with, you can select to use an item, from there you can pick
                the item to use.
            <p>

            <p>
                Upon use, the damage shown below will be whats done to all aspects of the kingdom. Buildings, morale, units - all reduced - at once.
            </p>

            <p>
                You cannot use these items to take a kingdom. Even if you decimate the kingdom, you must move in with a <strong>settler</strong> to take the kingdom.
            </p>
        </div>
        <dl>
            <dt>Damages Kingdom For:</dt>
            <dd>{{$item->kingdom_damage * 100}}%</dd>
        </dl>
    @else
        <dl>
            <dt>Lasts For: </dt>
            <dd>{{$item->lasts_for}} Minutes</dd>

            @if ($item->stat_increase)
                <dt>Increases all core stats by: </dt>
                <dd>{{$item->increase_stat_by * 100}}%</dd>
            @endif
            @if (!is_null($item->affects_skill_type))
                <dt>Skills Affected: </dt>
                <dd>{{empty($skills) ? 'None' : implode(', ', $skills)}}</dd>
                <dt>Skill Bonus: </dt>
                <dd>{{$item->increase_skill_bonus_by * 100}}%</dd>
                <dt>Skill Training Bonus: </dt>
                <dd>{{$item->increase_skill_training_bonus_by * 100}}%</dd>
                <dt>Skill Base Damage Mod Bonus:</dt>
                <dd>{{$item->base_damage_mod_bonus * 100}}%</dd>
                <dt>Skill Base Healing Mod Bonus:</dt>
                <dd>{{$item->base_healing_mod_bonus * 100}}%</dd>
                <dt>Skill Base AC Mod Bonus:</dt>
                <dd>{{$item->base_ac_mod_bonus * 100}}%</dd>
                <dt>Fight Timeout Mod Bonus:</dt>
                <dd>{{$item->fight_time_out_mod_bonus * 100}}%</dd>
                <dt>Move Timeout Mod Bonus:</dt>
                <dd>{{$item->move_time_out_mod_bonus * 100}}%</dd>
            @endif
            @if ($item->base_damage_mod > 0.0)
                <dt>Base Damage Modifier</dt>
                <dd>{{$item->base_damage_mod * 100}}%</dd>
            @endif
            @if ($item->base_ac_mod > 0.0)
                <dt>Base AC Modifier</dt>
                <dd>{{$item->base_ac_mod * 100}}%</dd>
            @endif
            @if ($item->base_healing_mod > 0.0)
                <dt>Base Healing Modifier</dt>
                <dd>{{$item->base_healing_mod * 100}}%</dd>
            @endif
            @if ($item->str_mod > 0.0)
                <dt>Str Modifier</dt>
                <dd>{{$item->str_mod * 100}}%</dd>
            @endif
            @if ($item->dex_mod > 0.0)
                <dt>Dex Modifier</dt>
                <dd>{{$item->dex_mod * 100}}%</dd>
            @endif
            @if ($item->dur_mod > 0.0)
                <dt>Dur Modifier</dt>
                <dd>{{$item->dur_mod * 100}}%</dd>
            @endif
            @if ($item->int_mod > 0.0)
                <dt>Int Modifier</dt>
                <dd>{{$item->int_mod * 100}}%</dd>
            @endif
            @if ($item->chr_mod > 0.0)
                <dt>Chr Modifier</dt>
                <dd>{{$item->chr_mod * 100}}%</dd>
            @endif
            @if ($item->agi_mod > 0.0)
                <dt>Agi Modifier</dt>
                <dd>{{$item->agi_mod * 100}}%</dd>
            @endif
            @if ($item->focus_mod > 0.0)
                <dt>Focus Modifier</dt>
                <dd>{{$item->focus_mod * 100}}%</dd>
            @endif
        </dl>
    @endif
</x-cards.card-with-title>
