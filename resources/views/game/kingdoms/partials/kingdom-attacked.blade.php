
@include('game.kingdoms.partials.kingdom-section', ['log' => $log])

@php
  $showUnitInformation = !empty($log['units']);
  $showBuildingInformation = !empty($log['buildings']);
@endphp

<div class="row {{$showUnitInformation && $showBuildingInformation ? '' : 'justify-content-md-center'}}">

    @if (!$showBuildingInformation && !$showUnitInformation)
        <x-cards.card>
            Your kingdom and the units survived the attack.
        </x-cards.card>
    @endif

    @if ($showBuildingInformation)
        <div class="col-xs-12 col-sm-12 col-md-6">
            <x-cards.card>
                <h3>Buildings</h3>
                <hr />
                @foreach($log['buildings'] as $key => $buildingInfo)
                    @php
                        $class = $buildingInfo['old_durability'] !== $buildingInfo['new_durability'] ? 'text-danger' : 'text-success';
                    @endphp
                    <dl>
                        <dt><strong>Name</strong>:</dt>
                        <dd>{{$key}}</dd>
                        <dt><strong>Durability Before Attack</strong>:</dt>
                        <dd>{{$buildingInfo['old_durability']}}</dd>
                        <dt><strong>Durability After Attack</strong>:</dt>
                        <dd class="{{$class}}">{{$buildingInfo['new_durability']}}</dd>
                        <dt><strong>Percentage Lost</strong>:</dt>
                        <dd class="{{$class}}">{{number_format($buildingInfo['durability_lost'], 2) * 100}}%</dd>
                        <dt><strong>Has Fallen?</strong>:</dt>
                        <dd class="{{$buildingInfo['has_fallen'] ? 'text-danger' : 'text-success'}}">{{$buildingInfo['has_fallen'] ? 'Yes' : 'No'}}</dd>
                        @if ($buildingInfo['affects_morale'])
                            @if (!$buildingInfo['decreases_morale'])
                                <dt><strong>Increase Morale/hr?</strong>:</dt>
                                <dd class="text-success">{{$buildingInfo['increase_morale_amount'] * 100}}%</dd>
                            @else
                                <dt><strong>Decreases Morale/hr?</strong>:</dt>
                                <dd class="text-danger">{{$buildingInfo['decrease_morale_amount'] * 100}}%</dd>
                            @endif
                        @endif
                    </dl>
                    <hr />
                @endforeach
            </x-cards.card>
        </div>
    @endif

    @if ($showUnitInformation)
        <div class="col-xs-12 col-sm-12 col-md-6">
            <x-cards.card>
                <h3>Units</h3>
                <hr />
                @foreach($log['units'] as $key => $unitInfo)
                    @php
                        $class = $unitInfo['old_amount'] !== $unitInfo['new_amount'] ? 'text-danger' : 'text-success';
                    @endphp
                    <dl>
                        <dt><strong>Name</strong>:</dt>
                        <dd>{{$key}}</dd>
                        <dt><strong>Amount Before Attack</strong>:</dt>
                        <dd>{{$unitInfo['old_amount']}}</dd>
                        <dt><strong>Amount After Attack</strong>:</dt>
                        <dd class="{{$class}}">{{$unitInfo['new_amount']}}</dd>
                        <dt><strong>Percentage Lost</strong>:</dt>
                        <dd class="{{$class}}">{{$unitInfo['lost'] * 100}}%</dd>
                        <dt><strong>Lost All?</strong>:</dt>
                        <dd class="{{$unitInfo['lost_all'] ? 'text-danger' : 'text-success'}}">{{$unitInfo['lost_all'] ? 'Yes' : 'No'}}</dd>
                    </dl>
                    <hr />
                @endforeach
            </x-cards.card>
        </div>
    @endif

</div>
