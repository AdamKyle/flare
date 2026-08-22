import React, { ReactNode, useState } from 'react';

import BatchCraftingScreenManager from '../component-mapping/batch-crafting-screen-manager';
import { BatchCraftingDisposition } from '../enums/batch-crafting-disposition';
import { BatchCraftingOutputDestination } from '../enums/batch-crafting-output-destination';
import { BatchCraftingScreenNames } from '../enums/batch-crafting-screen-names';
import { useBatchCraftingInventorySetOptions } from '../hooks/use-batch-crafting-inventory-set-options';
import CraftAndEnchantOutputSelection from '../types/craft-and-enchant-output-selection';
import { createEnumValueGuard } from '../utils/create-enum-value-guard';

import { useGameData } from 'game-data/hooks/use-game-data';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const isBatchCraftingDisposition = createEnumValueGuard(
  BatchCraftingDisposition
);

const isBatchCraftingOutputDestination = createEnumValueGuard(
  BatchCraftingOutputDestination
);

const DISPOSITION_OPTIONS: DropdownItem[] = [
  { label: 'Keep', value: BatchCraftingDisposition.KEEP },
  { label: 'Sell', value: BatchCraftingDisposition.SELL },
  { label: 'Destroy', value: BatchCraftingDisposition.DESTROY },
  { label: 'List', value: BatchCraftingDisposition.LIST },
  { label: 'Disenchant', value: BatchCraftingDisposition.DISENCHANT },
];

const OUTPUT_DESTINATION_OPTIONS: DropdownItem[] = [
  { label: 'Inventory', value: BatchCraftingOutputDestination.INVENTORY },
  {
    label: 'Specified Empty Set',
    value: BatchCraftingOutputDestination.INVENTORY_SET,
  },
  {
    label: 'Crafted Items Set',
    value: BatchCraftingOutputDestination.CRAFTED_ITEMS_SET,
  },
];

