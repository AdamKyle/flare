import React, { ReactNode } from 'react';

import Actions from './actions/partials/actions/actions';
import CharacterSheet from './character-sheet/character-sheet';
import { useCharacterSheetVisibility } from './hooks/use-character-sheet-visibility';
import { useManageCharacterSheetVisibility } from './hooks/use-manage-character-sheet-visibility';

export const GameCard = (): ReactNode => {
  const { closeCharacterSheet } = useManageCharacterSheetVisibility();

  const { showCharacterSheet } = useCharacterSheetVisibility();

  if (showCharacterSheet) {
    return (
      <CharacterSheet manageCharacterSheetVisibility={closeCharacterSheet} />
    );
  }

  return <Actions />;
};
