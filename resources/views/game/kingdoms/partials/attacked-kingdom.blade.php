@foreach($log['units'] as $key => $unitInfo)
    <dl>
        <dt><strong>Name</strong>:</dt>
        <dd>{{$key}}</dd>
        <dt><strong>Total Attack</strong>:</dt>
        <dd>{{$unitInfo['total_attack']}}</dd>
        <dt><strong>Total Defence</strong>:</dt>
        <dd>{{$unitInfo['total_defence']}}</dd>
        <dt><strong>Is Healer?</strong>:</dt>
        <dd>{{$unitInfo['healer'] ? 'Yes' : 'No'}}</dd>

        @if ($unitInfo['total_heal'] > 0)
            <dt><strong>Total Healing</strong>:</dt>
            <dd>{{$unitInfo['total_heal']}}%</dd>
        @endif

        <dt><strong>Is Settler?</strong>:</dt>
        <dd>{{$unitInfo['settler'] ? 'Yes' : 'No'}}</dd>

        @if (!is_null($unitInfo['primary_target']))
            <dt><strong>Primary Target</strong>:</dt>
            <dd>{{$unitInfo['primary_target']}}</dd>
        @endif

        @if (!is_null($unitInfo['fall_back']))
            <dt><strong>Fallback Target</strong>:</dt>
            <dd>{{$unitInfo['fall_back']}}</dd>
        @endif

        <dt><strong>Percentage Lost</strong>:</dt>
        <dd>{{$unitInfo['lost'] * 100}}%</dd>
        <dt><strong>Lost All?</strong>:</dt>
        <dd>{{$unitInfo['lost_all'] ? 'Yes' : 'No'}}</dd>
    </dl>
    <hr />
@endforeach
