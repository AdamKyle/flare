import React, { ReactNode } from 'react';

import EnchantingAffixSelectionProps from './types/enchanting-affix-selection-props';

import Dropdown from 'ui/drop-down/drop-down';

const buildAffixOptions = (
  affixes: EnchantingAffixSelectionProps['affixes'],
  type: 'prefix' | 'suffix'
) =>
  affixes
    .filter((affix) => affix.type === type)
    .sort((first, second) => first.cost - second.cost)
    .map((affix) => ({
      label: `${affix.name} [Cost: ${affix.cost}, INT REQ: ${affix.int_required}]`,
      value: affix.id,
    }));

const EnchantingAffixSelection = ({
  affixes,
  onPrefix,
  onSuffix,
}: EnchantingAffixSelectionProps): ReactNode => {
  const prefixOptions = buildAffixOptions(affixes, 'prefix');
  const suffixOptions = buildAffixOptions(affixes, 'suffix');

  const handlePrefixSelect = (item: { value: number | string }): void => {
    onPrefix(Number(item.value));
  };

  const handleSuffixSelect = (item: { value: number | string }): void => {
    onSuffix(Number(item.value));
  };

  return (
    <div className="grid gap-4 md:grid-cols-2">
      <div>
        <label
          id="enchanting-prefix-label"
          className="mb-2 block font-semibold"
        >
          Prefix
        </label>

        <Dropdown
          aria_labelled_by="enchanting-prefix-label"
          items={prefixOptions}
          on_clear={() => onPrefix(null)}
          selection_placeholder="Select a prefix"
          on_select={handlePrefixSelect}
        />
      </div>

      <div>
        <label
          id="enchanting-suffix-label"
          className="mb-2 block font-semibold"
        >
          Suffix
        </label>

        <Dropdown
          aria_labelled_by="enchanting-suffix-label"
          items={suffixOptions}
          on_clear={() => onSuffix(null)}
          selection_placeholder="Select a suffix"
          on_select={handleSuffixSelect}
        />
      </div>
    </div>
  );
};

export default EnchantingAffixSelection;