const CraftAndEnchantSetOutputScreen = (): ReactNode => {
  const { gameData } = useGameData();
  const characterId = gameData?.character?.id ?? 0;
  const navigation = BatchCraftingScreenManager.useScreenNavigation();

  const [disposition, setDisposition] =
    useState<BatchCraftingDisposition | null>(null);
  const [outputDestination, setOutputDestination] =
    useState<BatchCraftingOutputDestination | null>(null);
  const [selectedSet, setSelectedSet] = useState<DropdownItem | null>(null);
  const [setSearchText, setSetSearchTextState] = useState('');
  const [listingPriceText, setListingPriceText] = useState('');

  const showDestinationField = disposition === BatchCraftingDisposition.KEEP;
  const showListingPriceField = disposition === BatchCraftingDisposition.LIST;

  const showInventorySetSelector =
    showDestinationField &&
    outputDestination === BatchCraftingOutputDestination.INVENTORY_SET;

  const {
    setOptions,
    loading: setOptionsLoading,
    isLoadingMore: setOptionsLoadingMore,
    canLoadMore: setOptionsCanLoadMore,
    onEndReached: setOptionsOnEndReached,
    setSearchText: setApiSetSearchText,
  } = useBatchCraftingInventorySetOptions({
    characterId,
    enabled: showInventorySetSelector,
  });

  const setOptionItems: DropdownItem[] = setOptions.map((set) => ({
    label: set.display_name,
    value: set.set_id,
  }));

  const navigateToPlanner = (selection: CraftAndEnchantOutputSelection) => {
    navigation.navigateTo(BatchCraftingScreenNames.CRAFT_AND_ENCHANT_SET, {
      output_selection: selection,
    });
  };

  const handleDispositionSelect = (item: DropdownItem) => {
    if (
      typeof item.value !== 'string' ||
      !isBatchCraftingDisposition(item.value)
    ) {
      return;
    }

    const nextDisposition = item.value;

    setDisposition(nextDisposition);
    setOutputDestination(null);
    setSelectedSet(null);

    if (
      nextDisposition === BatchCraftingDisposition.KEEP ||
      nextDisposition === BatchCraftingDisposition.LIST
    ) {
      return;
    }

    navigateToPlanner({
      disposition: nextDisposition,
      output_destination: null,
      output_set_id: null,
      output_set_name: null,
      listing_price: null,
    });
  };

  const handleOutputDestinationSelect = (item: DropdownItem) => {
    if (
      typeof item.value !== 'string' ||
      !isBatchCraftingOutputDestination(item.value)
    ) {
      return;
    }

    const nextDestination = item.value;

    setOutputDestination(nextDestination);

    if (nextDestination !== BatchCraftingOutputDestination.INVENTORY_SET) {
      setSelectedSet(null);
    }

    if (nextDestination === BatchCraftingOutputDestination.INVENTORY_SET) {
      return;
    }

    navigateToPlanner({
      disposition: BatchCraftingDisposition.KEEP,
      output_destination: nextDestination,
      output_set_id: null,
      output_set_name: null,
      listing_price: null,
    });
  };

  const handleSetSelect = (item: DropdownItem) => {
    if (typeof item.value !== 'number') {
      return;
    }

    setSelectedSet(item);

    navigateToPlanner({
      disposition: BatchCraftingDisposition.KEEP,
      output_destination: BatchCraftingOutputDestination.INVENTORY_SET,
      output_set_id: item.value,
      output_set_name: item.label,
      listing_price: null,
    });
  };

  const handleSetSearch = (value: string) => {
    setSetSearchTextState(value);
    setApiSetSearchText(value);
  };

  const handleListingPriceChange = (
    event: React.ChangeEvent<HTMLInputElement>
  ) => {
    const value = event.target.value;

    setListingPriceText(value);

    const price = Number(value);

    if (!Number.isFinite(price) || !Number.isInteger(price) || price < 1) {
      return;
    }

    navigateToPlanner({
      disposition: BatchCraftingDisposition.LIST,
      output_destination: null,
      output_set_id: null,
      output_set_name: null,
      listing_price: price,
    });
  };

  const selectedDispositionItem = DISPOSITION_OPTIONS.find(
    (option) => option.value === disposition
  );

  const selectedOutputDestinationItem = OUTPUT_DESTINATION_OPTIONS.find(
    (option) => option.value === outputDestination
  );

  return (
    <div className="space-y-4">
      <h3 className="text-lg font-semibold">Craft and Enchant Set</h3>
      <p className="text-sm text-gray-600 dark:text-gray-400">
        First, choose what should happen to the completed enchanted set.
      </p>

      <fieldset>
        <legend
          id="craft-and-enchant-set-output-disposition-legend"
          className="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
        >
          What should happen to the completed set?
        </legend>
        <Dropdown
          aria_labelled_by="craft-and-enchant-set-output-disposition-legend"
          items={DISPOSITION_OPTIONS}
          on_select={handleDispositionSelect}
          pre_selected_item={selectedDispositionItem}
          selection_placeholder="Please select an option"
        />
      </fieldset>

      {showDestinationField && (
        <fieldset>
          <legend
            id="craft-and-enchant-set-output-destination-legend"
            className="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            Where would you like the completed set to go?
          </legend>
          <Dropdown
            aria_labelled_by="craft-and-enchant-set-output-destination-legend"
            items={OUTPUT_DESTINATION_OPTIONS}
            on_select={handleOutputDestinationSelect}
            pre_selected_item={selectedOutputDestinationItem}
            selection_placeholder="Please select a destination"
          />
        </fieldset>
      )}

      {showInventorySetSelector && (
        <fieldset>
          <legend
            id="craft-and-enchant-set-output-set-legend"
            className="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            Which inventory set should receive the completed set?
          </legend>
          <Dropdown
            aria_labelled_by="craft-and-enchant-set-output-set-legend"
            items={setOptionItems}
            on_select={handleSetSelect}
            pre_selected_item={selectedSet ?? undefined}
            selection_placeholder="Please select an inventory set"
            searchable
            search_value={setSearchText}
            on_search={handleSetSearch}
            can_load_more={setOptionsCanLoadMore}
            is_loading_more={setOptionsLoadingMore}
            on_end_reached={setOptionsOnEndReached}
            empty_message={
              setOptionsLoading ? 'Loading sets...' : 'No sets found.'
            }
            search_placeholder="Search your sets"
          />
        </fieldset>
      )}

      {showListingPriceField && (
        <div>
          <label
            htmlFor="craft-and-enchant-set-output-listing-price"
            className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            Listing Price
          </label>
          <input
            id="craft-and-enchant-set-output-listing-price"
            type="number"
            min={1}
            step={1}
            value={listingPriceText}
            onChange={handleListingPriceChange}
            className="w-full rounded-md border border-gray-500 bg-white p-2 text-gray-900 focus:ring-2 focus:ring-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white"
          />
        </div>
      )}
    </div>
  );
};

export default CraftAndEnchantSetOutputScreen;
