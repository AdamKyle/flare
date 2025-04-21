import React, { ReactNode } from 'react';

import Actions from './actions/partials/actions/actions';
import { useManageMonsterStatSectionVisibility } from './actions/partials/monster-stat-section/hooks/use-manage-monster-stat-section-visibility';
import { MonsterStatSection } from './actions/partials/monster-stat-section/monster-stat-section';
import CharacterSheet from './character-sheet/character-sheet';
import { useAttackDetailsVisibility } from './character-sheet/hooks/use-attack-details-visibility';
import { useStatDetailsVisibility } from './character-sheet/hooks/use-stat-details-visibility';
import CharacterStatTypeBreakDown from './character-sheet/partials/character-stat-types/character-stat-type-breakdown';
import Chat from './chat-section/chat';
import GameLoader from './game-loader/game-loader';
import { useCharacterInventoryVisibility } from './hooks/use-character-inventory-visibility';
import { useCharacterSheetVisibility } from './hooks/use-character-sheet-visibility';
import { useGameLoaderVisibility } from './hooks/use-game-loader-visibility';
import { useManageCharacterSheetVisibility } from './hooks/use-manage-character-sheet-visibility';
import CharacterAttackTypeBreakdown from './partials/character-attack-type-breakdown';
import CharacterInventory from './partials/character-inventory';

export const GameCard = (): ReactNode => {
  const { closeCharacterSheet } = useManageCharacterSheetVisibility();

  const { showMonsterStatsSection, showMonsterStats } =
    useManageMonsterStatSectionVisibility();

  const { showCharacterInventory, closeInventory } =
    useCharacterInventoryVisibility();

  const { showAttackType, attackType, closeAttackDetails } =
    useAttackDetailsVisibility();

  const { showStatDetails, statType, closeStatDetails } =
    useStatDetailsVisibility();

  const { showCharacterSheet } = useCharacterSheetVisibility();

  const { showGameLoader } = useGameLoaderVisibility();

  if (showGameLoader) {
    return <GameLoader />;
  }

  if (showCharacterSheet) {
    return (
      <CharacterSheet manageCharacterSheetVisibility={closeCharacterSheet} />
    );
  }

  if (showMonsterStatsSection) {
    return <MonsterStatSection />;
  }

  if (showAttackType && attackType !== null) {
    return (
      <CharacterAttackTypeBreakdown
        close_attack_details={closeAttackDetails}
        attack_type={attackType}
      />
    );
  }

  if (showStatDetails && statType !== null) {
    return (
      <CharacterStatTypeBreakDown
        stat_type={statType}
        close_stat_type={closeStatDetails}
      />
    );
  }

  if (showCharacterInventory) {
    return <CharacterInventory close_inventory={closeInventory} />;
  }

  return (
    <div>
      <Actions showMonsterStats={showMonsterStats} />
      <Chat />
    </div>
  );
};
