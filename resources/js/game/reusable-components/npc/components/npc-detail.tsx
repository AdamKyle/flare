import React, { ReactNode } from 'react';

import { NPC_TYPE_LABELS } from '../enums/npc-type';
import NpcDetailProps from '../types/npc-detail-props';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const NpcDetail = ({ npc, navigation }: NpcDetailProps): ReactNode => {
  const renderMap = (): ReactNode => {
    if (!navigation?.on_open_map) {
      return npc.game_map.name;
    }

    return (
      <button
        type="button"
        onClick={() => navigation.on_open_map?.(npc.game_map.id)}
        className="text-danube-700 hover:text-danube-600 dark:text-danube-200 dark:hover:text-danube-100 decoration-danube-400 dark:decoration-danube-500 focus-visible:ring-danube-400 rounded-sm font-medium underline underline-offset-2 focus:outline-none focus-visible:ring-2"
      >
        {npc.game_map.name}
      </button>
    );
  };

  return (
    <section>
      <h2 className="text-glacier-900 dark:text-glacier-100 mb-2 text-sm font-semibold">
        NPC Details
      </h2>
      <Dl>
        <Dt>Map</Dt>
        <Dd>{renderMap()}</Dd>
        <Dt>Type</Dt>
        <Dd>{NPC_TYPE_LABELS[npc.type]}</Dd>
        <Dt>Coordinates</Dt>
        <Dd>
          X {npc.x_position}, Y {npc.y_position}
        </Dd>
      </Dl>
    </section>
  );
};

export default NpcDetail;
