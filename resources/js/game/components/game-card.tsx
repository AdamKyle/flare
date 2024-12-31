import React, { ReactNode } from 'react';

import Actions from './actions/partials/actions/actions';
import { useManageMonsterStatSectionVisibility } from './actions/partials/monster-stat-section/hooks/use-manage-monster-stat-section-visibility';
import { MonsterStatSection } from './actions/partials/monster-stat-section/monster-stat-section';
import CharacterInventoryManagement from './character-sheet/character-inventory-management';
import CharacterSheet from './character-sheet/character-sheet';
import GameLoader from './game-loader/game-loader';
import { useCharacterInventoryVisibility } from './hooks/use-character-inventory-visibility';
import { useCharacterSheetVisibility } from './hooks/use-character-sheet-visibility';
import { useGameLoaderVisibility } from './hooks/use-game-loader-visibility';
import { useManageCharacterSheetVisibility } from './hooks/use-manage-character-sheet-visibility';

import Card from 'ui/cards/card';
import ContainerWithTitle from 'ui/container/container-with-title';

export const GameCard = (): ReactNode => {
  const { closeCharacterSheet } = useManageCharacterSheetVisibility();

  const { showMonsterStatsSection, showMonsterStats } =
    useManageMonsterStatSectionVisibility();

  const { showCharacterInventory, closeInventory } =
    useCharacterInventoryVisibility();

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

  if (showCharacterInventory) {
    return (
      <ContainerWithTitle
        manageSectionVisibility={closeInventory}
        title={'Character Name Inventory'}
      >
        <Card>
          <CharacterInventoryManagement />
        </Card>
      </ContainerWithTitle>
    );
  }

  return <Actions showMonsterStats={showMonsterStats} />;
};
