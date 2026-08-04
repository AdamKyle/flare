import React from 'react';

import MenuSectionProps from './types/menu-section-props';
import { CraftingTypes } from '../../enums/crafting-types';
import { getLocationRestrictedCraftingAction } from '../../shared/utils/get-location-restricted-crafting-action';

import { useGameData } from 'game-data/hooks/use-game-data';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const MenuSection = ({
  setActiveCraftingType,
  locationRestrictionWarning,
  clearLocationRestrictionWarning,
}: MenuSectionProps) => {
  const { gameData } = useGameData();
  const character = gameData?.character ?? null;

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
        label="Craft"
        on_click={() => handleSelectCraftingType(CraftingTypes.CRAFT)}
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full my-2"
        disabled={isFactionLoyaltyAutomationRunning}
      />
      <Button
        label="Enchant"
        on_click={() => handleSelectCraftingType(CraftingTypes.ENCHANT)}
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full my-2"
      />
      {character?.is_alchemy_locked !== true && (
        <Button
          label="Alchemy"
          on_click={() => handleSelectCraftingType(CraftingTypes.ALCHEMY)}
          variant={ButtonVariant.PRIMARY}
          additional_css="w-full my-2"
        />
      )}
      <Button
        label="Trinketry"
        on_click={() => handleSelectCraftingType(CraftingTypes.TRINKETS)}
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full my-2"
      />
      <Button
        label="Gem Crafting"
        on_click={() => handleSelectCraftingType(CraftingTypes.GEM_CRAFTING)}
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full my-2"
      />
      {canAccessQueenOfHearts && (
        <Button
          label="Queen of Hearts"
          on_click={() =>
            handleSelectCraftingType(CraftingTypes.QUEEN_OF_HEARTS)
          }
          variant={ButtonVariant.PRIMARY}
          additional_css="w-full my-2"
        />
      )}
      {canAccessSeerCamp && (
        <Button
          label="Seer Camp"
          on_click={() => handleSelectCraftingType(CraftingTypes.SEER_CAMP)}
          variant={ButtonVariant.PRIMARY}
          additional_css="w-full my-2"
        />
      )}
      {canUseWorkBench && (
        <Button
          label="Work Bench"
          on_click={() => handleSelectCraftingType(CraftingTypes.WORK_BENCH)}
          variant={ButtonVariant.PRIMARY}
          additional_css="w-full my-2"
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
        />
      )}
    </>
  );
};

export default MenuSection;
