import React, { ReactNode } from 'react';

import GameMapFormContent from '../components/forms/game-map-form-content';
import GameMapFormResponseDefinition from '../definitions/game-map-form-response-definition';
import { GameMapScreens } from '../screen-manager/game-map-screen-constants';
import { useGameMapScreenNavigation } from '../screen-manager/game-map-screen-kit';

const GameMapFormScreen = (): ReactNode => {
  const navigation = useGameMapScreenNavigation();

  const handleSaved = (gameMap: GameMapFormResponseDefinition): void => {
    navigation.replaceWith(GameMapScreens.SHOW, { game_map_id: gameMap.id });
  };

  const handleCancel = (): void => {
    navigation.pop();
  };

  return (
    <GameMapFormContent
      game_map_id={null}
      on_saved={handleSaved}
      on_cancel={handleCancel}
      embedded={false}
    />
  );
};

export default GameMapFormScreen;
