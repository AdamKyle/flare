import React, { ReactNode, useState } from 'react';

import BackpackItems from './backpack-items';
import QuestItems from './quest-items';

import { useGameData } from 'game-data/hooks/use-game-data';

const BackPack = (): ReactNode => {
  const { gameData, updateCharacter } = useGameData();

  const [isShowingInventory, setIsShowingInventory] = useState(true);

  if (!gameData?.character) {
    return null;
  }

  if (isShowingInventory) {
    return (
      <BackpackItems
        character={gameData.character}
        update_character={updateCharacter}
        on_switch_view={setIsShowingInventory}
      />
    );
  }

  return (
    <QuestItems
      character={gameData.character}
      on_switch_view={setIsShowingInventory}
    />
  );
};

export default BackPack;
