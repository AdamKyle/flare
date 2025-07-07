import { isNil } from 'lodash';
import React from 'react';

import { ShopProvider } from './context/shop-context';
import Shop from './shop';
import ShopScreenProps from './types/shop-screen-props';

import { useGameData } from 'game-data/hooks/use-game-data';

const ShopScreen = ({ close_shop }: ShopScreenProps) => {
  const { gameData } = useGameData();

  const characterId = !isNil(gameData?.character?.id)
    ? gameData.character.id
    : 0;

  return (
    <ShopProvider characterId={characterId}>
      <Shop close_shop={close_shop} />
    </ShopProvider>
  );
};

export default ShopScreen;
