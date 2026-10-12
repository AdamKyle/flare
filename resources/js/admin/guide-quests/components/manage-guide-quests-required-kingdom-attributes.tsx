import React, { ReactNode } from 'react';

import GuideQuestFormFieldsProps from './types/guide-quest-form-fields-props';
import {
  guideQuestDropdownItems,
  selectedGuideQuestOption,
} from '../utils/guide-quest-form-options';

import Dropdown from 'ui/drop-down/drop-down';
import FieldWrapper from 'ui/forms/field-wrapper';
import NumberField from 'ui/forms/number-field';

const ManageGuideQuestsRequiredKingdomAttributes = ({
  state,
  errors,
  form_options: formOptions,
  on_change: onChange,
}: GuideQuestFormFieldsProps): ReactNode => {
  const buildingItems = guideQuestDropdownItems(formOptions.kingdom_buildings);
  const passiveItems = guideQuestDropdownItems(formOptions.passives);

  return (
    <div className="space-y-4">
      <NumberField
        id="guide-quest-required-kingdoms"
        label="Total Kingdoms Owned"
        value={state.required_kingdoms}
        on_change={(value) => onChange('required_kingdoms', value)}
        error={errors.required_kingdoms}
      />
      <NumberField
        id="guide-quest-required-kingdom-level"
        label="Required Kingdom Level"
        description="This is the total level for all kingdoms you own."
        value={state.required_kingdom_level}
        on_change={(value) => onChange('required_kingdom_level', value)}
        error={errors.required_kingdom_level}
      />
      <FieldWrapper
        id="guide-quest-required-kingdom-building"
        label="Required Kingdom Building"
        error={errors.required_kingdom_building_id}
      >
        {(describedBy) => (
          <Dropdown
            id="guide-quest-required-kingdom-building"
            items={buildingItems}
            pre_selected_item={selectedGuideQuestOption(
              buildingItems,
              state.required_kingdom_building_id
            )}
            selection_placeholder="No required building"
            on_select={(item) =>
              onChange('required_kingdom_building_id', String(item.value))
            }
            on_clear={() => onChange('required_kingdom_building_id', null)}
            aria_described_by={describedBy}
            aria_invalid={!!errors.required_kingdom_building_id}
          />
        )}
      </FieldWrapper>
      <NumberField
        id="guide-quest-required-kingdom-building-level"
        label="Required Kingdom Building Level"
        value={state.required_kingdom_building_level}
        on_change={(value) =>
          onChange('required_kingdom_building_level', value)
        }
        error={errors.required_kingdom_building_level}
      />
      <NumberField
        id="guide-quest-required-kingdom-units"
        label="Required Kingdom Units"
        description="This is the total units across all kingdoms."
        value={state.required_kingdom_units}
        on_change={(value) => onChange('required_kingdom_units', value)}
        error={errors.required_kingdom_units}
      />
      <FieldWrapper
        id="guide-quest-required-passive"
        label="Required Kingdom Passive"
        error={errors.required_passive_skill}
      >
        {(describedBy) => (
          <Dropdown
            id="guide-quest-required-passive"
            items={passiveItems}
            pre_selected_item={selectedGuideQuestOption(
              passiveItems,
              state.required_passive_skill
            )}
            selection_placeholder="No required passive"
            on_select={(item) =>
              onChange('required_passive_skill', Number(item.value))
            }
            on_clear={() => onChange('required_passive_skill', null)}
            aria_described_by={describedBy}
            aria_invalid={!!errors.required_passive_skill}
          />
        )}
      </FieldWrapper>
      <NumberField
        id="guide-quest-required-passive-level"
        label="Required Kingdom Passive Level"
        value={state.required_passive_level}
        on_change={(value) => onChange('required_passive_level', value)}
        error={errors.required_passive_level}
      />
    </div>
  );
};

export default ManageGuideQuestsRequiredKingdomAttributes;
