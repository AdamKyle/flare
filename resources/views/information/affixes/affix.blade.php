@extends('layouts.information')

@section('content')
    <div class="row page-titles mt-3">
        <div class="col-md-6 align-self-right">
            <h4 class="mt-2">{{$itemAffix->name}}</h4>
        </div>
        <div class="col-md-6 align-self-right">
            <a href="{{url()->previous()}}" class="btn btn-primary float-right ml-2">Back</a>
        </div>
    </div>
    <hr />
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <p>{{$itemAffix->description}}</p>
                    <hr />
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
                        <dt>Skill Name:</dt>
                        <dd>{{is_null($itemAffix->skill_name) ? 'N/A' : $itemAffix->skill_name}}</dd>
                        <dt>Skill Training Bonus (XP Bonus):</dt>
                        <dd>{{is_null($itemAffix->skill_name) ? 0 : $itemAffix->skill_training_bonus * 100}}%</dd>
                        <dt>Skill Bonus (When Using):</dt>
                        <dd>{{is_null($itemAffix->skill_name) ? 0 : $itemAffix->skill_bonus * 100}}%</dd>
                    </dl>
                </div>
            </div>

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
