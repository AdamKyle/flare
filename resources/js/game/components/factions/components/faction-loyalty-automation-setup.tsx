import React, { ReactNode, useState } from 'react';

import FactionLoyaltyAutomationSetupProps from './types/faction-loyalty-automation-setup-props';
import { explorationAttackTypeOptions } from '../../actions/partials/monster-section/exploration/utils/exploration-attack-type-options';
import { useFactionLoyaltyAutomationActions } from '../api/hooks/use-faction-loyalty-automation-actions';
import { resolveFactionLoyaltyAutomationBlocker } from '../utils/resolve-faction-loyalty-automation-blocker';

import { useGameData } from 'game-data/hooks/use-game-data';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const FactionLoyaltyAutomationSetup = ({
  character_id: characterId,
  faction_loyalty_npc: factionLoyaltyNpc,
}: FactionLoyaltyAutomationSetupProps): ReactNode => {
  const { gameData } = useGameData();
  const { submitting, error, start } =
    useFactionLoyaltyAutomationActions(characterId);

  const [selectedAttackType, setSelectedAttackType] = useState<string | null>(
    null
  );
  const [startMessage, setStartMessage] = useState<string | null>(null);

  const blocker = resolveFactionLoyaltyAutomationBlocker(
    factionLoyaltyNpc,
    gameData?.character?.game_map_id ?? 0,
    gameData?.character?.active_automation ?? null
  );
  const canStart = blocker === null && selectedAttackType !== null;

  const handleAttackTypeSelected = (selectedValue: DropdownItem) => {
    setSelectedAttackType(String(selectedValue.value));
  };

  const handleStart = async () => {
    if (selectedAttackType === null) {
      return;
    }

    setStartMessage(null);

    const response = await start(selectedAttackType);

    if (response === null) {
      return;
    }

    setStartMessage(response.message ?? null);
  };

  const renderFeedback = (): ReactNode => {
    if (error !== null) {
      return <Alert variant={AlertVariant.DANGER}>{error}</Alert>;
    }

    if (startMessage !== null) {
      return <Alert variant={AlertVariant.SUCCESS}>{startMessage}</Alert>;
    }

    if (blocker !== null) {
      return <Alert variant={AlertVariant.INFO}>{blocker}</Alert>;
    }

    return null;
  };

  return (
    <section
      aria-label="Faction Loyalty automation"
      className="flex flex-col gap-3"
    >
      <h4 className="font-semibold text-gray-900 dark:text-gray-100">
        Automate Faction Loyalty
      </h4>
      {renderFeedback()}
      <Dropdown
        aria_label="Attack Type"
        items={explorationAttackTypeOptions}
        on_select={handleAttackTypeSelected}
        selection_placeholder="Select the attack type"
        disabled={blocker !== null}
      />
      <Button
        label="Begin Faction Loyalty Automation"
        variant={ButtonVariant.SUCCESS}
        additional_css="w-full"
        disabled={!canStart || submitting}
        on_click={() => void handleStart()}
      />
    </section>
  );
};

export default FactionLoyaltyAutomationSetup;
