import React, { ReactNode, useEffect, useState } from 'react';

import { useCraftSetRecommendation } from '../api/hooks/use-craft-set-recommendation';
import BatchCraftingScreenManager from '../component-mapping/batch-crafting-screen-manager';
import BatchCraftingOutputSummary from '../components/batch-crafting-output-summary';
import CraftSetHandSelector from '../components/craft-set-hand-selector';
import CraftSetPositionSelector from '../components/craft-set-position-selector';
import { BatchCraftingScreenNames } from '../enums/batch-crafting-screen-names';
import { CraftSetPosition } from '../enums/craft-set-position';
import {
  CRAFT_SET_OPTIONAL_HAND_POSITIONS,
  CRAFT_SET_REQUIRED_POSITION_OPTIONS,
} from '../enums/craft-set-positions';
import { CraftAndEnchantSetScreenProps } from '../types/batch-crafting-screen-map';
import { craftSetPositionLabel } from '../utils/batch-crafting-labels';

import { useGameData } from 'game-data/hooks/use-game-data';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import IndeterminateProgressBar from 'ui/progress/indeterminate-progress-bar';

const SECTION_HEADING_CSS =
  'text-sm font-semibold text-gray-900 dark:text-gray-100';

const CraftAndEnchantSetScreen = ({
  output_selection,
}: CraftAndEnchantSetScreenProps): ReactNode => {
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

  const requiredPositionsComplete = CRAFT_SET_REQUIRED_POSITION_OPTIONS.every(
    (position) => typeof selectedPositions[position.key]?.value === 'number'
  );

  const handleSetEnchantments = () => {
    if (!requiredPositionsComplete) {
      return;
    }

    const selectedItems: Record<
      string,
      { item_id: number; item_name: string; crafting_type: string }
    > = {};

    CRAFT_SET_REQUIRED_POSITION_OPTIONS.forEach((positionOption) => {
      const selected = selectedPositions[positionOption.key];

      if (typeof selected?.value !== 'number') {
        return;
      }

      selectedItems[positionOption.key] = {
        item_id: selected.value,
        item_name: selected.label,
        crafting_type: positionOption.crafting_type,
      };
    });

    CRAFT_SET_OPTIONAL_HAND_POSITIONS.forEach((positionOption) => {
      const selected = selectedPositions[positionOption.key];

      if (typeof selected?.value !== 'number') {
        return;
      }

      selectedItems[positionOption.key] = {
        item_id: selected.value,
        item_name: selected.label,
        crafting_type: '',
      };
    });

    navigation.navigateTo(
      BatchCraftingScreenNames.CRAFT_AND_ENCHANT_SET_ENCHANTMENTS,
      {
        set_selection: {
          output_selection,
          selected_items: selectedItems,
        },
      }
    );
  };

  const missingPositionLabels = (recommendation?.missing_positions ?? []).map(
    (position) => craftSetPositionLabel(position) ?? position
  );

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
      <h3 className="text-lg font-semibold">Craft and Enchant Set</h3>
      <p className="text-sm text-gray-600 dark:text-gray-400">
        Your best available items are selected automatically. You can change any
        item before continuing to enchantments.
      </p>

      <BatchCraftingOutputSummary
        title="Enchanted Set Output"
        disposition={output_selection.disposition}
        output_destination={output_selection.output_destination}
        output_set_name={output_selection.output_set_name}
        listing_price={output_selection.listing_price}
      />

      {renderPositionSelectors()}

      <Button
        label="Set Enchantments"
        variant={ButtonVariant.SUCCESS}
        additional_css="w-full"
        disabled={!requiredPositionsComplete}
        on_click={handleSetEnchantments}
      />
    </div>
  );
};

export default CraftAndEnchantSetScreen;
