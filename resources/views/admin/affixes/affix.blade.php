@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row page-titles">
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
                        <dt>Skill Bonus:</dt>
                        <dd>{{is_null($itemAffix->skill_name) ? 0 : $itemAffix->skill_bonus * 100}}%</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
    @livewire('admin.items.data-table', [
        'affixId' => $itemAffix->id
    ])
</div>
@endsection