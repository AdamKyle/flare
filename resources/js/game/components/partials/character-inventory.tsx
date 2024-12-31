import React, { ReactNode } from 'react';

import CharacterInventoryManagement from '../character-sheet/character-inventory-management';
import CharacterInventoryProps from './types/character-inventory-props';

import { GameDataError } from 'game-data/components/game-data-error';
import { useGameData } from 'game-data/hooks/use-game-data';

import Card from 'ui/cards/card';
import ContainerWithTitle from 'ui/container/container-with-title';

const CharacterInventory = ({
  close_inventory,
}: CharacterInventoryProps): ReactNode => {
  const { gameData } = useGameData();

  const characterData = gameData?.character;

  if (!characterData) {
    return <GameDataError />;
  }

  return (
    <ContainerWithTitle
      manageSectionVisibility={close_inventory}
      title={`${characterData.name} Inventory`}
    >
      <Card>
        <CharacterInventoryManagement />
      </Card>
    </ContainerWithTitle>
  );
};

export default CharacterInventory;
