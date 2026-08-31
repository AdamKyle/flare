import React, { ReactNode } from 'react';

import QuestFormFieldsProps from '../../types/quest-form-fields-props';
import { parseNumberOption } from '../../utils/parse-quest-dropdown-value';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import FieldWrapper from 'ui/forms/field-wrapper';
import Input from 'ui/input/input';
import MarkDownEditor from 'ui/mark-down-editor/mark-down-editor';

const QuestStoryFields = ({
  state,
  errors,
  form_options: formOptions,
  on_change: onChange,
}: QuestFormFieldsProps): ReactNode => {
  const npcItems: DropdownItem[] = formOptions.npcs;
  const raidItems: DropdownItem[] = formOptions.raids;
  const eventItems: DropdownItem[] = formOptions.event_types.map((value) => ({
    label: `Event ${value}`,
    value,
  }));

  return (
    <div className="space-y-4">
      <FieldWrapper id="quest-name" label="Name" required error={errors.name}>
        {(describedBy) => (
          <Input
            id="quest-name"
            value={state.name}
            on_change={(value) => onChange('name', value)}
            described_by={describedBy}
            invalid={!!errors.name}
            required
          />
        )}
      </FieldWrapper>

      <FieldWrapper
        id="quest-npc"
        label="Quest Giver NPC"
        required
        error={errors.npc_id}
      >
        {(describedBy) => (
          <Dropdown
            id="quest-npc"
            aria_label="Quest Giver NPC"
            aria_described_by={describedBy}
            aria_invalid={!!errors.npc_id}
            aria_required
            searchable
            items={npcItems}
            pre_selected_item={npcItems.find(
              (item) => item.value === state.npc_id
            )}
            on_select={(item) =>
              onChange('npc_id', parseNumberOption(item.value))
            }
            selection_placeholder="Select an NPC"
          />
        )}
      </FieldWrapper>

      <FieldWrapper id="quest-raid" label="Raid">
        {(describedBy) => (
          <Dropdown
            id="quest-raid"
            aria_label="Raid"
            aria_described_by={describedBy}
            searchable
            items={raidItems}
            pre_selected_item={raidItems.find(
              (item) => item.value === state.raid_id
            )}
            on_select={(item) =>
              onChange('raid_id', parseNumberOption(item.value))
            }
            on_clear={() => onChange('raid_id', null)}
            selection_placeholder="None"
          />
        )}
      </FieldWrapper>

      <FieldWrapper id="quest-event" label="Event Restriction">
        {(describedBy) => (
          <Dropdown
            id="quest-event"
            aria_label="Event Restriction"
            aria_described_by={describedBy}
            items={eventItems}
            pre_selected_item={eventItems.find(
              (item) => item.value === state.only_for_event
            )}
            on_select={(item) =>
              onChange('only_for_event', parseNumberOption(item.value))
            }
            on_clear={() => onChange('only_for_event', null)}
            selection_placeholder="None"
          />
        )}
      </FieldWrapper>

      <FieldWrapper id="quest-before-completion" label="Before Completion">
        {() => (
          <MarkDownEditor
            id="quest-before-completion"
            initial_markdown={state.before_completion_description}
            on_value_change={(value) =>
              onChange('before_completion_description', value)
            }
            placeholder="Describe the Quest before completion…"
          />
        )}
      </FieldWrapper>

      <FieldWrapper id="quest-after-completion" label="After Completion">
        {() => (
          <MarkDownEditor
            id="quest-after-completion"
            initial_markdown={state.after_completion_description}
            on_value_change={(value) =>
              onChange('after_completion_description', value)
            }
            placeholder="Describe the Quest after completion…"
          />
        )}
      </FieldWrapper>
    </div>
  );
};

export default QuestStoryFields;
