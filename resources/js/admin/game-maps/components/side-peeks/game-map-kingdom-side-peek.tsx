import React, { ReactNode } from 'react';

import GameMapKingdomSidePeekProps from './types/game-map-kingdom-side-peek-props';
import { GameMapKingdomOwnerType } from '../../api/enums/game-map-kingdom-owner-type';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const GameMapKingdomSidePeek = ({
  kingdom,
}: GameMapKingdomSidePeekProps): ReactNode => (
  <div className="px-4">
    <Dl>
      <Dt>Name</Dt>
      <Dd>{kingdom.name}</Dd>
      <Dt>Owner Type</Dt>
      <Dd>
        {kingdom.owner_type === GameMapKingdomOwnerType.Npc ? 'Npc' : 'Player'}
      </Dd>
      <Dt>Npc Owned</Dt>
      <Dd>{kingdom.npc_owned ? 'Yes' : 'No'}</Dd>
      <Dt>X Coordinate</Dt>
      <Dd>{kingdom.x_position}</Dd>
      <Dt>Y Coordinate</Dt>
      <Dd>{kingdom.y_position}</Dd>
    </Dl>
  </div>
);

export default GameMapKingdomSidePeek;
