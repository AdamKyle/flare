import React, { ReactNode } from 'react';

import GuideQuestFormFieldsProps from './types/guide-quest-form-fields-props';
import {
  guideQuestDropdownItems,
  selectedGuideQuestOption,
} from '../utils/guide-quest-form-options';

import Dropdown from 'ui/drop-down/drop-down';
import FieldWrapper from 'ui/forms/field-wrapper';
import NumberField from 'ui/forms/number-field';

const ManageGuideQuestsRequiredQuestAndPlaneAttributes = ({
  state,
  errors,
  form_options: formOptions,
  on_change: onChange,
}: GuideQuestFormFieldsProps): ReactNode => {
  const mapItems = guideQuestDropdownItems(formOptions.game_maps);
  const questItems = guideQuestDropdownItems(formOptions.quests);
  const questItemItems = guideQuestDropdownItems(formOptions.quest_items);

  const renderDropdown = (
    id: string,
    label: string,
    field:
      | 'required_game_map_id'
      | 'be_on_game_map'
      | 'required_quest_id'
      | 'required_quest_item_id'
      | 'secondary_quest_item_id',
    items: typeof mapItems
  ) => (
    <FieldWrapper id={id} label={label} error={errors[field]}>
      {(describedBy) => (
        <Dropdown
          id={id}
          items={items}
          pre_selected_item={selectedGuideQuestOption(items, state[field])}
          selection_placeholder="No requirement"
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
      {renderDropdown(
        'guide-quest-required-game-map',
        'Required Plane Access',
        'required_game_map_id',
        mapItems
      )}
      {renderDropdown(
        'guide-quest-be-on-game-map',
        'Physically Be on plane',
        'be_on_game_map',
        mapItems
      )}
      {renderDropdown(
        'guide-quest-required-quest',
        'Required Quest Completed',
        'required_quest_id',
        questItems
      )}
      {renderDropdown(
        'guide-quest-required-quest-item',
        'Required Quest Item',
        'required_quest_item_id',
        questItemItems
      )}
      {renderDropdown(
        'guide-quest-secondary-quest-item',
        'Required Secondary Quest Item',
        'secondary_quest_item_id',
        questItemItems
      )}
      <NumberField
        id="guide-quest-required-event-goal-participation"
        label="Required Event Goal Kill Count"
        value={state.required_event_goal_participation}
        on_change={(value) =>
          onChange('required_event_goal_participation', value)
        }
        error={errors.required_event_goal_participation}
      />
      <NumberField
        id="guide-quest-required-event-goal-crafting-participation"
        label="Required Event Goal Crafting Count"
        value={state.required_event_goal_crafting_participation}
        on_change={(value) =>
          onChange('required_event_goal_crafting_participation', value)
        }
        error={errors.required_event_goal_crafting_participation}
      />
      <NumberField
        id="guide-quest-required-event-goal-enchanting-participation"
        label="Required Event Goal Enchanting Count"
        value={state.required_event_goal_enchanting_participation}
        on_change={(value) =>
          onChange('required_event_goal_enchanting_participation', value)
        }
        error={errors.required_event_goal_enchanting_participation}
      />
    </div>
  );
};

export default ManageGuideQuestsRequiredQuestAndPlaneAttributes;
