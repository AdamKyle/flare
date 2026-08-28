import React, { ReactNode } from 'react';

import GameMapFormContent from '../forms/game-map-form-content';
import GameMapFormSidePeekProps from './types/game-map-form-side-peek-props';

import { useCloseSidePeekEmitter } from '../../../../game/components/side-peeks/base/hooks/use-close-side-peek-emitter';

const GameMapFormSidePeek = ({
  game_map_id: gameMapId,
  on_saved: onSaved,
}: GameMapFormSidePeekProps): ReactNode => {
  const { closeSidePeek } = useCloseSidePeekEmitter();

  const handleSaved: GameMapFormSidePeekProps['on_saved'] = (gameMap) => {
    onSaved(gameMap);
    closeSidePeek();
  };

  return (
    <GameMapFormContent
      game_map_id={gameMapId}
      on_saved={handleSaved}
      on_cancel={closeSidePeek}
      embedded
    />
  );
};

export default GameMapFormSidePeek;
