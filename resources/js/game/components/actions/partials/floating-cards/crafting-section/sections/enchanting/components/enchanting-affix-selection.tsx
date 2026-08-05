import React, { ReactNode } from 'react';

import EnchantingAffixSelectionProps, {
  EnchantingAffixSlotProps,
} from './types/enchanting-affix-selection-props';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const EnchantingAffixSelection = ({
  prefix,
  suffix,
  onPrefix,
  onSuffix,
}: EnchantingAffixSelectionProps): ReactNode => {
  const handlePrefixSelect = (item: DropdownItem): void => {
    onPrefix(Number(item.value));
  };

  const handleSuffixSelect = (item: DropdownItem): void => {
    onSuffix(Number(item.value));
  };

  const renderAffixDropdown = (
    labelId: string,
    slot: EnchantingAffixSlotProps,
    placeholder: string,
    onSelect: (item: DropdownItem) => void,
    onClear: () => void
  ): ReactNode => (
    <Dropdown
      aria_labelled_by={labelId}
      items={slot.items}
      on_clear={onClear}
      selection_placeholder={slot.loading ? 'Loading…' : placeholder}
      on_select={onSelect}
      searchable
      search_value={slot.searchText}
      on_search={slot.onSearch}
      can_load_more={slot.canLoadMore}
      is_loading_more={slot.isLoadingMore}
      on_end_reached={slot.onEndReached}
      empty_message="No affixes are available."
      disabled={slot.loading}
    />
  );

  return (
    <div className="space-y-4">
      <div>
        <label
          id="enchanting-prefix-label"
          className="mb-2 block font-semibold"
        >
          Prefix
        </label>

        {renderAffixDropdown(
          'enchanting-prefix-label',
          prefix,
          'Select a prefix',
          handlePrefixSelect,
          () => onPrefix(null)
        )}
      </div>

      <div>
        <label
          id="enchanting-suffix-label"
          className="mb-2 block font-semibold"
        >
          Suffix
        </label>

        {renderAffixDropdown(
          'enchanting-suffix-label',
          suffix,
          'Select a suffix',
          handleSuffixSelect,
          () => onSuffix(null)
        )}
      </div>
    </div>
  );
};

export default EnchantingAffixSelection;
