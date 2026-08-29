import React, { ReactNode } from 'react';

import GameMapAccessFieldsProps from '../../types/game-map-access-fields-props';
import {
  GAME_MAP_EVENT_TYPE_LABELS,
  isGameMapEventType,
} from '../../enums/game-map-event-type';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import CheckboxField from 'ui/forms/checkbox-field';
import FieldWrapper from 'ui/forms/field-wrapper';

const GameMapAccessFields = ({
  state,
  errors,
  form_options: formOptions,
  on_change: onChange,
}: GameMapAccessFieldsProps): ReactNode => {
  const locationItems: DropdownItem[] = formOptions.locations.map(
    (location) => ({
      label: location.name,
      value: location.id,
    })
  );
  const eventTypeItems: DropdownItem[] = formOptions.event_types.map(
    (eventType) => ({
      label: GAME_MAP_EVENT_TYPE_LABELS[eventType],
      value: eventType,
    })
  );

  return (
    <div className="space-y-4">
      <FieldWrapper
        id="game-map-required-location"
        label="Required Location"
        error={errors.required_location_id}
      >
        {(describedBy) => (
          <Dropdown
            id="game-map-required-location"
            aria_label="Required Location"
            aria_described_by={describedBy}
            aria_invalid={!!errors.required_location_id}
            items={locationItems}
            pre_selected_item={locationItems.find(
              (item) => item.value === state.required_location_id
            )}
            on_select={(item) =>
              onChange('required_location_id', Number(item.value))
            }
            on_clear={() => onChange('required_location_id', null)}
            selection_placeholder="None"
          />
        )}
      </FieldWrapper>

      <CheckboxField
        id="game-map-can-traverse"
        label="Can Traverse"
        checked={state.can_traverse}
        on_change={(value) => onChange('can_traverse', value)}
      />

      <FieldWrapper
        id="game-map-only-during-event-type"
        label="Only For Event Type"
      >
        {(describedBy) => (
          <Dropdown
            id="game-map-only-during-event-type"
            aria_label="Only For Event Type"
            aria_described_by={describedBy}
            items={eventTypeItems}
            pre_selected_item={eventTypeItems.find(
              (item) => item.value === state.only_during_event_type
            )}
            on_select={(item) => {
              if (!isGameMapEventType(item.value)) {
                return;
              }

              onChange('only_during_event_type', item.value);
            }}
            on_clear={() => onChange('only_during_event_type', null)}
            selection_placeholder="Not event-restricted"
          />
        )}
      </FieldWrapper>
    </div>
  );
};

export default GameMapAccessFields;
