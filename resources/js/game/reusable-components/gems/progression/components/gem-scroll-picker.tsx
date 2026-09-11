import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import React, { ReactNode, useMemo } from 'react';

import GemScrollPickerProps from './types/gem-scroll-picker-props';
import BaseUsableItemDefinition from '../../../../api-definitions/items/usable-item-definitions/base-usable-item-definition';
import { CharacterInventoryApiUrls } from '../../../../components/side-peeks/character-inventory/api/enums/character-inventory-api-urls';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const filterValueForScrollType = (
  gemScrollType: GemScrollPickerProps['gem_scroll_type']
): string => {
  if (gemScrollType === 'xp') {
    return 'xp-scrolls';
  }

  if (gemScrollType === 'currency') {
    return 'currency-scrolls';
  }

  return 'item-scrolls';
};

const GemScrollPicker = ({
  character_id: characterId,
  gem_scroll_type: gemScrollType,
  gem_scroll_currency_type: gemScrollCurrencyType,
  disabled,
  on_select: onSelect,
}: GemScrollPickerProps): ReactNode => {
  const { data, loading, error } = UsePaginatedApiHandler<
    BaseUsableItemDefinition,
    Record<string, boolean>
  >(
    {
      url: CharacterInventoryApiUrls.CHARACTER_USABLE_ITEMS,
      urlParams: { character: characterId },
      initialFilters: { [filterValueForScrollType(gemScrollType)]: true },
      enabled: characterId > 0,
    },
    50
  );

  const matchingItems = useMemo(
    () =>
      data.filter((item) => {
        if (item.slot_id === null) {
          return false;
        }

        if (!gemScrollCurrencyType) {
          return true;
        }

        return item.gem_scroll_currency_type === gemScrollCurrencyType;
      }),
    [data, gemScrollCurrencyType]
  );

  const dropdownItems: DropdownItem[] = matchingItems.map((item) => ({
    label: `${item.name} (${item.amount})`,
    value: item.slot_id as number,
  }));

  if (loading) {
    return (
      <div className="text-sm text-gray-600 dark:text-gray-400" role="status">
        Loading matching Gem Scrolls…
      </div>
    );
  }

  if (error) {
    return (
      <div className="text-sm text-rose-700 dark:text-rose-300">
        Unable to load your matching Gem Scrolls.
      </div>
    );
  }

  if (dropdownItems.length === 0) {
    return (
      <div className="text-sm text-gray-600 dark:text-gray-400">
        You have no matching Gem Scrolls in your Alchemy Bag.
      </div>
    );
  }

  return (
    <Dropdown
      items={dropdownItems}
      selection_placeholder="Choose a Gem Scroll"
      on_select={(item) => onSelect(item.value as number)}
      disabled={disabled}
    />
  );
};

export default GemScrollPicker;
