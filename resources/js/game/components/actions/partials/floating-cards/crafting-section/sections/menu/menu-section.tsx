import React from 'react';

import MenuSectionProps from './types/menu-section-props';
import { CraftingTypes } from '../../enums/crafting-types';
import { getLocationRestrictedCraftingAction } from '../../shared/utils/get-location-restricted-crafting-action';
import { useBatchCraftingStatus } from '../batch-crafting/api/hooks/use-batch-crafting-status';

import { useGameData } from 'game-data/hooks/use-game-data';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Separator from 'ui/separator/separator';

const MenuSection = ({
  setActiveCraftingType,
  locationRestrictionWarning,
  clearLocationRestrictionWarning,
}: MenuSectionProps) => {
  const { gameData } = useGameData();
  const character = gameData?.character ?? null;

  const { status: batchCraftingStatus } = useBatchCraftingStatus({
    characterId: character?.id ?? 0,
    userId: character?.user_id ?? 0,
  });
  const isBatchCraftingVisible = Boolean(
    batchCraftingStatus?.active || batchCraftingStatus?.is_visible
  );
  const isBatchCraftingRunning = Boolean(batchCraftingStatus?.is_running);

  const isFactionLoyaltyAutomationRunning =
    character?.is_faction_loyalty_automation_running === true;

  const canAccessQueenOfHearts =
    getLocationRestrictedCraftingAction(
      CraftingTypes.QUEEN_OF_HEARTS,
      character
    ) === null;
  const canAccessSeerCamp =
    getLocationRestrictedCraftingAction(CraftingTypes.SEER_CAMP, character) ===
    null;
  const canUseWorkBench =
    getLocationRestrictedCraftingAction(CraftingTypes.WORK_BENCH, character) ===
    null;
  const canAccessLabyrinthOracle =
    getLocationRestrictedCraftingAction(
      CraftingTypes.LABYRINTH_ORACLE,
      character
    ) === null;

  const handleSelectCraftingType = (type: CraftingTypes) => {
    clearLocationRestrictionWarning?.();
    setActiveCraftingType(type);
  };

  const renderLocationRestrictionWarning = () => {
    if (!locationRestrictionWarning) {
      return null;
    }

    return (
      <Alert
        variant={AlertVariant.WARNING}
        closable
        on_close={clearLocationRestrictionWarning}
      >
        {locationRestrictionWarning}
      </Alert>
    );
  };

  return (
    <>
      {renderLocationRestrictionWarning()}
      {isFactionLoyaltyAutomationRunning && (
        <Alert variant={AlertVariant.WARNING}>
          You are currently doing faction loyalty automation and cannot do some
          actions below.
        </Alert>
      )}
      <Button
        label="Batch Craft"
        on_click={() => handleSelectCraftingType(CraftingTypes.BATCH_CRAFTING)}
        variant={
          isBatchCraftingVisible ? ButtonVariant.ACTIVE : ButtonVariant.SUCCESS
        }
        additional_css="w-full my-2"
      />
      <Separator />
      <Button
        label="Craft"
        on_click={() => handleSelectCraftingType(CraftingTypes.CRAFT)}
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full my-2"
        disabled={isFactionLoyaltyAutomationRunning || isBatchCraftingRunning}
      />
      <Button
        label="Enchant"
        on_click={() => handleSelectCraftingType(CraftingTypes.ENCHANT)}
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full my-2"
        disabled={isBatchCraftingRunning}
      />
      {character?.is_alchemy_locked !== true && (
        <Button
          label="Alchemy"
          on_click={() => handleSelectCraftingType(CraftingTypes.ALCHEMY)}
          variant={ButtonVariant.PRIMARY}
          additional_css="w-full my-2"
          disabled={isBatchCraftingRunning}
        />
      )}
      <Button
        label="Trinketry"
        on_click={() => handleSelectCraftingType(CraftingTypes.TRINKETS)}
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full my-2"
        disabled={isBatchCraftingRunning}
      />
      <Button
        label="Gem Crafting"
        on_click={() => handleSelectCraftingType(CraftingTypes.GEM_CRAFTING)}
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full my-2"
        disabled={isBatchCraftingRunning}
      />
      {canAccessQueenOfHearts && (
        <Button
          label="Queen of Hearts"
          on_click={() =>
            handleSelectCraftingType(CraftingTypes.QUEEN_OF_HEARTS)
          }
          variant={ButtonVariant.PRIMARY}
          additional_css="w-full my-2"
          disabled={isBatchCraftingRunning}
        />
      )}
      {canAccessSeerCamp && (
        <Button
          label="Seer Camp"
          on_click={() => handleSelectCraftingType(CraftingTypes.SEER_CAMP)}
          variant={ButtonVariant.PRIMARY}
          additional_css="w-full my-2"
          disabled={isBatchCraftingRunning}
        />
      )}
      {canUseWorkBench && (
        <Button
          label="Work Bench"
          on_click={() => handleSelectCraftingType(CraftingTypes.WORK_BENCH)}
          variant={ButtonVariant.PRIMARY}
          additional_css="w-full my-2"
          disabled={isBatchCraftingRunning}
        />
      )}
      {canAccessLabyrinthOracle && (
        <Button
          label="Labyrinth Oracle"
          on_click={() =>
            handleSelectCraftingType(CraftingTypes.LABYRINTH_ORACLE)
          }
          variant={ButtonVariant.PRIMARY}
          additional_css="w-full my-2"
          disabled={isBatchCraftingRunning}
        />
      )}
    </>
  );
};

export default MenuSection;
