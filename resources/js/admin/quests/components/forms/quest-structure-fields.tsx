import React, { ReactNode } from 'react';

import QuestFormFieldsProps from '../../types/quest-form-fields-props';
import { parseNumberOption } from '../../utils/parse-quest-dropdown-value';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import FieldWrapper from 'ui/forms/field-wrapper';
import NumberField from 'ui/forms/number-field';

const QuestStructureFields = ({
  state,
  errors,
  form_options: formOptions,
  on_change: onChange,
}: QuestFormFieldsProps): ReactNode => {
  const questItems: DropdownItem[] = formOptions.quests;

  const chainQuestNames = state.required_quest_chain.map(
    (id) => questItems.find((item) => item.value === id)?.label ?? `#${id}`
  );

  const addToChain = (id: number): void => {
    if (state.required_quest_chain.includes(id)) {
      return;
    }

    onChange('required_quest_chain', [...state.required_quest_chain, id]);
  };

  const removeFromChain = (id: number): void => {
    onChange(
      'required_quest_chain',
      state.required_quest_chain.filter((chainId) => chainId !== id)
    );
  };

  return (
    <div className="space-y-4">
      <FieldWrapper
        id="quest-parent"
        label="Parent Quest"
        error={errors.parent_quest_id}
      >
        {(describedBy) => (
          <Dropdown
            id="quest-parent"
            aria_label="Parent Quest"
            aria_described_by={describedBy}
            aria_invalid={!!errors.parent_quest_id}
            searchable
            items={questItems}
            pre_selected_item={questItems.find(
              (item) => item.value === state.parent_quest_id
            )}
            on_select={(item) =>
              onChange('parent_quest_id', parseNumberOption(item.value))
            }
            on_clear={() => onChange('parent_quest_id', null)}
            selection_placeholder="None"
          />
        )}
      </FieldWrapper>

      <FieldWrapper
        id="quest-required-quest"
        label="Required Quest"
        error={errors.required_quest_id}
      >
        {(describedBy) => (
          <Dropdown
            id="quest-required-quest"
            aria_label="Required Quest"
            aria_described_by={describedBy}
            aria_invalid={!!errors.required_quest_id}
            searchable
            items={questItems}
            pre_selected_item={questItems.find(
              (item) => item.value === state.required_quest_id
            )}
            on_select={(item) =>
              onChange('required_quest_id', parseNumberOption(item.value))
            }
            on_clear={() => onChange('required_quest_id', null)}
            selection_placeholder="None"
          />
        )}
      </FieldWrapper>

      <FieldWrapper
        id="quest-required-chain"
        label="Required Quest Chain"
        error={errors.required_quest_chain}
      >
        {(describedBy) => (
          <div className="space-y-2">
            <Dropdown
              id="quest-required-chain"
              aria_label="Add a required Quest to the chain"
              aria_described_by={describedBy}
              searchable
              items={questItems.filter(
                (item) =>
                  !state.required_quest_chain.includes(
                    parseNumberOption(item.value)
                  )
              )}
              on_select={(item) => addToChain(parseNumberOption(item.value))}
              selection_placeholder="Add a required Quest…"
            />
            {chainQuestNames.length > 0 && (
              <ul className="flex flex-wrap gap-2">
                {state.required_quest_chain.map((id, index) => (
                  <li
                    key={id}
                    className="bg-danube-100 text-danube-800 dark:bg-danube-200 dark:text-danube-900 flex items-center gap-2 rounded-full px-3 py-1 text-sm"
                  >
                    <span>
                      {index + 1}. {chainQuestNames[index]}
                    </span>
                    <button
                      type="button"
                      aria-label={`Remove ${chainQuestNames[index]} from the required chain`}
                      onClick={() => removeFromChain(id)}
                      className="font-semibold"
                    >
                      ×
                    </button>
                  </li>
                ))}
              </ul>
            )}
          </div>
        )}
      </FieldWrapper>

      <NumberField
        id="quest-reincarnated-times"
        label="Reincarnated Times"
        value={state.reincarnated_times}
        on_change={(value) => onChange('reincarnated_times', value)}
        min={0}
        error={errors.reincarnated_times}
      />
    </div>
  );
};

export default QuestStructureFields;
