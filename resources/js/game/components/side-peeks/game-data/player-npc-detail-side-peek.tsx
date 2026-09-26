import React, { ReactNode } from 'react';

import PlayerNpcDetailBody from './components/player-npc-detail-body';
import PlayerNpcDetailSidePeekProps from './types/player-npc-detail-side-peek-props';

const PlayerNpcDetailSidePeek = ({
  npc_id: npcId,
}: PlayerNpcDetailSidePeekProps): ReactNode => {
  return <PlayerNpcDetailBody npc_id={npcId} />;
};

export default PlayerNpcDetailSidePeek;
