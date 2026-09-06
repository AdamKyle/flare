import React, { ReactNode } from 'react';

import MapGemRangeField from './map-gem-range-field';
import MapGemFormFieldsProps from '../../types/map-gem-form-fields-props';

const MapGemEnemyCombatFields = ({
  state,
  errors,
  on_change: onChange,
}: MapGemFormFieldsProps): ReactNode => {
  return (
    <div className="grid gap-4 md:grid-cols-2">
      <MapGemRangeField
        id="map-gem-character-power-reduction-range"
        label="Character Power Reduction Range"
        value={state.character_power_reduction_range}
        on_change={(value) =>
          onChange('character_power_reduction_range', value)
        }
        error={errors.character_power_reduction_range}
      />
      <MapGemRangeField
        id="map-gem-enemy-strength-increase-range"
        label="Enemy Strength Increase Range"
        value={state.enemy_strength_increase_range}
        on_change={(value) => onChange('enemy_strength_increase_range', value)}
        error={errors.enemy_strength_increase_range}
      />
      <MapGemRangeField
        id="map-gem-enemy-healing-increase-range"
        label="Enemy Healing Increase Range"
        value={state.enemy_healing_increase_range}
        on_change={(value) => onChange('enemy_healing_increase_range', value)}
        error={errors.enemy_healing_increase_range}
      />
      <MapGemRangeField
        id="map-gem-enemy-spell-evasion-range"
        label="Enemy Spell Evasion Range"
        value={state.enemy_spell_evasion_range}
        on_change={(value) => onChange('enemy_spell_evasion_range', value)}
        error={errors.enemy_spell_evasion_range}
      />
      <MapGemRangeField
        id="map-gem-enemy-affix-resistance-range"
        label="Enemy Affix Resistance Range"
        value={state.enemy_affix_resistance_range}
        on_change={(value) => onChange('enemy_affix_resistance_range', value)}
        error={errors.enemy_affix_resistance_range}
      />
      <MapGemRangeField
        id="map-gem-enemy-entrancing-chance-range"
        label="Enemy Entrancing Chance Range"
        value={state.enemy_entrancing_chance_range}
        on_change={(value) => onChange('enemy_entrancing_chance_range', value)}
        error={errors.enemy_entrancing_chance_range}
      />
      <MapGemRangeField
        id="map-gem-enemy-devouring-light-chance-range"
        label="Enemy Devouring Light Chance Range"
        value={state.enemy_devouring_light_chance_range}
        on_change={(value) =>
          onChange('enemy_devouring_light_chance_range', value)
        }
        error={errors.enemy_devouring_light_chance_range}
      />
      <MapGemRangeField
        id="map-gem-enemy-devouring-darkness-chance-range"
        label="Enemy Devouring Darkness Chance Range"
        value={state.enemy_devouring_darkness_chance_range}
        on_change={(value) =>
          onChange('enemy_devouring_darkness_chance_range', value)
        }
        error={errors.enemy_devouring_darkness_chance_range}
      />
      <MapGemRangeField
        id="map-gem-enemy-ambush-chance-range"
        label="Enemy Ambush Chance Range"
        value={state.enemy_ambush_chance_range}
        on_change={(value) => onChange('enemy_ambush_chance_range', value)}
        error={errors.enemy_ambush_chance_range}
      />
      <MapGemRangeField
        id="map-gem-enemy-ambush-resistance-range"
        label="Enemy Ambush Resistance Range"
        value={state.enemy_ambush_resistance_range}
        on_change={(value) => onChange('enemy_ambush_resistance_range', value)}
        error={errors.enemy_ambush_resistance_range}
      />
      <MapGemRangeField
        id="map-gem-enemy-counter-chance-range"
        label="Enemy Counter Chance Range"
        value={state.enemy_counter_chance_range}
        on_change={(value) => onChange('enemy_counter_chance_range', value)}
        error={errors.enemy_counter_chance_range}
      />
      <MapGemRangeField
        id="map-gem-enemy-counter-resistance-range"
        label="Enemy Counter Resistance Range"
        value={state.enemy_counter_resistance_range}
        on_change={(value) => onChange('enemy_counter_resistance_range', value)}
        error={errors.enemy_counter_resistance_range}
      />
    </div>
  );
};

export default MapGemEnemyCombatFields;
