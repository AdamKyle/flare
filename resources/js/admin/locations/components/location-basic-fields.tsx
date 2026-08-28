import React, { ReactNode } from 'react';

import LocationBasicFieldsProps from '../types/location-basic-fields-props';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import CheckboxField from 'ui/forms/checkbox-field';
import FieldWrapper from 'ui/forms/field-wrapper';
import TextAreaField from 'ui/forms/text-area-field';
import Input from 'ui/input/input';

const LocationBasicFields = ({
  game_map_name,
  state,
  errors,
  coordinates,
  on_change,
}: LocationBasicFieldsProps): ReactNode => {
  const xItems: DropdownItem[] = coordinates.x.map((value) => ({
    label: String(value),
    value,
  }));
  const yItems: DropdownItem[] = coordinates.y.map((value) => ({
    label: String(value),
    value,
  }));

  return (
    <div className="space-y-4">
      <div>
        <p className="text-glacier-900 dark:text-glacier-100 mb-1 text-sm font-medium">
          Game Map
        </p>
        <p className="text-glacier-600 dark:text-glacier-300 text-sm">
          {game_map_name}
        </p>
      </div>

      <FieldWrapper
        id="location-name"
        label="Name"
        required
        error={errors.name}
      >
        {(describedBy) => (
          <Input
            id="location-name"
            value={state.name}
            on_change={(value) => on_change('name', value)}
            described_by={describedBy}
            invalid={!!errors.name}
            required
          />
        )}
      </FieldWrapper>

      <TextAreaField
        id="location-description"
        label="Description"
        required
        value={state.description}
        on_change={(value) => on_change('description', value)}
        error={errors.description}
      />

      <CheckboxField
        id="location-is-port"
        label="Is Port"
        checked={state.is_port}
        on_change={(value) => on_change('is_port', value)}
      />

      <CheckboxField
        id="location-can-players-enter"
        label="Can players enter"
        checked={state.can_players_enter}
        on_change={(value) => on_change('can_players_enter', value)}
      />

      <CheckboxField
        id="location-can-auto-battle"
        label="Can auto battle"
        checked={state.can_auto_battle}
        on_change={(value) => on_change('can_auto_battle', value)}
      />

      <div className="grid gap-4 md:grid-cols-2">
        <FieldWrapper
          id="location-x"
          label="X Coordinate"
          required
          error={errors.x}
        >
          {(describedBy) => (
            <Dropdown
              id="location-x"
              aria_label="X Coordinate"
              aria_described_by={describedBy}
              aria_invalid={!!errors.x}
              aria_required
              items={xItems}
              pre_selected_item={xItems.find((item) => item.value === state.x)}
              on_select={(item) => on_change('x', Number(item.value))}
              selection_placeholder="Select an X coordinate"
            />
          )}
        </FieldWrapper>

        <FieldWrapper
          id="location-y"
          label="Y Coordinate"
          required
          error={errors.y}
        >
          {(describedBy) => (
            <Dropdown
              id="location-y"
              aria_label="Y Coordinate"
              aria_described_by={describedBy}
              aria_invalid={!!errors.y}
              aria_required
              items={yItems}
              pre_selected_item={yItems.find((item) => item.value === state.y)}
              on_select={(item) => on_change('y', Number(item.value))}
              selection_placeholder="Select a Y coordinate"
            />
          )}
        </FieldWrapper>
      </div>
    </div>
  );
};

export default LocationBasicFields;
