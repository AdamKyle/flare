import React, { ReactNode, useState } from 'react';

import BatchCraftingScreenManager from '../component-mapping/batch-crafting-screen-manager';
import { BatchCraftingDisposition } from '../enums/batch-crafting-disposition';
import { BatchCraftingOutputDestination } from '../enums/batch-crafting-output-destination';
import { BatchCraftingScreenNames } from '../enums/batch-crafting-screen-names';
import CraftAndEnchantOutputSelection from '../types/craft-and-enchant-output-selection';
import { createEnumValueGuard } from '../utils/create-enum-value-guard';

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
    label: 'Crafted Items Set',
    value: BatchCraftingOutputDestination.CRAFTED_ITEMS_SET,
  },
];

const CraftAndEnchantAmountOutputScreen = (): ReactNode => {
  const navigation = BatchCraftingScreenManager.useScreenNavigation();

  const [disposition, setDisposition] =
    useState<BatchCraftingDisposition | null>(null);
  const [outputDestination, setOutputDestination] =
    useState<BatchCraftingOutputDestination | null>(null);
  const [listingPriceText, setListingPriceText] = useState('');

  const showDestinationField = disposition === BatchCraftingDisposition.KEEP;
  const showListingPriceField = disposition === BatchCraftingDisposition.LIST;

  const navigateToForm = (selection: CraftAndEnchantOutputSelection) => {
    navigation.navigateTo(BatchCraftingScreenNames.CRAFT_AND_ENCHANT_AMOUNT, {
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

    if (
      nextDisposition === BatchCraftingDisposition.KEEP ||
      nextDisposition === BatchCraftingDisposition.LIST
    ) {
      return;
    }

    navigateToForm({
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

    navigateToForm({
      disposition: BatchCraftingDisposition.KEEP,
      output_destination: nextDestination,
      output_set_id: null,
      output_set_name: null,
      listing_price: null,
    });
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

    navigateToForm({
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
      <h3 className="text-lg font-semibold">Craft and Enchant Amount</h3>
      <p className="text-sm text-gray-600 dark:text-gray-400">
        First, choose what should happen to the completed enchanted items.
      </p>

      <fieldset>
        <legend
          id="craft-and-enchant-amount-disposition-legend"
          className="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
        >
          What should happen to the completed items?
        </legend>
        <Dropdown
          aria_labelled_by="craft-and-enchant-amount-disposition-legend"
          items={DISPOSITION_OPTIONS}
          on_select={handleDispositionSelect}
          pre_selected_item={selectedDispositionItem}
          selection_placeholder="Please select an option"
        />
      </fieldset>

      {showDestinationField && (
        <fieldset>
          <legend
            id="craft-and-enchant-amount-destination-legend"
            className="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            Where would you like the completed items to go?
          </legend>
          <Dropdown
            aria_labelled_by="craft-and-enchant-amount-destination-legend"
            items={OUTPUT_DESTINATION_OPTIONS}
            on_select={handleOutputDestinationSelect}
            pre_selected_item={selectedOutputDestinationItem}
            selection_placeholder="Please select a destination"
          />
        </fieldset>
      )}

      {showListingPriceField && (
        <div>
          <label
            htmlFor="craft-and-enchant-amount-listing-price"
            className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            Listing Price
          </label>
          <input
            id="craft-and-enchant-amount-listing-price"
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

export default CraftAndEnchantAmountOutputScreen;
