import React, { ReactNode } from 'react';

import MapGemRangeField from './map-gem-range-field';
import { gemTypeLabel } from '../../../../game/reusable-components/gems/enums/gem-type';
import MapGemFormFieldsProps from '../../types/map-gem-form-fields-props';
import { parseNumberOption } from '../../utils/parse-map-gem-dropdown-value';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import FieldWrapper from 'ui/forms/field-wrapper';

const MapGemMonsterRewardsFields = ({
  state,
  errors,
  form_options: formOptions,
  on_change: onChange,
}: MapGemFormFieldsProps): ReactNode => {
  const gemTypeItems: DropdownItem[] = formOptions.gem_types.map((gemType) => ({
    label: gemTypeLabel(gemType),
    value: gemType,
  }));

  return (
    <div className="space-y-4">
      <MapGemRangeField
        id="map-gem-enemy-quest-item-drop-chance-increase-range"
        label="Enemy Quest Item Drop Chance Increase Range"
        value={state.enemy_quest_item_drop_chance_increase_range}
        on_change={(value) =>
          onChange('enemy_quest_item_drop_chance_increase_range', value)
        }
        error={errors.enemy_quest_item_drop_chance_increase_range}
      />
      <MapGemRangeField
        id="map-gem-monster-xp-increase-range"
        label="Monster XP Increase Range"
        value={state.monster_xp_increase_range}
        on_change={(value) => onChange('monster_xp_increase_range', value)}
        error={errors.monster_xp_increase_range}
      />
      <MapGemRangeField
        id="map-gem-monster-gold-drop-increase-range"
        label="Monster Gold Drop Increase Range"
        value={state.monster_gold_drop_increase_range}
        on_change={(value) =>
          onChange('monster_gold_drop_increase_range', value)
        }
        error={errors.monster_gold_drop_increase_range}
      />

      <FieldWrapper
        id="map-gem-monster-atonement"
        label="Monster Atonement"
        error={errors.monster_atonement}
      >
        {(describedBy) => (
          <Dropdown
            id="map-gem-monster-atonement"
            aria_label="Monster Atonement"
            aria_described_by={describedBy}
            items={gemTypeItems}
            pre_selected_item={gemTypeItems.find(
              (item) => item.value === state.monster_atonement
            )}
            on_select={(item) =>
              onChange('monster_atonement', parseNumberOption(item.value))
            }
            on_clear={() => onChange('monster_atonement', null)}
            selection_placeholder="None"
          />
        )}
      </FieldWrapper>

      <MapGemRangeField
        id="map-gem-monster-atonement-range"
        label="Monster Atonement Range"
        value={state.monster_atonement_range}
        on_change={(value) => onChange('monster_atonement_range', value)}
        error={errors.monster_atonement_range}
      />
    </div>
  );
};

export default MapGemMonsterRewardsFields;
