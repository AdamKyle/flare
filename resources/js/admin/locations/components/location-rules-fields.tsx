import React, { ReactNode } from 'react';

import { LOCATION_PIN_LABELS, isLocationPin } from '../enums/location-pin';
import { LOCATION_TYPE_LABELS, isLocationType } from '../enums/location-type';
import LocationRulesFieldsProps from '../types/location-rules-fields-props';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import FieldWrapper from 'ui/forms/field-wrapper';
import NumberField from 'ui/forms/number-field';

const LocationRulesFields = ({
  state,
  errors,
  form_options,
  on_change,
}: LocationRulesFieldsProps): ReactNode => {
  const locationTypeItems: DropdownItem[] = form_options.location_types.map(
    (locationType) => ({
      label: LOCATION_TYPE_LABELS[locationType],
      value: locationType,
    })
  );
  const pinItems: DropdownItem[] = form_options.special_pins.map(
    (locationPin) => ({
      label: LOCATION_PIN_LABELS[locationPin],
      value: locationPin,
    })
  );
  const questItemItems: DropdownItem[] = form_options.quest_items.map(
    (option) => ({
      label: option.label,
      value: option.value,
    })
  );

  return (
    <div className="space-y-4">
      <FieldWrapper
        id="location-type"
        label="Location Type"
        error={errors.type}
      >
        {(describedBy) => (
          <Dropdown
            id="location-type"
            aria_label="Location Type"
            aria_described_by={describedBy}
            aria_invalid={!!errors.type}
            items={locationTypeItems}
            pre_selected_item={locationTypeItems.find(
              (item) => item.value === state.type
            )}
            on_select={(item) => {
              if (!isLocationType(item.value)) {
                return;
              }

              on_change('type', item.value);
            }}
            on_clear={() => on_change('type', null)}
            selection_placeholder="No special type"
          />
        )}
      </FieldWrapper>

      <FieldWrapper
        id="location-pin"
        label="Special Pin"
        error={errors.pin_css_class}
      >
        {(describedBy) => (
          <Dropdown
            id="location-pin"
            aria_label="Special Pin"
            aria_described_by={describedBy}
            aria_invalid={!!errors.pin_css_class}
            items={pinItems}
            pre_selected_item={pinItems.find(
              (item) => item.value === state.pin_css_class
            )}
            on_select={(item) => {
              if (!isLocationPin(item.value)) {
                return;
              }

              on_change('pin_css_class', item.value);
            }}
            on_clear={() => on_change('pin_css_class', null)}
            selection_placeholder="Default pin"
          />
        )}
      </FieldWrapper>

      <FieldWrapper
        id="location-required-quest-item"
        label="Required Quest Item"
        error={errors.required_quest_item_id}
      >
        {(describedBy) => (
          <Dropdown
            id="location-required-quest-item"
            aria_label="Required Quest Item"
            aria_described_by={describedBy}
            aria_invalid={!!errors.required_quest_item_id}
            items={questItemItems}
            pre_selected_item={questItemItems.find(
              (item) => item.value === state.required_quest_item_id
            )}
            on_select={(item) =>
              on_change('required_quest_item_id', Number(item.value))
            }
            on_clear={() => on_change('required_quest_item_id', null)}
            selection_placeholder="None"
          />
        )}
      </FieldWrapper>

      <FieldWrapper
        id="location-quest-reward-item"
        label="Quest Reward Item"
        error={errors.quest_reward_item_id}
      >
        {(describedBy) => (
          <Dropdown
            id="location-quest-reward-item"
            aria_label="Quest Reward Item"
            aria_described_by={describedBy}
            aria_invalid={!!errors.quest_reward_item_id}
            items={questItemItems}
            pre_selected_item={questItemItems.find(
              (item) => item.value === state.quest_reward_item_id
            )}
            on_select={(item) =>
              on_change('quest_reward_item_id', Number(item.value))
            }
            on_clear={() => on_change('quest_reward_item_id', null)}
            selection_placeholder="None"
          />
        )}
      </FieldWrapper>

      <NumberField
        id="location-hours-to-drop"
        label="Hours until quest-item drop"
        value={state.hours_to_drop}
        on_change={(value) => on_change('hours_to_drop', value)}
        error={errors.hours_to_drop}
        min={0}
      />

      <NumberField
        id="location-minutes-between-delve-fights"
        label="Minutes between Delve fights"
        value={state.minutes_between_delve_fights}
        on_change={(value) => on_change('minutes_between_delve_fights', value)}
        error={errors.minutes_between_delve_fights}
        min={0}
      />
    </div>
  );
};

export default LocationRulesFields;
