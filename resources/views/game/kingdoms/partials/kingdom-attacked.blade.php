<div class="row">
    <div class="col-xs-12 col-sm-12 col-md-6">
        <h3>Buildings</h3>
        <hr />
        @foreach($log['buildings'] as $key => $buildingInfo)
            <dl>
                <dt><strong>Name</strong>:</dt>
                <dd>{{$key}}</dd>
                <dt><strong>Durability Before Attack</strong>:</dt>
                <dd>{{$buildingInfo['old_durability']}}</dd>
                <dt><strong>Durability After Attack</strong>:</dt>
                <dd>{{$buildingInfo['new_durability']}}</dd>
                <dt><strong>Percentage Lost</strong>:</dt>
                <dd>{{$buildingInfo['durability_lost'] * 100}}%</dd>
                <dt><strong>Has Fallen?</strong>:</dt>
                <dd>{{$buildingInfo['has_fallen'] ? 'Yes' : 'No'}}</dd>
            </dl>
            <hr />
        @endforeach
    </div>
    <div class="col-xs-12 col-sm-12 col-md-6">
        <h3>Units</h3>
        <hr />
        @foreach($log['units'] as $key => $buildingInfo)
            <dl>
                <dt><strong>Name</strong>:</dt>
                <dd>{{$key}}</dd>
                <dt><strong>Amount Before Attack</strong>:</dt>
                <dd>{{$buildingInfo['old_amount']}}</dd>
                <dt><strong>Amount After Attack</strong>:</dt>
                <dd>{{$buildingInfo['new_amount']}}</dd>
                <dt><strong>Percentage Lost</strong>:</dt>
                <dd>{{$buildingInfo['lost'] * 100}}%</dd>
                <dt><strong>Lost All?</strong>:</dt>
                <dd>{{$buildingInfo['lost_all'] ? 'Yes' : 'No'}}</dd>
            </dl>
            <hr />
        @endforeach
    </div>
</div>
