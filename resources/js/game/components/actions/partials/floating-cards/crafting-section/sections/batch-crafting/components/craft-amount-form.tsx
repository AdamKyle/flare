import { debounce } from 'lodash';
import React, { ReactNode, useEffect, useMemo, useRef, useState } from 'react';

import CraftAmountPreview from './craft-amount-preview';
import { useCraftableItemsApi } from '../../crafting/api/hooks/use-craftable-items-api';
import {
  armourTypeOptions,
  craftTypeOptions,
} from '../../crafting/utils/crafting-options';
import { useBatchCraftingPreview } from '../api/hooks/use-batch-crafting-preview';
import { useStartBatchCrafting } from '../api/hooks/use-start-batch-crafting';
import BatchCraftingScreenManager from '../component-mapping/batch-crafting-screen-manager';
import { BatchCraftingDisposition } from '../enums/batch-crafting-disposition';
import { BatchCraftingOutputDestination } from '../enums/batch-crafting-output-destination';
import { BatchCraftingScreenNames } from '../enums/batch-crafting-screen-names';
import { buildCraftAmountRequest } from '../utils/build-craft-amount-request';

import { useGameData } from 'game-data/hooks/use-game-data';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import IndeterminateProgressBar from 'ui/progress/indeterminate-progress-bar';

const PREVIEW_DEBOUNCE_MS = 300;

const CRAFT_TYPE_OPTIONS: DropdownItem[] = craftTypeOptions.filter(
  (option) => option.value !== 'for-class'
);

const DISPOSITION_OPTIONS: DropdownItem[] = [
  { label: 'Keep', value: BatchCraftingDisposition.KEEP },
  { label: 'Sell', value: BatchCraftingDisposition.SELL },
  { label: 'Destroy', value: BatchCraftingDisposition.DESTROY },
];

const OUTPUT_DESTINATION_OPTIONS: DropdownItem[] = [
  {
    label: 'Crafted Items Set',
    value: BatchCraftingOutputDestination.CRAFTED_ITEMS_SET,
  },
  { label: 'Inventory', value: BatchCraftingOutputDestination.INVENTORY },
];

