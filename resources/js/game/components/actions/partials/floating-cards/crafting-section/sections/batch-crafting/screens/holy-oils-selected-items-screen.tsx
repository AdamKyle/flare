import { debounce } from 'lodash';
import React, { ReactNode, useEffect, useMemo, useRef, useState } from 'react';

import { useHolyOilsApi } from '../../work-bench/api/hooks/use-holy-oils-api';
import { useWorkBenchItemsApi } from '../../work-bench/api/hooks/use-work-bench-items-api';
import { useHolyOilsSelectedItemsPreview } from '../api/hooks/use-holy-oils-selected-items-preview';
import { useStartBatchCrafting } from '../api/hooks/use-start-batch-crafting';
import BatchCraftingScreenManager from '../component-mapping/batch-crafting-screen-manager';
import { BatchCraftingDisposition } from '../enums/batch-crafting-disposition';
import { BatchCraftingScreenNames } from '../enums/batch-crafting-screen-names';
import { dispositionLabel } from '../utils/batch-crafting-labels';
import { buildHolyOilsSelectedItemsRequest } from '../utils/build-holy-oils-selected-items-request';

import { useGameData } from 'game-data/hooks/use-game-data';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LinkButton from 'ui/buttons/link-button';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import IndeterminateProgressBar from 'ui/progress/indeterminate-progress-bar';

const PREVIEW_DEBOUNCE_MS = 300;

const DISPOSITION_OPTIONS: DropdownItem[] = [
  BatchCraftingDisposition.KEEP,
  BatchCraftingDisposition.SELL,
  BatchCraftingDisposition.DESTROY,
  BatchCraftingDisposition.LIST,
  BatchCraftingDisposition.DISENCHANT,
].map((disposition) => ({
  label: dispositionLabel(disposition),
  value: disposition,
}));

const toggleId = (ids: number[], id: number): number[] =>
  ids.includes(id) ? ids.filter((existing) => existing !== id) : [...ids, id];

const HolyOilsSelectedItemsScreen = (): ReactNode => {
  const { gameData } = useGameData();
  const characterId = gameData?.character?.id ?? 0;
  const navigation = BatchCraftingScreenManager.useScreenNavigation();

  const [disposition, setDisposition] = useState<DropdownItem | null>(null);
  const [listingPriceText, setListingPriceText] = useState('');
  const [targetSlotIds, setTargetSlotIds] = useState<number[]>([]);
  const [oilSlotIds, setOilSlotIds] = useState<number[]>([]);

  const targetItems = useWorkBenchItemsApi({ character_id: characterId });
  const holyOils = useHolyOilsApi({ character_id: characterId });

  const {
    preview,
    loading: previewLoading,
    error: previewError,
    fetchPreview,
    clearPreview,
  } = useHolyOilsSelectedItemsPreview(characterId);
  const {
    starting,
    error: startError,
    start,
  } = useStartBatchCrafting(characterId);

  const selectedDisposition =
    typeof disposition?.value === 'string'
      ? (disposition.value as BatchCraftingDisposition)
      : null;

  const request = useMemo(
    () =>
      buildHolyOilsSelectedItemsRequest({
        targetSlotIds,
        oilSlotIds,
        disposition: selectedDisposition,
        listingPriceText,
      }),
    [targetSlotIds, oilSlotIds, selectedDisposition, listingPriceText]
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

  const renderTargetList = () => (
    <fieldset>
      <legend
        id="holy-oils-target-legend"
        className="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
      >
        Target Items
      </legend>
      <div className="max-h-48 space-y-1 overflow-y-auto rounded-md border border-gray-500 p-2 dark:border-gray-700">
        {targetItems.loadedItems.map((item) => (
          <label key={item.slot_id} className="flex items-center gap-2 text-sm">
            <input
              type="checkbox"
              checked={targetSlotIds.includes(item.slot_id)}
              onChange={() =>
                setTargetSlotIds((current) => toggleId(current, item.slot_id))
              }
              aria-labelledby="holy-oils-target-legend"
            />
            {item.name}
          </label>
        ))}
        {targetItems.canLoadMore && (
          <LinkButton
            label="Load more"
            variant={ButtonVariant.PRIMARY}
            on_click={targetItems.onEndReached}
          />
        )}
        {!targetItems.loading && targetItems.loadedItems.length === 0 && (
          <p className="text-sm text-gray-600 dark:text-gray-400">
            No eligible target items found.
          </p>
        )}
      </div>
    </fieldset>
  );

  const renderOilList = () => (
    <fieldset>
      <legend
        id="holy-oils-oil-legend"
        className="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
      >
        Holy Oils
      </legend>
      <div className="max-h-48 space-y-1 overflow-y-auto rounded-md border border-gray-500 p-2 dark:border-gray-700">
        {holyOils.loadedItems.map((item) => (
          <label key={item.id} className="flex items-center gap-2 text-sm">
            <input
              type="checkbox"
              checked={oilSlotIds.includes(item.id)}
              onChange={() =>
                setOilSlotIds((current) => toggleId(current, item.id))
              }
              aria-labelledby="holy-oils-oil-legend"
            />
            {item.name} (x{item.stack_amount})
          </label>
        ))}
        {holyOils.canLoadMore && (
          <LinkButton
            label="Load more"
            variant={ButtonVariant.PRIMARY}
            on_click={holyOils.onEndReached}
          />
        )}
        {!holyOils.loading && holyOils.loadedItems.length === 0 && (
          <p className="text-sm text-gray-600 dark:text-gray-400">
            No Holy Oils found.
          </p>
        )}
      </div>
    </fieldset>
  );

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
      <h3 className="text-lg font-semibold">Holy Oils — Selected Items</h3>

      <fieldset>
        <legend
          id="holy-oils-selected-disposition-legend"
          className="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
        >
          Disposition
        </legend>
        <Dropdown
          aria_labelled_by="holy-oils-selected-disposition-legend"
          items={DISPOSITION_OPTIONS}
          on_select={setDisposition}
          pre_selected_item={disposition ?? undefined}
          selection_placeholder="Select a disposition"
        />
      </fieldset>

      {selectedDisposition === BatchCraftingDisposition.LIST && (
        <div>
          <label
            htmlFor="holy-oils-selected-listing-price"
            className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            Listing Price
          </label>
          <input
            id="holy-oils-selected-listing-price"
            type="number"
            min={1}
            value={listingPriceText}
            onChange={(event) => setListingPriceText(event.target.value)}
            className="w-full rounded-md border border-gray-500 bg-white p-2 text-gray-900 focus:ring-2 focus:ring-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white"
          />
        </div>
      )}

      {renderTargetList()}
      {renderOilList()}
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

export default HolyOilsSelectedItemsScreen;
