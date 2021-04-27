<div class="row mb-3">
    <div class="col-12">
        <h3>Kingdom Changes</h3>
        <hr />
        <div class="row">
            <div class="col-xs-12 col-sm-12 col-md-6">
                @php
                    $newMoraleClass = $log['kingdom']['new_morale'] > 0.5 ? 'text-success' : ($log['kingdom']['new_morale'] === 0.0 ? 'text-danger' : 'text-warning');
                    $moraleLost     = $log['kingdom']['old_morale'] - $log['kingdom']['new_morale'];

                    $moraleLostClass = $moraleLost > 0.0 ? 'text-danger' : 'text-success';
                    $moraleIncreaseClass = $log['kingdom']['morale_increase'] > 0.5 ? 'text-success' : ($log['kingdom']['morale_increase'] === 0.0 ? 'text-danger' : 'text-warning');
                    $moraleDecreaseClass = $log['kingdom']['morale_increase'] > 0.0 ? 'text-danger' : 'text-success';
                @endphp
                <dl>
                    <dt><strong>Old Kingdom Morale</strong>:</dt>
                    <dd>{{$log['kingdom']['old_morale'] * 100}}%</dd>
                    <dt><strong>New Kingdom Morale</strong>:</dt>
                    <dd class="{{$newMoraleClass}}">{{$log['kingdom']['new_morale'] * 100}}%</dd>
                    <dt><strong>Morale Lost</strong>:</dt>
                    <dd class="{{$moraleLostClass}}">{{$moraleLost * 100}}%</dd>
                </dl>
            </div>
            <div class="col-xs-12 col-sm-12 col-md-6">
                <dl>
                    <dt><strong>Kingdom Morale Increase/hr</strong>:</dt>
                    <dd class="{{$moraleIncreaseClass}}">{{$log['kingdom']['morale_increase'] * 100}}%</dd>
                    <dt><strong>Kingdom Morale Decrease/hr</strong>:</dt>
                    <dd class="{{$moraleDecreaseClass}}">{{$log['kingdom']['morale_decrease'] * 100}}%</dd>
                </dl>
            </div>
        </div>
        <p class="mt-3 text-muted">
            <small>
                Kingdom morale can be lost because either buildings have fallen that affect morale, see below for more info, or
                the attacker might have sent a settler to reduce the morale.
            </small>
        </p>
        <p class="mt-3">
            <small class="text-success">Your kingdom morale, and it's hourly increase are fine.</small><br />
            <small class="text-warning">Careful your morale is getting low and your morale increase per low is very low.</small><br />
            <small class="text-danger">Your kingdom is in danger of falling or losing morale per hour.</small><br />
        </p>
        <hr />
    </div>
</div>
<div class="row">
    <div class="col-xs-12 col-sm-12 col-md-6">
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
            </dl>
            <hr />
        @endforeach
    </div>
    <div class="col-xs-12 col-sm-12 col-md-6">
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
    </div>
</div>
