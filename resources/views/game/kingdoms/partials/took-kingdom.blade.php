@include('game.kingdoms.partials.kingdom-section', ['log' => $log])

<div class="row justify-content-md-center">
    <div class="col-6">
        <x-core.cards.card>
            @foreach($log['units'] as $name => $unitInfo)
                <dl>
                    <dt><strong>Name</strong>:</dt>
                    <dd>{{$name}}</dd>
                    <dt><strong>Old Amount</strong>:</dt>
                    <dd>{{$unitInfo['old_amount']}}</dd>
                    <dt><strong>New Amount</strong>:</dt>
                    <dd>{{$unitInfo['new_amount']}}</dd>
                    <dt><strong>Gained</strong>:</dt>
                    <dd class="text-success">+{{$unitInfo['gained']}}</dd>
                </dl>
                <hr />
            @endforeach
        </x-core.cards.card>
    </div>
</div>
