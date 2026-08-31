import React, { ReactNode } from 'react';

import QuestFormFieldsProps from '../../types/quest-form-fields-props';
import { parseNumberOption } from '../../utils/parse-quest-dropdown-value';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import FieldWrapper from 'ui/forms/field-wrapper';
import NumberField from 'ui/forms/number-field';

const QuestRequirementsFields = ({
  state,
  errors,
  form_options: formOptions,
  on_change: onChange,
}: QuestFormFieldsProps): ReactNode => {
  const questItemItems: DropdownItem[] = formOptions.quest_items;
  const gameMapItems: DropdownItem[] = formOptions.game_maps;
  const npcItems: DropdownItem[] = formOptions.npcs;

  return (
    <div className="space-y-4">
      <FieldWrapper id="quest-primary-item" label="Primary Required Quest Item">
        {(describedBy) => (
          <Dropdown
            id="quest-primary-item"
            aria_label="Primary Required Quest Item"
            aria_described_by={describedBy}
            searchable
            items={questItemItems}
            pre_selected_item={questItemItems.find(
              (item) => item.value === state.item_id
            )}
            on_select={(item) =>
              onChange('item_id', parseNumberOption(item.value))
            }
            on_clear={() => onChange('item_id', null)}
            selection_placeholder="None"
          />
        )}
      </FieldWrapper>

      <FieldWrapper
        id="quest-secondary-item"
        label="Secondary Required Quest Item"
      >
        {(describedBy) => (
          <Dropdown
            id="quest-secondary-item"
            aria_label="Secondary Required Quest Item"
            aria_described_by={describedBy}
            disabled={state.item_id === null}
            searchable
            items={questItemItems}
            pre_selected_item={questItemItems.find(
              (item) => item.value === state.secondary_required_item
            )}
            on_select={(item) =>
              onChange('secondary_required_item', parseNumberOption(item.value))
            }
            on_clear={() => onChange('secondary_required_item', null)}
            selection_placeholder="None"
          />
        )}
      </FieldWrapper>

      <div className="grid gap-4 md:grid-cols-2">
        <FieldWrapper id="quest-access-map" label="Access To Map">
          {(describedBy) => (
            <Dropdown
              id="quest-access-map"
              aria_label="Access To Map"
              aria_described_by={describedBy}
              searchable
              items={gameMapItems}
              pre_selected_item={gameMapItems.find(
                (item) => item.value === state.access_to_map_id
              )}
              on_select={(item) =>
                onChange('access_to_map_id', parseNumberOption(item.value))
              }
              on_clear={() => onChange('access_to_map_id', null)}
              selection_placeholder="None"
            />
          )}
        </FieldWrapper>

        <FieldWrapper id="quest-faction-map" label="Faction Map">
          {(describedBy) => (
            <Dropdown
              id="quest-faction-map"
              aria_label="Faction Map"
              aria_described_by={describedBy}
              searchable
              items={gameMapItems}
              pre_selected_item={gameMapItems.find(
                (item) => item.value === state.faction_game_map_id
              )}
              on_select={(item) =>
                onChange('faction_game_map_id', parseNumberOption(item.value))
              }
              on_clear={() => onChange('faction_game_map_id', null)}
              selection_placeholder="None"
            />
          )}
        </FieldWrapper>
      </div>

      <NumberField
        id="quest-required-faction-level"
        label="Required Faction Level"
        value={state.required_faction_level}
        on_change={(value) => onChange('required_faction_level', value)}
        min={0}
        error={errors.required_faction_level}
      />

      <FieldWrapper id="quest-assisting-npc" label="Assisting NPC">
        {(describedBy) => (
          <Dropdown
            id="quest-assisting-npc"
            aria_label="Assisting NPC"
            aria_described_by={describedBy}
            searchable
            items={npcItems}
            pre_selected_item={npcItems.find(
              (item) => item.value === state.assisting_npc_id
            )}
            on_select={(item) =>
              onChange('assisting_npc_id', parseNumberOption(item.value))
            }
            on_clear={() => onChange('assisting_npc_id', null)}
            selection_placeholder="None"
          />
        )}
      </FieldWrapper>

      <NumberField
        id="quest-required-fame-level"
        label="Required Fame Level"
        value={state.required_fame_level}
        on_change={(value) => onChange('required_fame_level', value)}
        min={0}
        error={errors.required_fame_level}
      />

      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <NumberField
          id="quest-gold-cost"
          label="Gold Cost"
          value={state.gold_cost}
          on_change={(value) => onChange('gold_cost', value)}
          min={0}
          error={errors.gold_cost}
        />
        <NumberField
          id="quest-gold-dust-cost"
          label="Gold Dust Cost"
          value={state.gold_dust_cost}
          on_change={(value) => onChange('gold_dust_cost', value)}
          min={0}
          error={errors.gold_dust_cost}
        />
        <NumberField
          id="quest-shard-cost"
          label="Shard Cost"
          value={state.shard_cost}
          on_change={(value) => onChange('shard_cost', value)}
          min={0}
          error={errors.shard_cost}
        />
        <NumberField
          id="quest-copper-coin-cost"
          label="Copper Coin Cost"
          value={state.copper_coin_cost}
          on_change={(value) => onChange('copper_coin_cost', value)}
          min={0}
          error={errors.copper_coin_cost}
        />
      </div>
    </div>
  );
};

export default QuestRequirementsFields;
