<div class="row justify-content-md-center">
    <div class="{{!empty($log['defender_buildings']) ? 'col-md-6' : 'col-md-12'}}">
        <x-cards.card>
            <p>
                The following is a list of units (from the defending kingdom) and the percentage of units lost in your attack.
            </p>
            <dl>
                @foreach ($log['defender_units'] as $key => $value)
                    <dt>{{$key}}</dt>
                    <dd>{{$value['amount_killed'] * 100}}%</dd>
                @endforeach
            </dl>
        </x-cards.card>
    </div>
    <div class="{{!empty($log['defender_buildings']) ? 'col-md-6' : 'hide'}}">
        <x-cards.card>
            <p>
                The following is a list of buildings (from the defending kingdom) and the percentage of durability lost in your attack.
            </p>
            <dl>
                @foreach ($log['defender_buildings'] as $key => $value)
                    <dt>{{$key}}</dt>
                    <dd>{{$value['durability_lost'] * 100}}%</dd>
                @endforeach
            </dl>
        </x-cards.card>
    </div>
</div>
