@foreach($details as $key => $value)
    <div class="col-md-6 mt-4">
        @if ($hasDefaultPosition)
            <p>If Replaced:</p>
        @else
            <p>If {{title_case(str_replace('-', ' ', $key))}} Replaced:</p>
        @endif

        <dl>
            <dt>Attack <sup>*</sup>:</dt>
            <dd><span class={{$value['damage_adjustment'] === 0 ? '' : ($value['damage_adjustment'] >= 0.0 ? 'text-success' : 'text-danger')}}>{{$value['damage_adjustment'] >= 0 ? '+' : ''}}{{$value['damage_adjustment']}}</span></dd>
            <dt>AC:</dt>
            <dd><span class={{$value['ac_adjustment'] === 0 ? '' : ($value['ac_adjustment'] >= 0.0 ? 'text-success' : 'text-danger')}}>{{$value['ac_adjustment'] >= 0 ? '+' : ''}}{{$value['ac_adjustment']}}</span></dd>
            <dt>Healing:</dt>
            <dd><span class={{$value['healing_adjustment'] === 0 ? '' : ($value['healing_adjustment'] >= 0.0 ? 'text-success' : 'text-danger')}}>{{$value['healing_adjustment'] >= 0 ? '+' : ''}}{{$value['healing_adjustment']}}</span></dd>
            <dt>Base Attack Mod:</dt>
            <dd><span class={{$value['base_damage_adjustment'] === 0.0 ? '' : ($value['base_damage_adjustment'] >= 0.0 ? 'text-success' : 'text-danger')}}>{{$value['base_damage_adjustment'] >= 0 ? '+' : ''}}{{$value['base_damage_adjustment'] * 100}}%</span></dd>
            <dt>Fight Timeout Mod <sup>**</sup>:</dt>
            <dd><span class={{$value['fight_timeout_mod_adjustment'] === 0.0 ? '' : ($value['fight_timeout_mod_adjustment'] >= 0.0 ? 'text-success' : 'text-danger')}}>{{$value['fight_timeout_mod_adjustment'] >= 0 ? '+' : ''}}{{$value['fight_timeout_mod_adjustment'] * 100}}%</span></dd>
            <dt>Base Damage Mod <sup>**</sup>:</dt>
            <dd><span class={{$value['base_damage_mod_adjustment'] === 0.0 ? '' : ($value['base_damage_mod_adjustment'] >= 0.0 ? 'text-success' : 'text-danger')}}>{{$value['base_damage_mod_adjustment'] >= 0 ? '+' : ''}}{{$value['base_damage_mod_adjustment'] * 100}}%</span></dd>
            <dt>Spell Evasion Modifier:</dt>
            <dd><span class={{$value['spell_evasion_adjustment'] === 0.0 ? '' : ($value['spell_evasion_adjustment'] >= 0.0 ? 'text-success' : 'text-danger')}}>{{$value['spell_evasion_adjustment'] >= 0 ? '+' : ''}}{{$value['spell_evasion_adjustment'] * 100}}%</span></dd>
            <dt>Artifact Annulment Modifier:</dt>
            <dd><span class={{$value['artifact_annulment_adjustment'] === 0.0 ? '' : ($value['artifact_annulment_adjustment'] >= 0.0 ? 'text-success' : 'text-danger')}}>{{$value['artifact_annulment_adjustment'] >= 0 ? '+' : ''}}{{$value['artifact_annulment_adjustment'] * 100}}%</span></dd>
            @if ($item->can_resurrect)
                <dt>Resurrection Chance <sup>rc</sup>:</dt>
                <dd><span class={{$value['res_chance_adjustment'] === 0.0 ? '' : ($value['res_chance_adjustment'] >= 0.0 ? 'text-success' : 'text-danger')}}>{{$value['res_chance_adjustment'] >= 0 ? '+' : ''}}{{$value['res_chance_adjustment'] * 100}}%</span></dd>
            @endif

            <dt>Str:</dt>
            <dd><span class={{$value['str_adjustment'] === 0.0 ? '' : ($value['str_adjustment'] >= 0.0 ? 'text-success' : 'text-danger')}}>{{$value['str_adjustment'] >= 0 ? '+' : ''}}{{$value['str_adjustment'] * 100}}%</span></dd>
            <dt>Dur:</dt>
            <dd><span class={{$value['dur_adjustment'] === 0.0 ? '' : ($value['dur_adjustment'] >= 0.0 ? 'text-success' : 'text-danger')}}>{{$value['dur_adjustment'] >= 0 ? '+' : ''}}{{$value['dur_adjustment'] * 100}}%</span></dd>
            <dt>Dex:</dt>
            <dd><span class={{$value['dex_adjustment'] === 0.0 ? '' : ($value['dex_adjustment'] >= 0.0 ? 'text-success' : 'text-danger')}}>{{$value['dex_adjustment'] >= 0 ? '+' : ''}}{{$value['dex_adjustment'] * 100}}%</span></dd>
            <dt>Chr:</dt>
            <dd><span class={{$value['chr_adjustment'] === 0.0 ? '' : ($value['chr_adjustment'] >= 0.0 ? 'text-success' : 'text-danger')}}>{{$value['chr_adjustment'] >= 0 ? '+' : ''}}{{$value['chr_adjustment'] * 100}}%</span></dd>
            <dt>Int:</dt>
            <dd><span class={{$value['int_adjustment'] === 0.0 ? '' : ($value['int_adjustment'] >= 0.0 ? 'text-success' : 'text-danger')}}>{{$value['int_adjustment'] >= 0 ? '+' : ''}}{{$value['int_adjustment'] * 100}}%</span></dd>
            <dt>Agi:</dt>
            <dd><span class={{$value['agi_adjustment'] === 0.0 ? '' : ($value['agi_adjustment'] >= 0.0 ? 'text-success' : 'text-danger')}}>{{$value['agi_adjustment'] >= 0 ? '+' : ''}}{{$value['agi_adjustment'] * 100}}%</span></dd>
            <dt>Focus:</dt>
            <dd><span class={{$value['focus_adjustment'] === 0.0 ? '' : ($value['focus_adjustment'] >= 0.0 ? 'text-success' : 'text-danger')}}>{{$value['focus_adjustment'] >= 0 ? '+' : ''}}{{$value['focus_adjustment'] * 100}}%</span></dd>
        </dl>
    </div>
@endforeach
