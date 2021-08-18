<div class="row justify-content-md-center">
    <div class="col-6">
        @if (empty($log['units']))
            @if ($lost)
                <x-cards.card>
                    You lost all your units in the attack. Check the enemy data tab for more info.
                </x-cards.card>
            @else
                <x-cards.card>
                    None of your units were lost in this attack. Check the enemy data tab for more info.
                </x-cards.card>
            @endif
        @else
            <x-cards.card>
                @foreach($log['units'] as $key => $unitInfo)
                    @php
                        $class = $unitInfo['old_amount'] !== $unitInfo['new_amount'] ? 'text-danger' : 'text-success';
                    @endphp

                    <dl>
                        <dt><strong>Name</strong>:</dt>
                        <dd>{{$key}}</dd>
                        <dt><strong>Total Attack</strong>:</dt>
                        <dd>{{$unitInfo['total_attack']}}</dd>
                        <dt><strong>Total Defence</strong>:</dt>
                        <dd>{{$unitInfo['total_defence']}}</dd>

                        @if  (isset($unitInfo['healer']))
                            <dt><strong>Is Healer?</strong>:</dt>
                            <dd>{{$unitInfo['healer'] ? 'Yes' : 'No'}}</dd>

                            @if ($unitInfo['total_heal'] > 0)
                                <dt><strong>Total Healing</strong>:</dt>
                                <dd>{{$unitInfo['total_heal']}}%</dd>
                            @endif
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

                        <dt><strong>Amount Sent</strong>:</dt>
                        <dd>{{$unitInfo['old_amount']}}</dd>
                        <dt><strong>New Amount</strong>:</dt>
                        <dd class="{{$class}}">{{$unitInfo['new_amount']}}</dd>
                        <dt><strong>Percentage Lost</strong>:</dt>
                        <dd class="{{$class}}">{{$unitInfo['lost'] * 100}}%</dd>
                        <dt><strong>Lost All?</strong>:</dt>
                        <dd class="{{$unitInfo['lost_all'] ? 'text-danger' : 'text-success'}}">{{$unitInfo['lost_all'] ? 'Yes' : 'No'}}</dd>
                    </dl>
                    @if ($unitInfo['settler'])
                        <p class="text-muted mt-3">
                            This unit was killed upon its duties. If you sent it alone,
                            it died long before it even got a chance to try and reduce the morale.
                            If sent with other units, then rest assured the morale was decreased.
                        </p>
                    @endif
                    <hr />
                @endforeach
            </x-cards.card>
        @endif
    </div>
</div>
