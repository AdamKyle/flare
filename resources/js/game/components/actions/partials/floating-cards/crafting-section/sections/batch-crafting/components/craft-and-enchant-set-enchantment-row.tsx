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
  prefix_loading,
  prefix_search_text,
  on_prefix_search,
  prefix_can_load_more,
  prefix_is_loading_more,
  on_prefix_end_reached,
  suffix_loading,
  suffix_search_text,
  on_suffix_search,
  suffix_can_load_more,
  suffix_is_loading_more,
  on_suffix_end_reached,
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
            selection_placeholder={
              prefix_loading ? 'Loading…' : 'Select a Prefix'
            }
            force_clear={selected_prefix === null}
            render_item_content={(option) => (
              <EnchantingAffixOption option={option} affixes={prefix_affixes} />
            )}
            searchable
            search_value={prefix_search_text}
            on_search={on_prefix_search}
            can_load_more={prefix_can_load_more}
            is_loading_more={prefix_is_loading_more}
            on_end_reached={on_prefix_end_reached}
            empty_message="No affixes are available."
            disabled={prefix_loading}
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
            selection_placeholder={
              suffix_loading ? 'Loading…' : 'Select a Suffix'
            }
            force_clear={selected_suffix === null}
            render_item_content={(option) => (
              <EnchantingAffixOption option={option} affixes={suffix_affixes} />
            )}
            searchable
            search_value={suffix_search_text}
            on_search={on_suffix_search}
            can_load_more={suffix_can_load_more}
            is_loading_more={suffix_is_loading_more}
            on_end_reached={on_suffix_end_reached}
            empty_message="No affixes are available."
            disabled={suffix_loading}
          />
        </fieldset>
      </div>
    </div>
  );
};

export default CraftAndEnchantSetEnchantmentRow;
