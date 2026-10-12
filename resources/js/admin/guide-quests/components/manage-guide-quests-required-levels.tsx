import React, { ReactNode } from 'react';

import GuideQuestFormFieldsProps from './types/guide-quest-form-fields-props';
import {
  guideQuestDropdownItems,
  selectedGuideQuestOption,
} from '../utils/guide-quest-form-options';

import Dropdown from 'ui/drop-down/drop-down';
import FieldWrapper from 'ui/forms/field-wrapper';
import NumberField from 'ui/forms/number-field';

const ManageGuideQuestsRequiredLevels = ({
  state,
  errors,
  form_options: formOptions,
  on_change: onChange,
}: GuideQuestFormFieldsProps): ReactNode => {
  const skillItems = guideQuestDropdownItems(formOptions.game_skills);
  const skillTypeItems = formOptions.skill_types.map((label, value) => ({
    value,
    label,
  }));

  const renderSkill = (
    id: string,
    label: string,
    field:
      'required_training_skill' | 'required_skill' | 'required_secondary_skill',
    value: number | null
  ) => (
    <FieldWrapper id={id} label={label} error={errors[field]}>
      {(describedBy) => (
        <Dropdown
          id={id}
          items={skillItems}
          pre_selected_item={selectedGuideQuestOption(skillItems, value)}
          selection_placeholder="No required skill"
          on_select={(item) => onChange(field, Number(item.value))}
          on_clear={() => onChange(field, null)}
          aria_described_by={describedBy}
          aria_invalid={!!errors[field]}
        />
      )}
    </FieldWrapper>
  );

  return (
    <div className="space-y-4">
      <NumberField
        id="guide-quest-required-level"
        label="Required Character Level"
        value={state.required_level}
        on_change={(value) => onChange('required_level', value)}
        error={errors.required_level}
      />
      <NumberField
        id="guide-quest-required-exploration-hours"
        label="Required Exploration Hours"
        value={state.required_exploration_hours}
        on_change={(value) => onChange('required_exploration_hours', value)}
        error={errors.required_exploration_hours}
      />
      {renderSkill(
        'guide-quest-required-training-skill',
        'Required Training Skill',
        'required_training_skill',
        state.required_training_skill
      )}
      <NumberField
        id="guide-quest-required-training-xp-sacrifice"
        label="Required Training XP Sacrifice"
        description="Enter a decimal fraction; 0.10 equals 10%."
        min={0}
        max={1}
        value={state.required_training_xp_sacrifice}
        on_change={(value) => onChange('required_training_xp_sacrifice', value)}
        error={errors.required_training_xp_sacrifice}
      />
      <NumberField
        id="guide-quest-required-reincarnation-amount"
        label="Required Reincarnation Amount"
        value={state.required_reincarnation_amount}
        on_change={(value) => onChange('required_reincarnation_amount', value)}
        error={errors.required_reincarnation_amount}
      />
      {renderSkill(
        'guide-quest-required-skill',
        'Required Skill',
        'required_skill',
        state.required_skill
      )}
      <NumberField
        id="guide-quest-required-skill-level"
        label="Required Skill Level"
        value={state.required_skill_level}
        on_change={(value) => onChange('required_skill_level', value)}
        error={errors.required_skill_level}
      />
      {renderSkill(
        'guide-quest-required-secondary-skill',
        'Secondary Required Skill',
        'required_secondary_skill',
        state.required_secondary_skill
      )}
      <NumberField
        id="guide-quest-required-secondary-skill-level"
        label="Secondary Required Skill Level"
        value={state.required_secondary_skill_level}
        on_change={(value) => onChange('required_secondary_skill_level', value)}
        error={errors.required_secondary_skill_level}
      />
      <FieldWrapper
        id="guide-quest-required-skill-type"
        label="Required Skill Type"
        error={errors.required_skill_type}
      >
        {(describedBy) => (
          <Dropdown
            id="guide-quest-required-skill-type"
            items={skillTypeItems}
            pre_selected_item={selectedGuideQuestOption(
              skillTypeItems,
              state.required_skill_type
            )}
            selection_placeholder="No required skill type"
            on_select={(item) =>
              onChange('required_skill_type', Number(item.value))
            }
            on_clear={() => onChange('required_skill_type', null)}
            aria_described_by={describedBy}
            aria_invalid={!!errors.required_skill_type}
          />
        )}
      </FieldWrapper>
      <NumberField
        id="guide-quest-required-skill-type-level"
        label="Required Skill Type Level"
        value={state.required_skill_type_level}
        on_change={(value) => onChange('required_skill_type_level', value)}
        error={errors.required_skill_type_level}
      />
    </div>
  );
};

export default ManageGuideQuestsRequiredLevels;
