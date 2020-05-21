@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Equipped</h4>
                    <hr />

                    @if (empty($details))
                        <div class="alert alert-info">
                            You have nothing equipped. Anything is better then nothing.
                        </div>
                    @else
                        @foreach($details as $key => $value)
                            @if (!empty($details[$key]))
                                @if ($details[$key]['is_better'])
                                    @include('game.core.partials.item-details-replace', [
                                        'details' => $details[$key]
                                    ])
                                @else
                                    <div class="alert alert-warning">Your current equipment may be better. Check the equip options.</div>
                                @endif
                            @endif
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">To Equip:</h4>
                    <hr />
                    @include('game.core.partials.item-details-to-equip', [
                        'item' => $itemToEquip
                    ])

                    <form class="mt-4" action="{{route('game.equip.item')}}" method="POST">
                        @csrf
                        <input type="hidden" name="slot_id" value={{$slotId}} />
                        <input type="hidden" name="equip_type" value={{$type}} />

                        <fieldset class="form-group row">
                          <legend class="col-sm-2">Which Position</legend>
                          <div class="col-sm-10">
                            <div class="form-check">
                                <label class="form-check-label">
                                  <input class="form-check-input radio-inline" type="radio" name="position" id="position-left" value="left-hand">
                                    @if (isset($details['left-hand']))
                                        Left Hand <span class={{$details['left-hand']['damage_adjustment'] > 0 ? "text-success" : "text-danger"}}>{{$details['left-hand']['damage_adjustment']}} (Replace)</span>
                                    @else
                                        Left Hand <span class="text-success">+{{$itemToEquip->getTotalDamage()}} (Equip)</span>
                                    @endif
                                </label>
                            </div>
                            <div class="form-check">
                                <label class="form-check-label">
                                    <input class="form-check-input radio-inline" type="radio" name="position" id="position-right" value="right-hand">
                                    @if (isset($details['right-hand']))
                                        Right Hand <span class={{$details['right-hand']['damage_adjustment'] > 0 ? "text-success" : "text-danger"}}>{{$details['right-hand']['damage_adjustment']}} (Replace)</span>
                                    @else
                                        Right Hand <span class="text-success">{{$itemToEquip->getTotalDamage()}} (Equip)</span>
                                    @endif
                                </label>
                            </div>
                        </fieldset>
                        <button type="submit" class="btn btn-primary">Equip</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
