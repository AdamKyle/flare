import React, { ReactNode } from 'react';

import GuideQuestFormFieldsProps from './types/guide-quest-form-fields-props';
import {
  guideQuestDropdownItems,
  selectedGuideQuestOption,
} from '../utils/guide-quest-form-options';

import Dropdown from 'ui/drop-down/drop-down';
import FieldWrapper from 'ui/forms/field-wrapper';
import NumberField from 'ui/forms/number-field';

const ManageGuideQuestsRequiredItemAttributes = ({
  state,
  errors,
  form_options: formOptions,
  on_change: onChange,
}: GuideQuestFormFieldsProps): ReactNode => {
  const specialtyItems = guideQuestDropdownItems(
    formOptions.item_specialty_types
  );

  return (
    <div className="space-y-4">
      <FieldWrapper
        id="guide-quest-required-specialty-type"
        label="Required Specialty Type"
        error={errors.required_specialty_type}
      >
        {(describedBy) => (
          <Dropdown
            id="guide-quest-required-specialty-type"
            items={specialtyItems}
            pre_selected_item={selectedGuideQuestOption(
              specialtyItems,
              state.required_specialty_type
            )}
            selection_placeholder="No required specialty"
            on_select={(item) =>
              onChange('required_specialty_type', String(item.value))
            }
            on_clear={() => onChange('required_specialty_type', null)}
            aria_described_by={describedBy}
            aria_invalid={!!errors.required_specialty_type}
          />
        )}
      </FieldWrapper>
      <NumberField
        id="guide-quest-required-holy-stacks"
        label="Required Holy Stacks Level"
        value={state.required_holy_stacks}
        on_change={(value) => onChange('required_holy_stacks', value)}
        error={errors.required_holy_stacks}
      />
      <NumberField
        id="guide-quest-required-attached-gems"
        label="Required Gems Attached"
        value={state.required_attached_gems}
        on_change={(value) => onChange('required_attached_gems', value)}
        error={errors.required_attached_gems}
      />
    </div>
  );
};

export default ManageGuideQuestsRequiredItemAttributes;
