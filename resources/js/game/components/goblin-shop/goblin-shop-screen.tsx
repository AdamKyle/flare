import { isNil } from 'lodash';
import React from 'react';

import { GoblinShopProvider } from './context/goblin-shop-context';
import GoblinShop from './goblin-shop';
import GoblinShopProps from './types/goblin-shop-props';

import { useGameData } from 'game-data/hooks/use-game-data';

const GoblinShopScreen = ({ on_close }: GoblinShopProps) => {
  const { gameData, updateCharacter } = useGameData();

  if (!gameData || !gameData.character) {
    return null;
  }

  return (
    <GoblinShopProvider character={gameData.character} update_character={updateCharacter}>
      <GoblinShop on_close={on_close} />
    </GoblinShopProvider>
  );
};

export default GoblinShopScreen;
