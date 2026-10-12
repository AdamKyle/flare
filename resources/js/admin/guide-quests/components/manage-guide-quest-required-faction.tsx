import React, { ReactNode } from 'react';

import GuideQuestFormFieldsProps from './types/guide-quest-form-fields-props';
import {
  guideQuestDropdownItems,
  selectedGuideQuestOption,
} from '../utils/guide-quest-form-options';

import Dropdown from 'ui/drop-down/drop-down';
import FieldWrapper from 'ui/forms/field-wrapper';
import NumberField from 'ui/forms/number-field';

const ManageGuideQuestRequiredFaction = ({
  state,
  errors,
  form_options: formOptions,
  on_change: onChange,
}: GuideQuestFormFieldsProps): ReactNode => {
  const factionItems = guideQuestDropdownItems(formOptions.faction_maps);

  return (
    <div className="space-y-4">
      <FieldWrapper
        id="guide-quest-required-faction"
        label="Required Faction"
        error={errors.required_faction_id}
      >
        {(describedBy) => (
          <Dropdown
            id="guide-quest-required-faction"
            items={factionItems}
            pre_selected_item={selectedGuideQuestOption(
              factionItems,
              state.required_faction_id
            )}
            selection_placeholder="No required faction"
            on_select={(item) =>
              onChange('required_faction_id', Number(item.value))
            }
            on_clear={() => onChange('required_faction_id', null)}
            aria_described_by={describedBy}
            aria_invalid={!!errors.required_faction_id}
          />
        )}
      </FieldWrapper>
      <NumberField
        id="guide-quest-required-faction-level"
        label="Required Faction Level"
        value={state.required_faction_level}
        on_change={(value) => onChange('required_faction_level', value)}
        error={errors.required_faction_level}
      />
      <NumberField
        id="guide-quest-required-fame-level"
        label="Required Fame Level"
        value={state.required_fame_level}
        on_change={(value) => onChange('required_fame_level', value)}
        error={errors.required_fame_level}
      />
    </div>
  );
};

export default ManageGuideQuestRequiredFaction;
