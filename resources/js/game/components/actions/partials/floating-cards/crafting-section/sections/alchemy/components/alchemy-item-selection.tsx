import React, { ReactNode, useMemo } from 'react';

import AlchemyItemSelectionProps from './types/alchemy-item-selection-props';

import { formatNumberWithCommas } from 'game-utils/format-number';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const buildAlchemyItemOptions = (
  items: AlchemyItemSelectionProps['items']
): DropdownItem[] =>
  items.map((item) => ({
    label: `${item.name} [Gold Dust: ${formatNumberWithCommas(item.gold_dust_cost)}, Shards: ${formatNumberWithCommas(item.shards_cost)}, Owned: ${formatNumberWithCommas(item.owned_amount)}]`,
    value: item.id,
  }));

const AlchemyItemSelection = ({
  items,
  selectedItemId,
  onSelect,
}: AlchemyItemSelectionProps): ReactNode => {
  const options = useMemo(() => buildAlchemyItemOptions(items), [items]);

  const preSelectedItem = options.find(
    (option) => option.value === selectedItemId
  );

  const handleSelect = (option: DropdownItem): void => {
    onSelect(Number(option.value));
  };

  return (
    <div>
      <label id="alchemy-item-label" className="mb-2 block font-semibold">
        Alchemy item
      </label>

      <Dropdown
        aria_labelled_by="alchemy-item-label"
        items={options}
        on_select={handleSelect}
        selection_placeholder="Select an Alchemy item"
        pre_selected_item={preSelectedItem}
      />
    </div>
  );
};

export default AlchemyItemSelection;
