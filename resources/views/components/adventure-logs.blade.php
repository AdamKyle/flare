
@if ($hasLevels)
    @foreach ($logs as $level => $logsForLevel)
        <div class="card adventure-fight-log mb-3">
            <div class="card-body">
                <h4>Level: {{$level + 1}}</h4>
                <hr />
                @include('components.partials.adventure-level-log', [
                    'levelLog' => $logsForLevel
                ])
            </div>
        </div>
    @endforeach
@else
    <div class="card">
        <div class="card-body">
            <h4>Base Level:</h4>
            <hr />
            @include('components.partials.adventure-level-log', [
                'levelLog' => $logs
            ])
        </div>
    </div>
@endif