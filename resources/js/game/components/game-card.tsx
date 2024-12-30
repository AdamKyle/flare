import React, { ReactNode } from 'react';

import Actions from './actions/partials/actions/actions';
import { useManageMonsterStatSectionVisibility } from './actions/partials/monster-stat-section/hooks/use-manage-monster-stat-section-visibility';
import { MonsterStatSection } from './actions/partials/monster-stat-section/monster-stat-section';
import CharacterInventoryManagement from './character-sheet/character-inventory-management';
import CharacterSheet from './character-sheet/character-sheet';
import { useCharacterInventoryVisibility } from './hooks/use-character-inventory-visibility';
import { useCharacterSheetVisibility } from './hooks/use-character-sheet-visibility';
import { useManageCharacterSheetVisibility } from './hooks/use-manage-character-sheet-visibility';

import Card from 'ui/cards/card';
import Container from 'ui/container/container';

export const GameCard = (): ReactNode => {
  const { closeCharacterSheet } = useManageCharacterSheetVisibility();

  const { showMonsterStatsSection, showMonsterStats } =
    useManageMonsterStatSectionVisibility();

  const { showCharacterInventory, closeInventory } =
    useCharacterInventoryVisibility();

  const { showCharacterSheet } = useCharacterSheetVisibility();

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
      <Container
        manageSectionVisibility={closeInventory}
        title={'Character Name Inventory'}
      >
        <Card>
          <CharacterInventoryManagement />
        </Card>
      </Container>
    );
  }

  return <Actions showMonsterStats={showMonsterStats} />;
};
