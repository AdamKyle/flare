import React, { ReactNode } from 'react';

import { CharacterStatTypeDetails } from './character-stat-type-details';
import CharacterStatTypeBreakdownProps from './types/character-stat-type-breakdown-props';
import { getStatName } from '../../enums/stat-types';

import { GameDataError } from 'game-data/components/game-data-error';
import { useGameData } from 'game-data/hooks/use-game-data';

import Card from 'ui/cards/card';
import ContainerWithTitle from 'ui/container/container-with-title';

const CharacterStatTypeBreakDown = ({
  stat_type,
  close_stat_type,
}: CharacterStatTypeBreakdownProps): ReactNode => {
  const { gameData } = useGameData();

  const characterData = gameData?.character;

  if (!characterData) {
    return <GameDataError />;
  }

  return (
    <ContainerWithTitle
      manageSectionVisibility={close_stat_type}
      title={`${characterData.name} ${getStatName(stat_type)} break down`}
    >
      <Card>
        <CharacterStatTypeDetails stat_type={stat_type} />
      </Card>
    </ContainerWithTitle>
  );
};

export default CharacterStatTypeBreakDown;
