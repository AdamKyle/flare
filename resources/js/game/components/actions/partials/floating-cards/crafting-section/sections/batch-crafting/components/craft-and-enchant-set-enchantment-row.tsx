import React, { ReactNode } from 'react';

import CraftAndEnchantSetEnchantmentRowProps from './types/craft-and-enchant-set-enchantment-row-props';
import EnchantingAffixOption from '../../enchanting/components/enchanting-affix-option';

import Dropdown from 'ui/drop-down/drop-down';

const CraftAndEnchantSetEnchantmentRow = ({
  position_label,
  item_name,
  prefix_items,
  suffix_items,
  prefix_affixes,
  suffix_affixes,
  selected_prefix,
  selected_suffix,
  on_prefix_select,
  on_suffix_select,
}: CraftAndEnchantSetEnchantmentRowProps): ReactNode => {
  const idSuffix = position_label.toLowerCase().replace(/[^a-z0-9-]/g, '-');
  const prefixLegendId = `craft-and-enchant-set-row-prefix-${idSuffix}`;
  const suffixLegendId = `craft-and-enchant-set-row-suffix-${idSuffix}`;

  return (
    <div className="space-y-2 rounded-md border border-gray-300 p-3 dark:border-gray-700">
      <p className="text-sm font-semibold text-gray-900 dark:text-gray-100">
        {position_label}
      </p>
      <p className="text-sm text-gray-600 dark:text-gray-400">{item_name}</p>

      <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
        <fieldset>
          <legend
            id={prefixLegendId}
            className="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            Prefix
          </legend>
          <Dropdown
            aria_labelled_by={prefixLegendId}
            items={prefix_items}
            on_select={on_prefix_select}
            pre_selected_item={selected_prefix ?? undefined}
            selection_placeholder="Select a Prefix"
            force_clear={selected_prefix === null}
            render_item_content={(option) => (
              <EnchantingAffixOption option={option} affixes={prefix_affixes} />
            )}
          />
        </fieldset>

        <fieldset>
          <legend
            id={suffixLegendId}
            className="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            Suffix
          </legend>
          <Dropdown
            aria_labelled_by={suffixLegendId}
            items={suffix_items}
            on_select={on_suffix_select}
            pre_selected_item={selected_suffix ?? undefined}
            selection_placeholder="Select a Suffix"
            force_clear={selected_suffix === null}
            render_item_content={(option) => (
              <EnchantingAffixOption option={option} affixes={suffix_affixes} />
            )}
          />
        </fieldset>
      </div>
    </div>
  );
};

export default CraftAndEnchantSetEnchantmentRow;
