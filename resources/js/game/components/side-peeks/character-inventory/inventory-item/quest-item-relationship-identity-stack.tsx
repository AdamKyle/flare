import React, { ReactNode } from 'react';

import QuestItemRelationshipIdentityStackProps from './types/quest-item-relationship-identity-stack-props';
import PlayerGameMapDetailStack from '../../game-data/components/player-game-map-detail-stack';
import PlayerLocationDetailStack from '../../game-data/components/player-location-detail-stack';
import PlayerMonsterDetailStack from '../../game-data/components/player-monster-detail-stack';

const QuestItemRelationshipIdentityStack = ({
  kind,
  id,
  on_close: onClose,
}: QuestItemRelationshipIdentityStackProps): ReactNode => {
  if (kind === 'location') {
    return <PlayerLocationDetailStack location_id={id} on_close={onClose} />;
  }

  if (kind === 'monster') {
    return <PlayerMonsterDetailStack monster_id={id} on_close={onClose} />;
  }

  return <PlayerGameMapDetailStack game_map_id={id} on_close={onClose} />;
};

export default QuestItemRelationshipIdentityStack;
