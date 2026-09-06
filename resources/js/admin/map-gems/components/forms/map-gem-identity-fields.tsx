import React, { ReactNode } from 'react';

import MapGemFormFieldsProps from '../../types/map-gem-form-fields-props';
import { parseNumberOption } from '../../utils/parse-map-gem-dropdown-value';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import FieldWrapper from 'ui/forms/field-wrapper';
import TextAreaField from 'ui/forms/text-area-field';
import Input from 'ui/input/input';

const MapGemIdentityFields = ({
  state,
  errors,
  form_options: formOptions,
  on_change: onChange,
}: MapGemFormFieldsProps): ReactNode => {
  const gameMapItems: DropdownItem[] = formOptions.game_maps.map((gameMap) => ({
    label: gameMap.name,
    value: gameMap.id,
  }));

  return (
    <div className="space-y-4">
      <FieldWrapper
        id="map-gem-game-map"
        label="Game Map"
        required
        error={errors.game_map_id}
      >
        {(describedBy) => (
          <Dropdown
            id="map-gem-game-map"
            aria_label="Game Map"
            aria_described_by={describedBy}
            aria_invalid={!!errors.game_map_id}
            aria_required
            searchable
            items={gameMapItems}
            pre_selected_item={gameMapItems.find(
              (item) => item.value === state.game_map_id
            )}
            on_select={(item) =>
              onChange('game_map_id', parseNumberOption(item.value))
            }
            selection_placeholder="Select a Game Map"
          />
        )}
      </FieldWrapper>

      <FieldWrapper id="map-gem-name" label="Name" required error={errors.name}>
        {(describedBy) => (
          <Input
            id="map-gem-name"
            value={state.name}
            on_change={(value) => onChange('name', value)}
            described_by={describedBy}
            invalid={!!errors.name}
            required
          />
        )}
      </FieldWrapper>

      <TextAreaField
        id="map-gem-description"
        label="Description"
        value={state.description}
        on_change={(value) => onChange('description', value)}
        error={errors.description}
      />
    </div>
  );
};

export default MapGemIdentityFields;
