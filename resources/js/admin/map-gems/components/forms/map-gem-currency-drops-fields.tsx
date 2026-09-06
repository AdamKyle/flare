import React, { ReactNode } from 'react';

import MapGemRangeField from './map-gem-range-field';
import MapGemFormFieldsProps from '../../types/map-gem-form-fields-props';

const MapGemCurrencyDropsFields = ({
  state,
  errors,
  on_change: onChange,
}: MapGemFormFieldsProps): ReactNode => {
  return (
    <div className="grid gap-4 md:grid-cols-2">
      <MapGemRangeField
        id="map-gem-gold-gain-range"
        label="Gold Gain Range"
        value={state.gold_gain_range}
        on_change={(value) => onChange('gold_gain_range', value)}
        error={errors.gold_gain_range}
      />
      <MapGemRangeField
        id="map-gem-gold-dust-gain-range"
        label="Gold Dust Gain Range"
        value={state.gold_dust_gain_range}
        on_change={(value) => onChange('gold_dust_gain_range', value)}
        error={errors.gold_dust_gain_range}
      />
      <MapGemRangeField
        id="map-gem-shards-gain-range"
        label="Shards Gain Range"
        value={state.shards_gain_range}
        on_change={(value) => onChange('shards_gain_range', value)}
        error={errors.shards_gain_range}
      />
      <MapGemRangeField
        id="map-gem-copper-coin-gain-range"
        label="Copper Coin Gain Range"
        value={state.copper_coin_gain_range}
        on_change={(value) => onChange('copper_coin_gain_range', value)}
        error={errors.copper_coin_gain_range}
      />
      <MapGemRangeField
        id="map-gem-item-drop-chance-increase-range"
        label="Item Drop Chance Increase Range"
        value={state.item_drop_chance_increase_range}
        on_change={(value) =>
          onChange('item_drop_chance_increase_range', value)
        }
        error={errors.item_drop_chance_increase_range}
      />
      <MapGemRangeField
        id="map-gem-unique-item-drop-chance-increase-range"
        label="Unique Item Drop Chance Increase Range"
        value={state.unique_item_drop_chance_increase_range}
        on_change={(value) =>
          onChange('unique_item_drop_chance_increase_range', value)
        }
        error={errors.unique_item_drop_chance_increase_range}
      />
      <MapGemRangeField
        id="map-gem-mythic-item-drop-chance-increase-range"
        label="Mythic Item Drop Chance Increase Range"
        value={state.mythic_item_drop_chance_increase_range}
        on_change={(value) =>
          onChange('mythic_item_drop_chance_increase_range', value)
        }
        error={errors.mythic_item_drop_chance_increase_range}
      />
      <MapGemRangeField
        id="map-gem-cosmic-item-drop-chance-increase-range"
        label="Cosmic Item Drop Chance Increase Range"
        value={state.cosmic_item_drop_chance_increase_range}
        on_change={(value) =>
          onChange('cosmic_item_drop_chance_increase_range', value)
        }
        error={errors.cosmic_item_drop_chance_increase_range}
      />
    </div>
  );
};

export default MapGemCurrencyDropsFields;
