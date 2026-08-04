import React, { ReactNode, useMemo } from 'react';

import TrinketSelectionProps from './types/trinket-selection-props';

import { formatNumberWithCommas } from 'game-utils/format-number';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const buildTrinketOptions = (
  items: TrinketSelectionProps['items']
): DropdownItem[] =>
  items.map((item) => ({
    label: `${item.name} [Gold Dust: ${formatNumberWithCommas(item.gold_dust_cost)}, Copper Coins: ${formatNumberWithCommas(item.copper_coin_cost)}]`,
    value: item.id,
  }));

const TrinketSelection = ({
  items,
  selectedItemId,
  onSelect,
}: TrinketSelectionProps): ReactNode => {
  const options = useMemo(() => buildTrinketOptions(items), [items]);

  const preSelectedItem = options.find(
    (option) => option.value === selectedItemId
  );

  const handleSelect = (option: DropdownItem): void => {
    onSelect(Number(option.value));
  };

  return (
    <div>
      <label id="trinketry-item-label" className="mb-2 block font-semibold">
        Trinket
      </label>

      <Dropdown
        aria_labelled_by="trinketry-item-label"
        items={options}
        selection_placeholder="Select a Trinket"
        pre_selected_item={preSelectedItem}
        on_select={handleSelect}
      />
    </div>
  );
};

export default TrinketSelection;
