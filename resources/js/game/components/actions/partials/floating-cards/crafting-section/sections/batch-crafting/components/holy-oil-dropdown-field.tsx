import React, { ReactNode } from 'react';

import HolyOilDropdownFieldProps from './types/holy-oil-dropdown-field-props';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const HolyOilDropdownField = ({
  legend_id,
  loaded_items,
  selected_oil,
  loading,
  can_load_more,
  is_loading_more,
  search_text,
  on_search,
  on_end_reached,
  on_select,
}: HolyOilDropdownFieldProps): ReactNode => {
  const items: DropdownItem[] = loaded_items.map((item) => ({
    label: `${item.name} (x${item.stack_amount})`,
    value: item.id,
  }));

  return (
    <fieldset>
      <legend
        id={legend_id}
        className="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
      >
        Holy Oil
      </legend>
      <Dropdown
        aria_labelled_by={legend_id}
        items={items}
        on_select={on_select}
        on_clear={() => on_select(null)}
        pre_selected_item={selected_oil ?? undefined}
        force_clear={selected_oil === null}
        selection_placeholder="Select a Holy Oil"
        searchable
        search_value={search_text}
        on_search={on_search}
        can_load_more={can_load_more}
        is_loading_more={is_loading_more}
        on_end_reached={on_end_reached}
        empty_message={loading ? 'Loading Holy Oils...' : 'No Holy Oils found.'}
        search_placeholder="Search Holy Oils"
      />
    </fieldset>
  );
};

export default HolyOilDropdownField;
