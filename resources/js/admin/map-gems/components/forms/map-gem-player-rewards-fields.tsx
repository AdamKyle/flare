import React, { ReactNode } from 'react';

import MapGemRangeField from './map-gem-range-field';
import MapGemFormFieldsProps from '../../types/map-gem-form-fields-props';
import { parseNumberOption } from '../../utils/parse-map-gem-dropdown-value';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import FieldWrapper from 'ui/forms/field-wrapper';

const MapGemPlayerRewardsFields = ({
  state,
  errors,
  form_options: formOptions,
  on_change: onChange,
}: MapGemFormFieldsProps): ReactNode => {
  const craftingSkillItems: DropdownItem[] = formOptions.crafting_skills.map(
    (skill) => ({
      label: skill.name,
      value: skill.id,
    })
  );

  return (
    <div className="space-y-4">
      <MapGemRangeField
        id="map-gem-character-xp-bonus-range"
        label="Character XP Bonus Range"
        value={state.character_xp_bonus_range}
        on_change={(value) => onChange('character_xp_bonus_range', value)}
        error={errors.character_xp_bonus_range}
      />
      <MapGemRangeField
        id="map-gem-character-class-rank-xp-bonus-range"
        label="Character Class Rank XP Bonus Range"
        value={state.character_class_rank_xp_bonus_range}
        on_change={(value) =>
          onChange('character_class_rank_xp_bonus_range', value)
        }
        error={errors.character_class_rank_xp_bonus_range}
      />
      <MapGemRangeField
        id="map-gem-kingdom-passive-training-reduction-range"
        label="Kingdom Passive Training Reduction Range"
        value={state.kingdom_passive_training_reduction_range}
        on_change={(value) =>
          onChange('kingdom_passive_training_reduction_range', value)
        }
        error={errors.kingdom_passive_training_reduction_range}
      />
      <MapGemRangeField
        id="map-gem-character-class-specialty-xp-gain-range"
        label="Character Class Specialty XP Gain Range"
        value={state.character_class_specialty_xp_gain_range}
        on_change={(value) =>
          onChange('character_class_specialty_xp_gain_range', value)
        }
        error={errors.character_class_specialty_xp_gain_range}
      />

      <FieldWrapper
        id="map-gem-crafting-skills"
        label="Crafting Skills"
        error={errors.crafting_skill_ids}
      >
        {(describedBy) => (
          <Dropdown
            id="map-gem-crafting-skills"
            aria_label="Crafting Skills"
            aria_described_by={describedBy}
            searchable
            items={craftingSkillItems}
            pre_selected_item={craftingSkillItems.find((item) =>
              state.crafting_skill_ids.includes(Number(item.value))
            )}
            on_select={(item) => {
              const value = parseNumberOption(item.value);

              if (value === null || state.crafting_skill_ids.includes(value)) {
                return;
              }

              onChange('crafting_skill_ids', [
                ...state.crafting_skill_ids,
                value,
              ]);
            }}
            selection_placeholder="Select crafting Skills"
          />
        )}
      </FieldWrapper>

      {state.crafting_skill_ids.length > 0 && (
        <ul className="flex flex-wrap gap-2">
          {state.crafting_skill_ids.map((skillId) => {
            const skillItem = craftingSkillItems.find(
              (item) => Number(item.value) === skillId
            );

            return (
              <li key={skillId}>
                <button
                  type="button"
                  onClick={() =>
                    onChange(
                      'crafting_skill_ids',
                      state.crafting_skill_ids.filter((id) => id !== skillId)
                    )
                  }
                  className="bg-glacier-100 text-glacier-800 hover:bg-glacier-200 dark:bg-glacier-800 dark:text-glacier-100 dark:hover:bg-glacier-700 rounded-full px-3 py-1 text-xs font-medium focus:outline-none focus-visible:ring-2"
                  aria-label={`Remove ${skillItem?.label ?? 'crafting skill'}`}
                >
                  {skillItem?.label ?? skillId} ×
                </button>
              </li>
            );
          })}
        </ul>
      )}

      <MapGemRangeField
        id="map-gem-crafting-skill-bonus-range"
        label="Crafting Skill Bonus Range"
        value={state.crafting_skill_bonus_range}
        on_change={(value) => onChange('crafting_skill_bonus_range', value)}
        error={errors.crafting_skill_bonus_range}
      />
    </div>
  );
};

export default MapGemPlayerRewardsFields;
