import { debounce } from 'lodash';
import React, { ReactNode, useEffect, useMemo, useRef, useState } from 'react';

import BatchCraftingOutputSummary from './batch-crafting-output-summary';
import CraftSetHandSelector from './craft-set-hand-selector';
import CraftSetPositionSelector from './craft-set-position-selector';
import { useCraftSetPreview } from '../api/hooks/use-craft-set-preview';
import { useCraftSetRecommendation } from '../api/hooks/use-craft-set-recommendation';
import { useStartBatchCrafting } from '../api/hooks/use-start-batch-crafting';
import BatchCraftingScreenManager from '../component-mapping/batch-crafting-screen-manager';
import { BatchCraftingScreenNames } from '../enums/batch-crafting-screen-names';
import { CraftSetPosition } from '../enums/craft-set-position';
import {
  CRAFT_SET_OPTIONAL_HAND_POSITIONS,
  CRAFT_SET_REQUIRED_POSITION_OPTIONS,
} from '../enums/craft-set-positions';
import { CraftSetScreenProps } from '../types/batch-crafting-screen-map';
import { craftSetPositionLabel } from '../utils/batch-crafting-labels';
import { buildCraftSetRequest } from '../utils/build-craft-set-request';

import { useGameData } from 'game-data/hooks/use-game-data';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import IndeterminateProgressBar from 'ui/progress/indeterminate-progress-bar';

const PREVIEW_DEBOUNCE_MS = 300;

const SECTION_HEADING_CSS =
  'text-sm font-semibold text-gray-900 dark:text-gray-100';

const CraftSetForm = ({ output_selection }: CraftSetScreenProps): ReactNode => {
  const { gameData } = useGameData();
  const characterId = gameData?.character?.id ?? 0;
  const navigation = BatchCraftingScreenManager.useScreenNavigation();

  const [selectedPositions, setSelectedPositions] = useState<
    Partial<Record<CraftSetPosition, DropdownItem | null>>
  >({});

  const {
    recommendation,
    loading: recommendationLoading,
    error: recommendationError,
  } = useCraftSetRecommendation(characterId);

  const {
    preview,
    loading: previewLoading,
    error: previewError,
    fetchPreview,
    clearPreview,
  } = useCraftSetPreview(characterId);
  const {
    starting,
    error: startError,
    start,
  } = useStartBatchCrafting(characterId);

  useEffect(() => {
    if (!recommendation) {
      return;
    }

    setSelectedPositions((current) => {
      const next = { ...current };

      recommendation.positions.forEach((recommendedPosition) => {
        if (
          next[recommendedPosition.position] === null ||
          next[recommendedPosition.position] === undefined
        ) {
          next[recommendedPosition.position] = {
            label: recommendedPosition.item_name,
            value: recommendedPosition.item_id,
          };
        }
      });

      return next;
    });
  }, [recommendation]);

  const request = useMemo(
    () =>
      buildCraftSetRequest({
        selectedPositions,
        outputSelection: output_selection,
      }),
    [selectedPositions, output_selection]
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

  const handlePositionSelect = (
    position: CraftSetPosition,
    item: DropdownItem
  ) => {
    setSelectedPositions((current) => ({ ...current, [position]: item }));
  };

  const handleHandSelect = (
    position: CraftSetPosition,
    item: DropdownItem | null
  ) => {
    setSelectedPositions((current) => ({ ...current, [position]: item }));
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

  const canStart =
    request !== null &&
    preview !== null &&
    !previewLoading &&
    preview.blockers.length === 0 &&
    !starting;

  const missingPositionLabels = (recommendation?.missing_positions ?? []).map(
    (position) => craftSetPositionLabel(position) ?? position
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
        <dt className="text-gray-600 dark:text-gray-400">Positions</dt>
        <dd>
          {preview.included_position_count} / {preview.total_position_count}
        </dd>
        <dt className="text-gray-600 dark:text-gray-400">Total Cost</dt>
        <dd>{preview.total_cost.toLocaleString()} Gold</dd>
        <dt className="text-gray-600 dark:text-gray-400">Available Gold</dt>
        <dd>{preview.available_gold.toLocaleString()} Gold</dd>
        {preview.blockers.map((blocker) => (
          <dd key={blocker} className="col-span-2">
            <Alert variant={AlertVariant.DANGER}>{blocker}</Alert>
          </dd>
        ))}
      </dl>
    );
  };

  const renderMissingPositionsAlert = () => {
    if (missingPositionLabels.length === 0) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.DANGER}>
        No craftable item is currently available for:{' '}
        {missingPositionLabels.join(', ')}.
      </Alert>
    );
  };

  const renderPositionSelectors = () => {
    if (recommendationLoading) {
      return (
        <IndeterminateProgressBar
          label="Selecting your best craftable set"
          variant={ProgressBarVariant.PRIMARY}
        />
      );
    }

    return (
      <div className="space-y-4">
        {recommendationError && (
          <Alert variant={AlertVariant.DANGER}>{recommendationError}</Alert>
        )}

        {renderMissingPositionsAlert()}

        <div className="space-y-3">
          <p className={SECTION_HEADING_CSS}>Optional Hands</p>
          {CRAFT_SET_OPTIONAL_HAND_POSITIONS.map((position) => (
            <CraftSetHandSelector
              key={position.key}
              label={position.label}
              selected_item={selectedPositions[position.key] ?? null}
              on_select={(item) => handleHandSelect(position.key, item)}
            />
          ))}
        </div>

        <div className="space-y-3">
          <p className={SECTION_HEADING_CSS}>Set Items</p>
          {CRAFT_SET_REQUIRED_POSITION_OPTIONS.map((position) => (
            <CraftSetPositionSelector
              key={position.key}
              label={position.label}
              crafting_type={position.crafting_type}
              armour_type={position.armour_type ?? null}
              item_type={position.item_type ?? null}
              selected_item={selectedPositions[position.key] ?? null}
              on_select={(item) => handlePositionSelect(position.key, item)}
            />
          ))}
        </div>
      </div>
    );
  };

  return (
    <div className="space-y-4">
      <h3 className="text-lg font-semibold">Craft Set</h3>
      <p className="text-sm text-gray-600 dark:text-gray-400">
        Your best available items are selected automatically. You can change any
        item before starting.
      </p>

      <BatchCraftingOutputSummary
        title="Crafted Set Output"
        disposition={output_selection.disposition}
        output_destination={output_selection.output_destination}
        output_set_name={output_selection.output_set_name}
      />

      {renderPositionSelectors()}

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

export default CraftSetForm;
