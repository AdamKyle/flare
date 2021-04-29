<div class="row mb-3 justify-content-md-center">
    <x-cards.card>
        <div class="col-12">
            <h3>Kingdom Changes</h3>
            <hr />
            <div class="row">
                <div class="col-xs-12 col-sm-12 col-md-6">
                    @php
                        if (isset($log['kingdom']['new_morale'])) {
                            $newMoraleClass  = $log['kingdom']['new_morale'] > 0.5 ? 'text-success' : ($log['kingdom']['new_morale'] === 0 ? 'text-danger' : 'text-warning');
                            $moraleLost      = $log['kingdom']['old_morale'] - $log['kingdom']['new_morale'];
                            $moraleLostClass = $moraleLost > 0.0 ? 'text-danger' : 'text-success';
                        } else {
                            $currentMoraleClass  = $log['kingdom']['old_morale'] >= 0.25 ? ($log['kingdom']['old_morale'] >= 0.5 ? 'text-success' : 'text-warning') : 'text-danger';
                        }

                        $moraleIncreaseClass = $log['kingdom']['morale_increase'] > 0.1 ? 'text-success' : ($log['kingdom']['morale_increase'] === 0.0 ? 'text-danger' : 'text-warning');
                        $moraleDecreaseClass = $log['kingdom']['morale_decrease'] === 0 ? 'text-success' : 'text-danger';

                    @endphp

                    @if (isset($log['kingdom']['new_morale']))
                        <dl>
                            <dt><strong>Old Kingdom Morale</strong>:</dt>
                            <dd>{{$log['kingdom']['old_morale'] * 100}}%</dd>
                            <dt><strong>New Kingdom Morale</strong>:</dt>
                            <dd class="{{$newMoraleClass}}">{{$log['kingdom']['new_morale'] * 100}}%</dd>
                            <dt><strong>Morale Lost</strong>:</dt>
                            <dd class="{{$moraleLostClass}}">{{$moraleLost * 100}}%</dd>
                        </dl>
                    @else
                        <dl>
                            <dt><strong>Current Morale</strong>: </dt>
                            <dd class="{{$currentMoraleClass}}">{{$log['kingdom']['old_morale'] * 100}}%</dd>
                        </dl>
                    @endif
                </div>
                <div class="col-xs-12 col-sm-12 col-md-6">
                    <dl>
                        <dt><strong>Kingdom Morale Increase/hr</strong>:</dt>
                        <dd class="{{$moraleIncreaseClass}}">{{$log['kingdom']['morale_increase'] * 100}}% <sup>*</sup></dd>
                        <dt><strong>Kingdom Morale Decrease/hr</strong>:</dt>
                        <dd class="{{$moraleDecreaseClass}}">{{$log['kingdom']['morale_decrease'] * 100}}% <sup>*</sup></dd>
                    </dl>
                </div>
            </div>
            <p class="mt-3 text-muted"><sup>*</sup> Kingdom morale will never go below 0% and can never go above 100%</p>
            <p class="text-muted">
                Kingdom morale can be lost because either buildings have fallen that affect morale, see below for more info, or
                the attacker might have sent a settler to reduce the morale.
            </p>
            <p class="mt-3">
                <span class="text-success">Your kingdom morale, and it's hourly increase are fine.</span><br />
                <span class="text-warning">Careful your morale is getting low and/or your morale increase per hour is very low.</span><br />
                <span class="text-danger">Your kingdom is in danger of falling or losing morale per hour.</span><br />
            </p>
        </div>
    </x-cards.card>
</div>
