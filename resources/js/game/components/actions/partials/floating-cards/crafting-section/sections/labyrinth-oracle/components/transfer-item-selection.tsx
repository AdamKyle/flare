import React, { ReactNode, useMemo } from 'react';

import TransferItemSelectionProps from './types/transfer-item-selection-props';
import { buildTransferItemOptions } from '../utils/build-transfer-item-options';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const TransferItemSelection = ({
  items,
  sourceId,
  destinationId,
  loading,
  isLoadingMore,
  canLoadMore,
  searchText,
  onSearch,
  onEndReached,
  onSource,
  onDestination,
}: TransferItemSelectionProps): ReactNode => {
  const sourceOptions = useMemo(
    () => buildTransferItemOptions(items, destinationId),
    [items, destinationId]
  );

  const destinationOptions = useMemo(
    () => buildTransferItemOptions(items, sourceId),
    [items, sourceId]
  );

  const handleSourceSelect = (option: DropdownItem): void => {
    onSource(Number(option.value));
  };

  const handleDestinationSelect = (option: DropdownItem): void => {
    onDestination(Number(option.value));
  };

  const renderTransferField = (
    labelId: string,
    labelText: string,
    options: DropdownItem[],
    placeholder: string,
    onSelect: (option: DropdownItem) => void,
    shouldClearSelection: boolean
  ): ReactNode => (
    <div>
      <label id={labelId} className="mb-2 block font-semibold">
        {labelText}
      </label>

      <Dropdown
        aria_labelled_by={labelId}
        items={options}
        selection_placeholder={loading ? 'Loading items…' : placeholder}
        on_select={onSelect}
        force_clear={shouldClearSelection}
        searchable
        search_value={searchText}
        on_search={onSearch}
        can_load_more={canLoadMore}
        is_loading_more={isLoadingMore}
        on_end_reached={onEndReached}
        empty_message="No eligible items are available."
        disabled={loading}
      />
    </div>
  );

  return (
    <div className="space-y-4">
      {renderTransferField(
        'labyrinth-transfer-source-label',
        'Source item',
        sourceOptions,
        'Select the source item',
        handleSourceSelect,
        sourceId === null
      )}

      {renderTransferField(
        'labyrinth-transfer-destination-label',
        'Destination item',
        destinationOptions,
        'Select the destination item',
        handleDestinationSelect,
        destinationId === null
      )}
    </div>
  );
};

export default TransferItemSelection;
