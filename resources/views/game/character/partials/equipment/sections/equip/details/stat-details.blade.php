@foreach($details as $key => $value)
    <div class="col-md-6 mt-4">
        @if ($hasDefaultPosition)
            <p>If Replaced:</p>
        @else
            <p>If {{title_case(str_replace('-', ' ', $key))}} Replaced:</p>
        @endif

        <dl>
            <dt>Attack:</dt>
            <dd><span class={{$value['damage_adjustment'] >= 0 ? 'text-success' : 'text-danger'}}>{{$value['damage_adjustment'] >= 0 ? '+' : ''}}{{$value['damage_adjustment']}}</span></dd>
            <dt>AC:</dt>
            <dd><span class={{$value['ac_adjustment'] >= 0 ? 'text-success' : 'text-danger'}}>{{$value['ac_adjustment'] >= 0 ? '+' : ''}}{{$value['ac_adjustment']}}</span></dd>
            <dt>Healing:</dt>
            <dd><span class={{$value['healing_adjustment'] >= 0 ? 'text-success' : 'text-danger'}}>{{$value['healing_adjustment'] >= 0 ? '+' : ''}}{{$value['healing_adjustment']}}</span></dd>
            <dt>Base Attack Mod:</dt>
            <dd><span class={{$value['base_damage_adjustment'] >= 0 ? 'text-success' : 'text-danger'}}>{{$value['base_damage_adjustment'] >= 0 ? '+' : ''}}{{$value['base_damage_adjustment'] * 100}}%</span></dd>
            <dt>Base AC Mod:</dt>
            <dd><span class={{$value['base_ac_adjustment'] >= 0 ? 'text-success' : 'text-danger'}}>{{$value['base_ac_adjustment'] >= 0 ? '+' : ''}}{{$value['base_ac_adjustment'] * 100}}%</span></dd>
            <dt>Base Healing Mod:</dt>
            <dd><span class={{$value['base_healing_adjustment'] >= 0 ? 'text-success' : 'text-danger'}}>{{$value['base_healing_adjustment'] >= 0 ? '+' : ''}}{{$value['base_healing_adjustment']}}</span></dd>
            <dt>Str:</dt>
            <dd><span class={{$value['str_adjustment'] >= 0 ? 'text-success' : 'text-danger'}}>{{$value['str_adjustment'] >= 0 ? '+' : ''}}{{$value['str_adjustment'] * 100}}%</span></dd>
            <dt>Dur:</dt>
            <dd><span class={{$value['dur_adjustment'] >= 0 ? 'text-success' : 'text-danger'}}>{{$value['dur_adjustment'] >= 0 ? '+' : ''}}{{$value['dur_adjustment'] * 100}}%</span></dd>
            <dt>Dex:</dt>
            <dd><span class={{$value['dex_adjustment'] >= 0 ? 'text-success' : 'text-danger'}}>{{$value['dex_adjustment'] >= 0 ? '+' : ''}}{{$value['dex_adjustment'] * 100}}%</span></dd>
            <dt>Chr:</dt>
            <dd><span class={{$value['chr_adjustment'] >= 0 ? 'text-success' : 'text-danger'}}>{{$value['chr_adjustment'] >= 0 ? '+' : ''}}{{$value['chr_adjustment'] * 100}}%</span></dd>
            <dt>Int:</dt>
            <dd><span class={{$value['int_adjustment'] >= 0 ? 'text-success' : 'text-danger'}}>{{$value['int_adjustment'] >= 0 ? '+' : ''}}{{$value['int_adjustment'] * 100}}%</span></dd>
        </dl>
    </div>
@endforeach
