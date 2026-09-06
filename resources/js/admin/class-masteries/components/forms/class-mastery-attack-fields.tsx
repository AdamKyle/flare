import React, { ReactNode } from 'react';

import { attackTypeLabel } from '../../enums/attack-type';
import ClassMasteryFormFieldsProps from '../../types/class-mastery-form-fields-props';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import FieldWrapper from 'ui/forms/field-wrapper';
import NumberField from 'ui/forms/number-field';

const ClassMasteryAttackFields = ({
  state,
  errors,
  form_options: formOptions,
  on_change: onChange,
}: ClassMasteryFormFieldsProps): ReactNode => {
  const attackTypeItems: DropdownItem[] = formOptions.attack_types.map(
    (attackType) => ({ label: attackTypeLabel(attackType), value: attackType })
  );

  return (
    <div className="space-y-4">
      <p className="text-glacier-700 dark:text-glacier-300 text-sm">
        Leave Specialty Damage empty or zero for a passive Class Mastery.
        Populate it for an attack Class Mastery.
      </p>

      <NumberField
        id="class-mastery-specialty-damage"
        label="Specialty Damage"
        value={state.specialty_damage}
        on_change={(value) => onChange('specialty_damage', value)}
        error={errors.specialty_damage}
      />

      <NumberField
        id="class-mastery-increase-specialty-damage-per-level"
        label="Increase Specialty Damage Per Level"
        value={state.increase_specialty_damage_per_level}
        on_change={(value) =>
          onChange('increase_specialty_damage_per_level', value)
        }
        error={errors.increase_specialty_damage_per_level}
      />

      <NumberField
        id="class-mastery-specialty-damage-uses-damage-stat-amount"
        label="Specialty Damage Uses Damage Stat Amount"
        value={state.specialty_damage_uses_damage_stat_amount}
        on_change={(value) =>
          onChange('specialty_damage_uses_damage_stat_amount', value)
        }
        error={errors.specialty_damage_uses_damage_stat_amount}
      />

      <FieldWrapper
        id="class-mastery-attack-type-required"
        label="Attack Type Required"
        error={errors.attack_type_required}
      >
        {(describedBy) => (
          <Dropdown
            id="class-mastery-attack-type-required"
            aria_label="Attack Type Required"
            aria_described_by={describedBy}
            items={attackTypeItems}
            pre_selected_item={attackTypeItems.find(
              (item) => item.value === state.attack_type_required
            )}
            on_select={(item) =>
              onChange('attack_type_required', String(item.value))
            }
            on_clear={() => onChange('attack_type_required', null)}
            selection_placeholder="None"
          />
        )}
      </FieldWrapper>
    </div>
  );
};

export default ClassMasteryAttackFields;
