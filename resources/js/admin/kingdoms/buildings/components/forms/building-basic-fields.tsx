import React, { ReactNode } from 'react';

import BuildingFormFieldsProps from '../../types/building-form-fields-props';
import { parseBuildingDropdownValue } from '../../utils/parse-building-dropdown-value';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import CheckboxField from 'ui/forms/checkbox-field';
import FieldWrapper from 'ui/forms/field-wrapper';
import NumberField from 'ui/forms/number-field';
import TextAreaField from 'ui/forms/text-area-field';
import Input from 'ui/input/input';

const BuildingBasicFields = ({
  state,
  errors,
  form_options: formOptions,
  on_change: onChange,
}: BuildingFormFieldsProps): ReactNode => {
  const passiveSkillItems: DropdownItem[] = formOptions.passive_skills.map(
    (passiveSkill) => ({
      label: passiveSkill.name,
      value: passiveSkill.id,
    })
  );

  return (
    <div className="space-y-4">
      <FieldWrapper
        id="building-name"
        label="Name"
        required
        error={errors.name}
      >
        {(describedBy) => (
          <Input
            id="building-name"
            value={state.name}
            on_change={(value) => onChange('name', value)}
            described_by={describedBy}
            invalid={!!errors.name}
            required
          />
        )}
      </FieldWrapper>

      <TextAreaField
        id="building-description"
        label="Description"
        value={state.description}
        on_change={(value) => onChange('description', value)}
        error={errors.description}
        required
      />

      <div className="grid gap-4 md:grid-cols-2">
        <NumberField
          id="building-max-level"
          label="Max Level"
          value={state.max_level}
          on_change={(value) => onChange('max_level', value)}
          error={errors.max_level}
          required
        />
        <NumberField
          id="building-required-population"
          label="Base Required Population"
          value={state.required_population}
          on_change={(value) => onChange('required_population', value)}
          error={errors.required_population}
          required
        />
        <NumberField
          id="building-base-durability"
          label="Base Durability"
          value={state.base_durability}
          on_change={(value) => onChange('base_durability', value)}
          error={errors.base_durability}
          required
        />
        <NumberField
          id="building-base-defence"
          label="Base Defence"
          value={state.base_defence}
          on_change={(value) => onChange('base_defence', value)}
          error={errors.base_defence}
          required
        />
      </div>

      <div className="grid gap-x-4 md:grid-cols-2">
        <CheckboxField
          id="building-is-walls"
          label="Wall"
          checked={state.is_walls}
          on_change={(checked) => onChange('is_walls', checked)}
        />
        <CheckboxField
          id="building-is-farm"
          label="Farm"
          checked={state.is_farm}
          on_change={(checked) => onChange('is_farm', checked)}
        />
        <CheckboxField
          id="building-is-church"
          label="Church"
          checked={state.is_church}
          on_change={(checked) => onChange('is_church', checked)}
        />
        <CheckboxField
          id="building-is-resource-building"
          label="Resource Building"
          checked={state.is_resource_building}
          on_change={(checked) => onChange('is_resource_building', checked)}
          description="Only kept when the Building increases at least one resource."
        />
        <CheckboxField
          id="building-is-special"
          label="Special"
          checked={state.is_special}
          on_change={(checked) => onChange('is_special', checked)}
        />
        <CheckboxField
          id="building-is-locked"
          label="Locked"
          checked={state.is_locked}
          on_change={(checked) => onChange('is_locked', checked)}
        />
      </div>

      <FieldWrapper
        id="building-passive-skill"
        label="Passive Skill Required"
        error={errors.passive_skill_id}
      >
        {(describedBy) => (
          <Dropdown
            id="building-passive-skill"
            aria_label="Passive Skill Required"
            aria_described_by={describedBy}
            aria_invalid={!!errors.passive_skill_id}
            searchable
            items={passiveSkillItems}
            pre_selected_item={passiveSkillItems.find(
              (item) => item.value === state.passive_skill_id
            )}
            on_select={(item) =>
              onChange(
                'passive_skill_id',
                parseBuildingDropdownValue(item.value)
              )
            }
            on_clear={() => onChange('passive_skill_id', null)}
            selection_placeholder="None"
          />
        )}
      </FieldWrapper>

      <NumberField
        id="building-level-required"
        label="Passive Level Required"
        value={state.level_required}
        on_change={(value) => onChange('level_required', value)}
        error={errors.level_required}
      />
    </div>
  );
};

export default BuildingBasicFields;
