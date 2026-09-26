import React, { ReactNode } from 'react';

import PlayerGameMapDetailBody from './components/player-game-map-detail-body';
import PlayerGameMapDetailSidePeekProps from './types/player-game-map-detail-side-peek-props';

const PlayerGameMapDetailSidePeek = ({
  game_map_id: gameMapId,
}: PlayerGameMapDetailSidePeekProps): ReactNode => {
  return <PlayerGameMapDetailBody game_map_id={gameMapId} />;
};

export default PlayerGameMapDetailSidePeek;
