import { debounce } from 'lodash';
import React, { ReactNode, useEffect, useMemo, useRef, useState } from 'react';

import { useHolyOilsApi } from '../../work-bench/api/hooks/use-holy-oils-api';
import { useHolyOilsSetPreview } from '../api/hooks/use-holy-oils-set-preview';
import { useStartBatchCrafting } from '../api/hooks/use-start-batch-crafting';
import BatchCraftingScreenManager from '../component-mapping/batch-crafting-screen-manager';
import HolyOilDropdownField from '../components/holy-oil-dropdown-field';
import { BatchCraftingDisposition } from '../enums/batch-crafting-disposition';
import { BatchCraftingScreenNames } from '../enums/batch-crafting-screen-names';
import { useHolyOilTargetSetOptions } from '../hooks/use-holy-oil-target-set-options';
import { buildHolyOilsSetRequest } from '../utils/build-holy-oils-set-request';
import { HOLY_OILS_DISPOSITION_OPTIONS } from '../utils/holy-oils-disposition-options';

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

const HolyOilsSetScreen = (): ReactNode => {
  const { gameData } = useGameData();
  const characterId = gameData?.character?.id ?? 0;
  const navigation = BatchCraftingScreenManager.useScreenNavigation();

  const [disposition, setDisposition] = useState<DropdownItem | null>(null);
  const [listingPriceText, setListingPriceText] = useState('');
  const [selectedSet, setSelectedSet] = useState<DropdownItem | null>(null);
  const [selectedOil, setSelectedOil] = useState<DropdownItem | null>(null);

  const { setOptions, canLoadMore, onEndReached, setSearchText } =
    useHolyOilTargetSetOptions({ characterId, enabled: true });
  const holyOils = useHolyOilsApi({ character_id: characterId });

  const selectedOilId =
    typeof selectedOil?.value === 'number' ? selectedOil.value : null;

  const {
    preview,
    loading: previewLoading,
    error: previewError,
    fetchPreview,
    clearPreview,
  } = useHolyOilsSetPreview(characterId);
  const {
    starting,
    error: startError,
    start,
  } = useStartBatchCrafting(characterId);

  const selectedDisposition =
    typeof disposition?.value === 'string'
      ? (disposition.value as BatchCraftingDisposition)
      : null;

  const inventorySetId =
    typeof selectedSet?.value === 'number' ? selectedSet.value : null;

  const request = useMemo(
    () =>
      buildHolyOilsSetRequest({
        inventorySetId,
        selectedOilId,
        disposition: selectedDisposition,
        listingPriceText,
      }),
    [inventorySetId, selectedOilId, selectedDisposition, listingPriceText]
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

  const setDropdownOptions: DropdownItem[] = setOptions.map((option) => ({
    label: option.display_name,
    value: option.set_id,
  }));

  const canStart =
    request !== null &&
    preview !== null &&
    !previewLoading &&
    preview.blockers.length === 0 &&
    !starting;

  const handleStart = async () => {
    if (!request) {
      return;
    }

    const started = await start(request);

    if (started) {
      navigation.resetTo(BatchCraftingScreenNames.RUNNING, {});
    }
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

    return (
      <dl className="grid grid-cols-2 gap-x-3 gap-y-1 text-sm">
        <dt className="text-gray-600 dark:text-gray-400">Target Items</dt>
        <dd>{preview.target_count.toLocaleString()}</dd>
        <dt className="text-gray-600 dark:text-gray-400">
          Planned Applications
        </dt>
        <dd>{preview.planned_application_count.toLocaleString()}</dd>
        <dt className="text-gray-600 dark:text-gray-400">
          Total Gold Dust Cost
        </dt>
        <dd>{preview.total_gold_dust_cost.toLocaleString()}</dd>
        <dt className="text-gray-600 dark:text-gray-400">
          Gold Dust Available
        </dt>
        <dd>{preview.gold_dust_available.toLocaleString()}</dd>
        {preview.blockers.map((blocker) => (
          <dd key={blocker} className="col-span-2">
            <Alert variant={AlertVariant.DANGER}>{blocker}</Alert>
          </dd>
        ))}
      </dl>
    );
  };

  return (
    <div className="space-y-4">
      <h3 className="text-lg font-semibold">Holy Oils — Inventory Set</h3>

      <fieldset>
        <legend
          id="holy-oils-set-disposition-legend"
          className="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
        >
          Disposition
        </legend>
        <Dropdown
          aria_labelled_by="holy-oils-set-disposition-legend"
          items={HOLY_OILS_DISPOSITION_OPTIONS}
          on_select={setDisposition}
          pre_selected_item={disposition ?? undefined}
          selection_placeholder="Select a disposition"
        />
      </fieldset>

      {selectedDisposition === BatchCraftingDisposition.LIST && (
        <div>
          <label
            htmlFor="holy-oils-set-listing-price"
            className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            Listing Price
          </label>
          <input
            id="holy-oils-set-listing-price"
            type="number"
            min={1}
            value={listingPriceText}
            onChange={(event) => setListingPriceText(event.target.value)}
            className="w-full rounded-md border border-gray-500 bg-white p-2 text-gray-900 focus:ring-2 focus:ring-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white"
          />
        </div>
      )}

      <fieldset>
        <legend
          id="holy-oils-set-legend"
          className="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
        >
          Inventory Set
        </legend>
        <Dropdown
          aria_labelled_by="holy-oils-set-legend"
          items={setDropdownOptions}
          on_select={setSelectedSet}
          pre_selected_item={selectedSet ?? undefined}
          selection_placeholder="Select an Inventory Set"
          searchable
          on_search={setSearchText}
          can_load_more={canLoadMore}
          on_end_reached={onEndReached}
          empty_message="No eligible Inventory Sets found."
        />
      </fieldset>

      <HolyOilDropdownField
        legend_id="holy-oils-set-oil-legend"
        loaded_items={holyOils.loadedItems}
        selected_oil={selectedOil}
        loading={holyOils.loading}
        can_load_more={holyOils.canLoadMore}
        is_loading_more={holyOils.isLoadingMore}
        search_text={holyOils.searchText}
        on_search={holyOils.setSearchText}
        on_end_reached={holyOils.onEndReached}
        on_select={setSelectedOil}
      />

      {renderPreview()}

      {startError && <Alert variant={AlertVariant.DANGER}>{startError}</Alert>}

      <Button
        label="Batch Craft"
        variant={ButtonVariant.SUCCESS}
        additional_css="w-full"
        disabled={!canStart}
        on_click={handleStart}
      />

      <Button
        label="Back"
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full"
        on_click={() => navigation.pop()}
      />
    </div>
  );
};

export default HolyOilsSetScreen;
