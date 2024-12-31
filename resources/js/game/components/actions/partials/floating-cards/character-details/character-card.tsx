import React, { ReactNode } from 'react';

import CharacterCardDetails from './character-card-details';
import { useManageCharacterCardVisibility } from './hooks/use-manage-character-card-visibility';
import { formatNumberWithCommas } from '../../../../../util/format-number';
import FloatingCard from '../../../components/icon-section/floating-card';

import { GameDataError } from 'game-data/components/game-data-error';
import { useGameData } from 'game-data/hooks/use-game-data';

const CharacterCard = (): ReactNode => {
  const { closeCharacterChard } = useManageCharacterCardVisibility();
  const { gameData } = useGameData();

  const characterData = gameData?.character;

  if (!characterData) {
    return (
      <FloatingCard title="An error occured" close_action={closeCharacterChard}>
        <GameDataError />
      </FloatingCard>
    );
  }

  return (
    <FloatingCard
      title={
        characterData.name +
        ' (Level ' +
        formatNumberWithCommas(characterData.level) +
        '/' +
        formatNumberWithCommas(characterData.max_level) +
        ')'
      }
      close_action={closeCharacterChard}
    >
      <CharacterCardDetails characterData={characterData} />
    </FloatingCard>
  );
};

export default CharacterCard;
