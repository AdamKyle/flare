import React, { ReactNode } from 'react';

import CharacterCardDetails from './character-card-details';
import { useManageCharacterCardVisibility } from './hooks/use-manage-character-card-visibility';
import FloatingCard from '../../../components/icon-section/floating-card';

const CharacterCard = (): ReactNode => {
  const { closeCharacterChard } = useManageCharacterCardVisibility();

  return (
    <FloatingCard
      title="Character Name (Lvl: 5,000)"
      close_action={closeCharacterChard}
    >
      <CharacterCardDetails />
    </FloatingCard>
  );
};

export default CharacterCard;
