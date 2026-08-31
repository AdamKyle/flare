import React, { ReactNode } from 'react';

import QuestFormFieldsProps from '../../types/quest-form-fields-props';
import { parseNumberOption } from '../../utils/parse-quest-dropdown-value';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import CheckboxField from 'ui/forms/checkbox-field';
import FieldWrapper from 'ui/forms/field-wrapper';
import NumberField from 'ui/forms/number-field';

const QuestRewardsFields = ({
  state,
  errors,
  form_options: formOptions,
  on_change: onChange,
}: QuestFormFieldsProps): ReactNode => {
  const questItemItems: DropdownItem[] = formOptions.quest_items;
  const passiveItems: DropdownItem[] = formOptions.passive_skills;
  const skillTypeItems: DropdownItem[] = formOptions.skill_types.map(
    (value) => ({ label: `Skill Type ${value}`, value })
  );
  const featureItems: DropdownItem[] = formOptions.feature_types.map(
    (value) => ({ label: `Feature ${value}`, value })
  );

  return (
    <div className="space-y-4">
      <FieldWrapper id="quest-reward-item" label="Reward Item">
        {(describedBy) => (
          <Dropdown
            id="quest-reward-item"
            aria_label="Reward Item"
            aria_described_by={describedBy}
            searchable
            items={questItemItems}
            pre_selected_item={questItemItems.find(
              (item) => item.value === state.reward_item
            )}
            on_select={(item) =>
              onChange('reward_item', parseNumberOption(item.value))
            }
            on_clear={() => onChange('reward_item', null)}
            selection_placeholder="None"
          />
        )}
      </FieldWrapper>

      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <NumberField
          id="quest-reward-gold"
          label="Gold"
          value={state.reward_gold}
          on_change={(value) => onChange('reward_gold', value)}
          min={0}
          error={errors.reward_gold}
        />
        <NumberField
          id="quest-reward-gold-dust"
          label="Gold Dust"
          value={state.reward_gold_dust}
          on_change={(value) => onChange('reward_gold_dust', value)}
          min={0}
          error={errors.reward_gold_dust}
        />
        <NumberField
          id="quest-reward-shards"
          label="Shards"
          value={state.reward_shards}
          on_change={(value) => onChange('reward_shards', value)}
          min={0}
          error={errors.reward_shards}
        />
        <NumberField
          id="quest-reward-xp"
          label="XP"
          value={state.reward_xp}
          on_change={(value) => onChange('reward_xp', value)}
          min={0}
          error={errors.reward_xp}
        />
      </div>

      <CheckboxField
        id="quest-unlocks-skill"
        label="Unlocks Skill"
        checked={state.unlocks_skill}
        on_change={(value) => onChange('unlocks_skill', value)}
      />

      <FieldWrapper id="quest-unlocks-skill-type" label="Skill Type">
        {(describedBy) => (
          <Dropdown
            id="quest-unlocks-skill-type"
            aria_label="Skill Type"
            aria_described_by={describedBy}
            disabled={!state.unlocks_skill}
            items={skillTypeItems}
            pre_selected_item={skillTypeItems.find(
              (item) => item.value === state.unlocks_skill_type
            )}
            on_select={(item) =>
              onChange('unlocks_skill_type', parseNumberOption(item.value))
            }
            on_clear={() => onChange('unlocks_skill_type', null)}
            selection_placeholder="None"
          />
        )}
      </FieldWrapper>

      <FieldWrapper id="quest-unlocks-feature" label="Unlocks Feature">
        {(describedBy) => (
          <Dropdown
            id="quest-unlocks-feature"
            aria_label="Unlocks Feature"
            aria_described_by={describedBy}
            items={featureItems}
            pre_selected_item={featureItems.find(
              (item) => item.value === state.unlocks_feature
            )}
            on_select={(item) =>
              onChange('unlocks_feature', parseNumberOption(item.value))
            }
            on_clear={() => onChange('unlocks_feature', null)}
            selection_placeholder="None"
          />
        )}
      </FieldWrapper>

      <FieldWrapper id="quest-unlocks-passive" label="Unlocks Passive">
        {(describedBy) => (
          <Dropdown
            id="quest-unlocks-passive"
            aria_label="Unlocks Passive"
            aria_described_by={describedBy}
            searchable
            items={passiveItems}
            pre_selected_item={passiveItems.find(
              (item) => item.value === state.unlocks_passive_id
            )}
            on_select={(item) =>
              onChange('unlocks_passive_id', parseNumberOption(item.value))
            }
            on_clear={() => onChange('unlocks_passive_id', null)}
            selection_placeholder="None"
          />
        )}
      </FieldWrapper>
    </div>
  );
};

export default QuestRewardsFields;
