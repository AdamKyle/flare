
<div class="row justify-content-md-center">
    <div class="{{!empty($log['defender_units']) ? 'col-md-6' : 'hide'}}">
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
            @if (empty($log['defender_units']))
                <p class="text-info">None of their units were affected. If you lost no units, then chances are they have no units.</p>
            @endif
            <p>
                The following is a list of buildings (from the defending kingdom) and the percentage of durability lost in your attack.
                If the below shows 0% and you lost no units, chances are this is a NPC kingdom or a new kingdom. You should be good to send in a settler,
                if you have any.
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
