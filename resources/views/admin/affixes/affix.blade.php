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
        </div>
    </div>
@endsection
