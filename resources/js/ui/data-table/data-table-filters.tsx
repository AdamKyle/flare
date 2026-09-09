import clsx from 'clsx';
import React, { ReactNode } from 'react';

import { useDataTableSearchValue } from './hooks/use-data-table-search-value';
import DataTableFilterDefinition from './types/data-table-filter-definition';
import DataTableFiltersProps from './types/data-table-filters-props';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import Input from 'ui/input/input';

const DataTableFilters = ({
  id_prefix,
  search_label,
  search_value,
  on_search_change,
  filters,
}: DataTableFiltersProps): ReactNode => {
  const {
    search_input_value: searchInputValue,
    set_search_input_value: setSearchInputValue,
  } = useDataTableSearchValue(search_value, on_search_change);

  const searchId = `${id_prefix}-search`;
  const hasFilters = Boolean(filters && filters.length > 0);

  const renderFilter = (filter: DataTableFilterDefinition) => {
    const items: DropdownItem[] = filter.options.map((option) => ({
      label: option.label,
      value: option.value,
    }));

    const preSelectedItem = items.find((item) => item.value === filter.value);
    const filterId = `${id_prefix}-filter-${filter.key}`;

    const handleSelect = (item: DropdownItem) => {
      filter.on_change(item.value);
    };

    return (
      <div key={filter.key} className="w-full md:w-56">
        <label
          htmlFor={filterId}
          className="mb-1 block text-xs font-semibold text-gray-700 uppercase dark:text-gray-300"
        >
          {filter.label}
        </label>
        <Dropdown
          id={filterId}
          aria_label={filter.label}
          items={items}
          on_select={handleSelect}
          on_clear={filter.on_clear}
          pre_selected_item={preSelectedItem}
          selection_placeholder={`All ${filter.label}`}
        />
      </div>
    );
  };

  const renderFilters = (): ReactNode => {
    if (!filters || filters.length === 0) {
      return null;
    }

    return (
      <div className="flex w-full flex-wrap gap-4 md:w-auto">
        {filters.map(renderFilter)}
      </div>
    );
  };

  return (
    <div className="flex flex-col gap-4 border-b border-gray-200 bg-gray-50 p-4 md:flex-row md:flex-wrap md:items-end dark:border-gray-700 dark:bg-gray-800">
      <div className={clsx('w-full', hasFilters ? 'md:w-80' : 'md:flex-1')}>
        <label
          htmlFor={searchId}
          className="mb-1 block text-xs font-semibold text-gray-700 uppercase dark:text-gray-300"
        >
          {search_label}
        </label>
        <Input
          id={searchId}
          aria_label={search_label}
          value={searchInputValue}
          on_change={setSearchInputValue}
          place_holder={search_label}
          clearable
        />
      </div>
      {renderFilters()}
    </div>
  );
};

export default DataTableFilters;
