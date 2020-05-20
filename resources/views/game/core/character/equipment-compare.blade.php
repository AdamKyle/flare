@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Equipped</h4>
                    <hr />

                    @if (is_null($replacesExistingItem))
                        @if (is_null($matchingEquippedItems)) 
                            <div class="alert alert-info">
                                You have nothing equipped. Anything is better then nothing.
                            </div>
                        @else
                            <div class="alert alert-info">
                                The equipped items are either better or the same as the item you want to equip.
                            </div>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Base Damage</th>
                                        <th>Type</th>
                                        <th>Position</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($matchingEquippedItems as $equipped)
                                        <tr>
                                            <td>{{$equipped->item->name}}</td>
                                            <td>{{$equipped->item->base_damage}}</td>
                                            <td>{{$equipped->item->type}}</td>
                                            <td>{{$equipped->position}}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    @else
                        <?php dump($replacesExistingItem); ?>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">To Equip:</h4>
                    <hr />
                    <h6>Item Details</h6>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Base Damage</th>
                                <th>Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th>{{$itemToEquip->name}}</th>
                                <th>{{$itemToEquip->base_damage}}</th>
                                <th>{{$itemToEquip->type}}</th>
                            </tr>
                        </tbody>
                    </table>
                    <hr />
                    <h6>Item Artifact</h6>
                    @if (is_null($itemToEquip->artifactProperty))
                        <div class="alert alert-info">
                            There is no artifact set to this item.
                        </div>
                    @else
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Base Damage Mod</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <th>{{$itemToEquip->artifactProperty->name}}</th>
                                    <th>{{$itemToEquip->artifactProperty->base_damage_mod}}</th>
                                    <th>{{$itemToEquip->artifactProperty->description}}</th>
                                </tr>
                            </tbody>
                        </table>
                    @endif

                    <hr />
                    <h6>Item Affixes</h6>
                    @if ($itemToEquip->itemAffixes->isEmpty())
                        <div class="alert alert-info">
                            There are no affixes on this item.
                        </div>
                    @else
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Base Damage Mod</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($itemToEquip->itemAffixes as $affix)
                                    <tr>
                                        <th>{{$affix->name}}</th>
                                        <th>{{$affix->base_damage_mod}}</th>
                                        <th>{{$affix->description}}</th>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif

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
                                    Left Hand
                                </label>
                            </div>
                            <div class="form-check">
                                <label class="form-check-label">
                                    <input class="form-check-input radio-inline" type="radio" name="position" id="position-right" value="right-hand">
                                    Right Hand
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
