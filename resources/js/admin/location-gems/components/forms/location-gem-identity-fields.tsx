import React, { ReactNode } from 'react';

import LocationGemFormFieldsProps from '../../types/location-gem-form-fields-props';
import { buildLocationGemOptionLabel } from '../../utils/build-location-gem-option-label';
import { parseNumberOption } from '../../utils/parse-location-gem-dropdown-value';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import FieldWrapper from 'ui/forms/field-wrapper';
import TextAreaField from 'ui/forms/text-area-field';
import Input from 'ui/input/input';

const LocationGemIdentityFields = ({
  state,
  errors,
  form_options: formOptions,
  on_change: onChange,
}: LocationGemFormFieldsProps): ReactNode => {
  const locationItems: DropdownItem[] = formOptions.locations.map(
    (location) => ({
      label: buildLocationGemOptionLabel(location),
      value: location.id,
    })
  );

  return (
    <div className="space-y-4">
      <FieldWrapper
        id="location-gem-location"
        label="Location"
        required
        error={errors.location_id}
      >
        {(describedBy) => (
          <Dropdown
            id="location-gem-location"
            aria_label="Location"
            aria_described_by={describedBy}
            aria_invalid={!!errors.location_id}
            aria_required
            searchable
            items={locationItems}
            pre_selected_item={locationItems.find(
              (item) => item.value === state.location_id
            )}
            on_select={(item) =>
              onChange('location_id', parseNumberOption(item.value))
            }
            selection_placeholder="Select a Location"
          />
        )}
      </FieldWrapper>

      <FieldWrapper
        id="location-gem-name"
        label="Name"
        required
        error={errors.name}
      >
        {(describedBy) => (
          <Input
            id="location-gem-name"
            value={state.name}
            on_change={(value) => onChange('name', value)}
            described_by={describedBy}
            invalid={!!errors.name}
            required
          />
        )}
      </FieldWrapper>

      <TextAreaField
        id="location-gem-description"
        label="Description"
        value={state.description}
        on_change={(value) => onChange('description', value)}
        error={errors.description}
      />
    </div>
  );
};

export default LocationGemIdentityFields;
