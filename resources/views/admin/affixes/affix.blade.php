@extends('layouts.app')

@section('content')
    <div class="row page-titles">
        <div class="col-md-6 align-self-right">
            <h4 class="mt-2">{{$itemAffix->name}}</h4>
        </div>
        <div class="col-md-6 align-self-right">
            <a href="{{url()->previous()}}" class="btn btn-success float-right ml-2">Back</a>
        </div>
    </div>
    <hr />
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <p>{{$itemAffix->description}}</p>
                    <hr />
                    <div class="row">
                        <div class="col-md-6">
                            <dl>
                                <dt>Base Damage:</dt>
                                <dd>{{$itemAffix->base_damage_mod * 100}}%</dd>
                                <dt>Base Defence:</dt>
                                <dd>{{$itemAffix->base_ac_mod * 100}}%</dd>
                                <dt>Base Healing Mod:</dt>
                                <dd>{{$itemAffix->base_healing_mod * 100}}%</dd>
                                <dt>Class Bonus Mod:</dt>
                                <dd>{{$itemAffix->class_bonus * 100}}%</dd>
                                <dt>Str Modifier:</dt>
                                <dd>{{$itemAffix->str_mod * 100}}%</dd>
                                <dt>Dex Modifier:</dt>
                                <dd>{{$itemAffix->dex_mod * 100}}%</dd>
                                <dt>Dur Modifier:</dt>
                                <dd>{{$itemAffix->dur_mod * 100}}%</dd>
                                <dt>Int Modifier:</dt>
                                <dd>{{$itemAffix->int_mod * 100}}%</dd>
                                <dt>Chr Modifier:</dt>
                                <dd>{{$itemAffix->chr_mod * 100}}%</dd>
                                <dt>Agi Modifier:</dt>
                                <dd>{{$itemAffix->agi_mod * 100}}%</dd>
                                <dt>Focus Modifier:</dt>
                                <dd>{{$itemAffix->focus_mod * 100}}%</dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl>
                                <dt>Skill Name:</dt>
                                <dd>{{is_null($itemAffix->skill_name) ? 'N/A' : $itemAffix->skill_name}}</dd>
                                <dt>Skill Training Bonus (XP Bonus):</dt>
                                <dd>{{is_null($itemAffix->skill_name) ? 0 : $itemAffix->skill_training_bonus * 100}}%</dd>
                                <dt>Skill Bonus (When Using):</dt>
                                <dd>{{is_null($itemAffix->skill_name) ? 0 : $itemAffix->skill_bonus * 100}}%</dd>
                                <dt>Skill Base Damage Modifier:</dt>
                                <dd>{{is_null($itemAffix->skill_name) ? 0 : $itemAffix->base_damage_mod_bonus * 100}}%</dd>
                                <dt>Skill Base Healing Modifier:</dt>
                                <dd>{{is_null($itemAffix->skill_name) ? 0 : $itemAffix->base_healing_mod_bonus * 100}}%</dd>
                                <dt>Skill Base Armour Class Modifier:</dt>
                                <dd>{{is_null($itemAffix->skill_name) ? 0 : $itemAffix->base_ac_mod_bonus * 100}}%</dd>
                                <dt>Skill Fight Time Out Modifier:</dt>
                                <dd>{{is_null($itemAffix->skill_name) ? 0 : $itemAffix->fight_time_out_mod_bonus * 100}}%</dd>
                                <dt>Skill Move Time Out Modifier:</dt>
                                <dd>{{is_null($itemAffix->skill_name) ? 0 : $itemAffix->move_time_out_mod_bonus * 100}}%</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <div class="col-md-6">
            <h2 class="mt-2 mb-2">Enchanting Information</h2>

            <div class="card">
                <div class="card-body">
                    <dl>
                        <dt>Base Cost:</dt>
                        <dd>{{number_format($itemAffix->cost)}} gold</dd>
                        <dt>Intelligence Required:</dt>
                        <dd>{{$itemAffix->int_required}}</dd>
                        <dt>Level Required:</dt>
                        <dd>{{$itemAffix->skill_level_required}}</dd>
                        <dt>Level Becomes To Easy:</dt>
                        <dd>{{$itemAffix->skill_level_trivial}}</dd>
                    </dl>
                </div>
            </div>

            @if ($itemAffix->damage !== 0)
                <h2 class="mt-2 mb-2">Damage Information</h2>

                <div class="card">
                    <div class="card-body">
                        <p>
                            Affixes such as these will fire automatically. How ever enemies can out right
                            resist the damage done. All enemies have a % of resistance against affixes. Celestials have a higher
                            amount of resistance then regular drop down critters.
                        </p>
                        <p>
                            Unlike artifact Annulment and Spell Evasion, the resistance will not reduce damage done, instead it will
                            out right nullify the damage. If the enchantment is marked as irresistible damage, then the enemy cannot resist
                            the incoming damage.
                        </p>
                        <p>
                            These affixes will fire, regardless if you miss or hit. These affixes cannot stack unless otherwise stated.
                            That means, having multiple will do nothing, you'll take the highest of all non stacking damaging affixes.
                        </p>
                        <p>
                            With the right quest item, you can make all damage from all affixes Irresistible.
                        </p>
                        <dl>
                            <dt>Damage:</dt>
                            <dd>{{$itemAffix->damage}}</dd>
                            <dt>Is Damage Irresistible?:</dt>
                            <dd>{{$itemAffix->irresistible_damage ? 'Yes' : 'No'}}</dd>
                            <dt>Can Stack:</dt>
                            <dd>{{$itemAffix->damage_can_stack ? 'Yes' : 'No'}}</dd>
                        </dl>
                    </div>
                </div>
            @endif

            @if ($itemAffix->reduces_enemy_stats)
                <h2 class="mt-2 mb-2">Stat Reduction</h2>

                <div class="card">
                    <div class="card-body">
                        <p>
                            Affixes that reduce stats can and cannot stack. For example: Prefixes cannot stack, but Suffixes can.
                        </p>
                        <p>
                            If you have multiple prefixes attached that reduce all enemy stats, we will take the first one. Doesn't matter
                            what it is.
                        </p>
                        <p>
                            Stat reduction is applied before anything else is done, but can be resisted unless you have the appropriate quest item.
                        </p>
                        <dl>
                            <dt>Str Reduction:</dt>
                            <dd>{{$itemAffix->str_reduction * 100}}%</dd>
                            <dt>Dex Reduction:</dt>
                            <dd>{{$itemAffix->dex_reduction * 100}}%</dd>
                            <dt>Dur Reduction:</dt>
                            <dd>{{$itemAffix->dur_reduction * 100}}%</dd>
                            <dt>Int Reduction:</dt>
                            <dd>{{$itemAffix->int_reduction * 100}}%</dd>
                            <dt>Chr Reduction:</dt>
                            <dd>{{$itemAffix->chr_reduction * 100}}%</dd>
                            <dt>Agi Reduction:</dt>
                            <dd>{{$itemAffix->agi_reduction * 100}}%</dd>
                            <dt>Focus Reduction:</dt>
                            <dd>{{$itemAffix->focus_reduction * 100}}%</dd>
                        </dl>
                    </div>
                </div>
            @endif
            @if ($itemAffix->reduces_enemy_stats)
                <h2 class="mt-2 mb-2">Stat Reduction</h2>

                <div class="card">
                    <div class="card-body">
                        <p>
                            Affixes that reduce stats can and cannot stack. For example: Prefixes cannot stack, but Suffixes can.
                        </p>
                        <p>
                            If you have multiple prefixes attached that reduce all enemy stats, we will take the first one. Doesn't matter
                            what it is.
                        </p>
                        <p>
                            Stat reduction is applied before anything else is done, but can be resisted unless you have the appropriate quest item.
                        </p>
                        <dl>
                            <dt>Str Reduction:</dt>
                            <dd>{{$itemAffix->str_reduction * 100}}%</dd>
                            <dt>Dex Reduction:</dt>
                            <dd>{{$itemAffix->dex_reduction * 100}}%</dd>
                            <dt>Dur Reduction:</dt>
                            <dd>{{$itemAffix->dur_reduction * 100}}%</dd>
                            <dt>Int Reduction:</dt>
                            <dd>{{$itemAffix->int_reduction * 100}}%</dd>
                            <dt>Chr Reduction:</dt>
                            <dd>{{$itemAffix->chr_reduction * 100}}%</dd>
                            <dt>Agi Reduction:</dt>
                            <dd>{{$itemAffix->agi_reduction * 100}}%</dd>
                            <dt>Focus Reduction:</dt>
                            <dd>{{$itemAffix->focus_reduction * 100}}%</dd>
                        </dl>
                    </div>
                </div>
            @endif
            @if (!is_null($itemAffix->steal_life_amount))
                <h2 class="mt-2 mb-2">Life Stealing Amount</h2>

                <div class="card">
                    <div class="card-body">
                        <p>
                            These Affixes can and cannot stack. If you are a vampire they will stack and you have a chance for them to fire twice.
                            The first time they can fire is during the attack and the second time is after the enemies round if you or
                            the enemy is still alive.
                        </p>
                        <p>The chance aspect depends on the enemies affix resistance, assuming you do not have the appropriate quest item.</p>
                        <p>
                            If you are <strong>not</strong> a vampire, these affixes will
                            <strong>NOT</strong> stack. Instead we will use your highest and it will only fire after the enemy attack.
                        </p>
                        <dl>
                            <dt>Steal Life Amount:</dt>
                            <dd>{{$itemAffix->steal_life_amount * 100}}%</dd>
                        </dl>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
