import React, { ReactNode } from 'react';

import UnitFormFieldsProps from '../../types/unit-form-fields-props';

import CheckboxField from 'ui/forms/checkbox-field';
import FieldWrapper from 'ui/forms/field-wrapper';
import NumberField from 'ui/forms/number-field';
import TextAreaField from 'ui/forms/text-area-field';
import Input from 'ui/input/input';

const UnitBasicCombatFields = ({
  state,
  errors,
  on_change: onChange,
}: UnitFormFieldsProps): ReactNode => {
  return (
    <div className="space-y-4">
      <FieldWrapper id="unit-name" label="Name" required error={errors.name}>
        {(describedBy) => (
          <Input
            id="unit-name"
            value={state.name}
            on_change={(value) => onChange('name', value)}
            described_by={describedBy}
            invalid={!!errors.name}
            required
          />
        )}
      </FieldWrapper>

      <TextAreaField
        id="unit-description"
        label="Description"
        value={state.description}
        on_change={(value) => onChange('description', value)}
        error={errors.description}
        required
      />

      <div className="grid gap-4 md:grid-cols-2">
        <NumberField
          id="unit-attack"
          label="Attack"
          value={state.attack}
          on_change={(value) => onChange('attack', value)}
          error={errors.attack}
          required
        />
        <NumberField
          id="unit-defence"
          label="Defence"
          value={state.defence}
          on_change={(value) => onChange('defence', value)}
          error={errors.defence}
          required
        />
        <NumberField
          id="unit-heal-percentage"
          label="Heal %"
          value={state.heal_percentage}
          on_change={(value) => onChange('heal_percentage', value)}
          error={errors.heal_percentage}
          description="A decimal fraction, e.g. 0.05 for 5%."
        />
        <NumberField
          id="unit-reduces-morale-by"
          label="Reduces Morale By %"
          value={state.reduces_morale_by}
          on_change={(value) => onChange('reduces_morale_by', value)}
          error={errors.reduces_morale_by}
          description="A decimal fraction, e.g. 0.05 for 5%."
        />
        <NumberField
          id="unit-time-to-recruit"
          label="Time to Recruit (seconds)"
          value={state.time_to_recruit}
          on_change={(value) => onChange('time_to_recruit', value)}
          error={errors.time_to_recruit}
          required
        />
      </div>

      <div className="grid gap-x-4 md:grid-cols-2">
        <CheckboxField
          id="unit-can-heal"
          label="Can Heal"
          checked={state.can_heal}
          on_change={(checked) => onChange('can_heal', checked)}
        />
        <CheckboxField
          id="unit-is-settler"
          label="Is Settler"
          checked={state.is_settler}
          on_change={(checked) => onChange('is_settler', checked)}
        />
        <CheckboxField
          id="unit-attacker"
          label="Attacker"
          checked={state.attacker}
          on_change={(checked) => onChange('attacker', checked)}
        />
        <CheckboxField
          id="unit-defender"
          label="Defender"
          checked={state.defender}
          on_change={(checked) => onChange('defender', checked)}
        />
        <CheckboxField
          id="unit-siege-weapon"
          label="Siege"
          checked={state.siege_weapon}
          on_change={(checked) => onChange('siege_weapon', checked)}
        />
        <CheckboxField
          id="unit-is-airship"
          label="Airship"
          checked={state.is_airship}
          on_change={(checked) => onChange('is_airship', checked)}
        />
        <CheckboxField
          id="unit-is-special"
          label="Special"
          checked={state.is_special}
          on_change={(checked) => onChange('is_special', checked)}
        />
        <CheckboxField
          id="unit-can-not-be-healed"
          label="Cannot Be Healed"
          checked={state.can_not_be_healed}
          on_change={(checked) => onChange('can_not_be_healed', checked)}
        />
      </div>
    </div>
  );
};

export default UnitBasicCombatFields;