const CraftAmountForm = (): ReactNode => {
  const { gameData } = useGameData();
  const characterId = gameData?.character?.id ?? 0;
  const navigation = BatchCraftingScreenManager.useScreenNavigation();

  const [craftingType, setCraftingType] = useState<DropdownItem | null>(null);
  const [armourType, setArmourType] = useState<DropdownItem | null>(null);
  const [selectedItem, setSelectedItem] = useState<DropdownItem | null>(null);
  const [amountText, setAmountText] = useState('1');
  const [disposition, setDisposition] = useState<BatchCraftingDisposition>(
    BatchCraftingDisposition.KEEP
  );
  const [outputDestination, setOutputDestination] =
    useState<BatchCraftingOutputDestination>(
      BatchCraftingOutputDestination.CRAFTED_ITEMS_SET
    );
  const [searchText, setSearchTextState] = useState('');

  const selectedCraftingType =
    craftTypeOptions.find((option) => option.value === craftingType?.value)
      ?.value ?? null;
  const selectedArmourType =
    armourTypeOptions.find((option) => option.value === armourType?.value)
      ?.value ?? null;

  const {
    items,
    loading,
    isLoadingMore,
    canLoadMore,
    onEndReached,
    setSearchText,
  } = useCraftableItemsApi({
    characterId,
    selectedType: selectedCraftingType,
    armourType: selectedArmourType,
    itemType: null,
  });

  const {
    preview,
    loading: previewLoading,
    error: previewError,
    fetchPreview,
    clearPreview,
  } = useBatchCraftingPreview(characterId);
  const {
    starting,
    error: startError,
    start,
  } = useStartBatchCrafting(characterId);

  const request = useMemo(
    () =>
      buildCraftAmountRequest({
        craftingType,
        selectedItem,
        amountText,
        disposition,
        outputDestination,
      }),
    [craftingType, selectedItem, amountText, disposition, outputDestination]
  );

  const debouncedFetchRef = useRef<ReturnType<typeof debounce> | null>(null);

  useEffect(() => {
    debouncedFetchRef.current?.cancel();
    clearPreview();

    if (!request) {
      return;
    }

    const debouncedFetch = debounce(() => {
      void fetchPreview(request);
    }, PREVIEW_DEBOUNCE_MS);

    debouncedFetchRef.current = debouncedFetch;
    debouncedFetch();

    return () => {
      debouncedFetch.cancel();
    };
  }, [request, fetchPreview, clearPreview]);

  const handleSearch = (value: string) => {
    setSearchTextState(value);
    setSearchText(value);
  };

  const handleCraftingTypeSelect = (item: DropdownItem) => {
    setCraftingType(item);
    setArmourType(null);
    setSelectedItem(null);
    setSearchTextState('');
    setSearchText('');
  };

  const handleArmourTypeSelect = (item: DropdownItem) => {
    setArmourType(item);
    setSelectedItem(null);
    setSearchTextState('');
    setSearchText('');
  };

  const handleItemSelect = (item: DropdownItem) => {
    setSelectedItem(item);
  };

  const handleAmountChange = (event: React.ChangeEvent<HTMLInputElement>) => {
    setAmountText(event.target.value);
  };

  const handleDispositionSelect = (item: DropdownItem) => {
    if (typeof item.value !== 'string') {
      return;
    }

    if (
      item.value === BatchCraftingDisposition.KEEP ||
      item.value === BatchCraftingDisposition.SELL ||
      item.value === BatchCraftingDisposition.DESTROY
    ) {
      setDisposition(item.value);
    }
  };

  const handleOutputDestinationSelect = (item: DropdownItem) => {
    if (typeof item.value !== 'string') {
      return;
    }

    if (
      item.value === BatchCraftingOutputDestination.INVENTORY ||
      item.value === BatchCraftingOutputDestination.CRAFTED_ITEMS_SET
    ) {
      setOutputDestination(item.value);
    }
  };

  const handleStart = async () => {
    if (!request) {
      return;
    }

    const started = await start(request);

    if (started) {
      navigation.resetTo(BatchCraftingScreenNames.RUNNING, {});
    }
  };

  const itemOptions: DropdownItem[] = items.map((item) => ({
    label: item.preview.name,
    value: item.id,
  }));

  const selectedDispositionItem =
    DISPOSITION_OPTIONS.find((option) => option.value === disposition) ??
    DISPOSITION_OPTIONS[0];

  const selectedOutputDestinationItem =
    OUTPUT_DESTINATION_OPTIONS.find(
      (option) => option.value === outputDestination
    ) ?? OUTPUT_DESTINATION_OPTIONS[0];

  const canStart =
    request !== null &&
    preview !== null &&
    !previewLoading &&
    preview.blockers.length === 0 &&
    !starting;

  const renderArmourTypeField = () => {
    if (craftingType?.value !== 'armour') {
      return null;
    }

    return (
      <fieldset>
        <legend
          id="batch-craft-armour-type-legend"
          className="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
        >
          Armour Type
        </legend>
        <Dropdown
          aria_labelled_by="batch-craft-armour-type-legend"
          items={armourTypeOptions}
          on_select={handleArmourTypeSelect}
          pre_selected_item={armourType ?? undefined}
          selection_placeholder="Select an armour type"
          force_clear={armourType === null}
        />
      </fieldset>
    );
  };

  const renderOutputDestinationField = () => {
    if (disposition !== BatchCraftingDisposition.KEEP) {
      return null;
    }

    return (
      <fieldset>
        <legend
          id="batch-craft-output-destination-legend"
          className="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
        >
          Output Destination
        </legend>
        <Dropdown
          aria_labelled_by="batch-craft-output-destination-legend"
          items={OUTPUT_DESTINATION_OPTIONS}
          on_select={handleOutputDestinationSelect}
          pre_selected_item={selectedOutputDestinationItem}
          selection_placeholder="Select an output destination"
        />
      </fieldset>
    );
  };

  const renderPreview = () => {
    if (previewLoading) {
      return (
        <IndeterminateProgressBar
          label="Updating preview"
          variant={ProgressBarVariant.PRIMARY}
        />
      );
    }

    if (previewError) {
      return <Alert variant={AlertVariant.DANGER}>{previewError}</Alert>;
    }

    if (!preview) {
      return null;
    }

    return <CraftAmountPreview preview={preview} />;
  };

  return (
    <div className="space-y-4">
      <h3 className="text-lg font-semibold">Craft Amount</h3>

      <fieldset>
        <legend
          id="batch-craft-type-legend"
          className="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
        >
          Craft Type
        </legend>
        <Dropdown
          aria_labelled_by="batch-craft-type-legend"
          items={CRAFT_TYPE_OPTIONS}
          on_select={handleCraftingTypeSelect}
          pre_selected_item={craftingType ?? undefined}
          selection_placeholder="Select a craft type"
        />
      </fieldset>

      {renderArmourTypeField()}

      <fieldset>
        <legend
          id="batch-craft-item-legend"
          className="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
        >
          Craftable Item
        </legend>
        <Dropdown
          aria_labelled_by="batch-craft-item-legend"
          items={itemOptions}
          on_select={handleItemSelect}
          pre_selected_item={selectedItem ?? undefined}
          selection_placeholder="Select a craftable item"
          disabled={
            !craftingType || (craftingType.value === 'armour' && !armourType)
          }
          searchable
          search_value={searchText}
          on_search={handleSearch}
          can_load_more={canLoadMore}
          is_loading_more={isLoadingMore}
          on_end_reached={onEndReached}
          empty_message={
            loading ? 'Loading craftable items...' : 'No craftable items found.'
          }
          search_placeholder="Search craftable items"
        />
      </fieldset>

      <div>
        <label
          htmlFor="batch-craft-amount"
          className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
        >
          Amount
        </label>
        <input
          id="batch-craft-amount"
          type="number"
          min={1}
          max={2000}
          step={1}
          value={amountText}
          onChange={handleAmountChange}
          className="w-full rounded-md border border-gray-500 bg-white p-2 text-gray-900 focus:ring-2 focus:ring-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white"
        />
      </div>

      <fieldset>
        <legend
          id="batch-craft-disposition-legend"
          className="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
        >
          Disposition
        </legend>
        <Dropdown
          aria_labelled_by="batch-craft-disposition-legend"
          items={DISPOSITION_OPTIONS}
          on_select={handleDispositionSelect}
          pre_selected_item={selectedDispositionItem}
          selection_placeholder="Select a disposition"
        />
      </fieldset>

      {renderOutputDestinationField()}

      {renderPreview()}

      {startError && <Alert variant={AlertVariant.DANGER}>{startError}</Alert>}

      <Button
        label="Batch Craft"
        variant={ButtonVariant.SUCCESS}
        additional_css="w-full"
        disabled={!canStart}
        on_click={handleStart}
      />
    </div>
  );
};

export default CraftAmountForm;
