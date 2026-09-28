import React, { ReactNode } from 'react';

import { gemAbilityTypeLabel } from '../../../../game/reusable-components/gem-ability/enums/gem-ability-type';
import GemAbilityFormFieldsProps from '../../types/gem-ability-form-fields-props';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import CheckboxField from 'ui/forms/checkbox-field';
import FieldWrapper from 'ui/forms/field-wrapper';
import TextAreaField from 'ui/forms/text-area-field';
import Input from 'ui/input/input';

const GemAbilityIdentityFields = ({
  state,
  errors,
  form_options: formOptions,
  on_change: onChange,
}: GemAbilityFormFieldsProps): ReactNode => {
  const abilityTypeItems: DropdownItem[] = formOptions.ability_types.map(
    (abilityType) => ({
      label: gemAbilityTypeLabel(abilityType),
      value: abilityType,
    })
  );

  const handleAbilityTypeSelect = (item: DropdownItem): void => {
    const abilityType =
      formOptions.ability_types.find((candidate) => candidate === item.value) ??
      null;

    onChange('ability_type', abilityType);
    onChange('effect_type', null);
  };

  return (
    <div className="space-y-4">
      <FieldWrapper
        id="gem-ability-name"
        label="Name"
        required
        error={errors.name}
      >
        {(describedBy) => (
          <Input
            id="gem-ability-name"
            value={state.name}
            on_change={(value) => onChange('name', value)}
            described_by={describedBy}
            invalid={!!errors.name}
            required
          />
        )}
      </FieldWrapper>

      <TextAreaField
        id="gem-ability-description"
        label="Description"
        value={state.description}
        on_change={(value) => onChange('description', value)}
        error={errors.description}
        required
      />

      <FieldWrapper
        id="gem-ability-ability-type"
        label="Ability Type"
        required
        error={errors.ability_type}
      >
        {(describedBy) => (
          <Dropdown
            id="gem-ability-ability-type"
            aria_label="Ability Type"
            aria_described_by={describedBy}
            aria_invalid={!!errors.ability_type}
            aria_required
            items={abilityTypeItems}
            pre_selected_item={abilityTypeItems.find(
              (item) => item.value === state.ability_type
            )}
            on_select={handleAbilityTypeSelect}
            selection_placeholder="Select Active or Passive"
          />
        )}
      </FieldWrapper>

      <CheckboxField
        id="gem-ability-enabled"
        label="Enabled"
        description="Disabled abilities are never rolled onto newly crafted Tier 1 Gems. Gems that already have this ability keep it."
        checked={state.enabled}
        on_change={(checked) => onChange('enabled', checked)}
        error={errors.enabled}
      />
    </div>
  );
};

export default GemAbilityIdentityFields;
