import React, { ReactNode } from 'react';

import NpcFormSidePeekProps from './types/npc-form-side-peek-props';
import NpcFormScreen from '../../screens/npc-form-screen';

import { useCloseSidePeekEmitter } from '../../../../game/components/side-peeks/base/hooks/use-close-side-peek-emitter';

const NpcFormSidePeek = ({
  game_map_id: gameMapId,
  npc_id: npcId,
  on_saved: onSaved,
}: NpcFormSidePeekProps): ReactNode => {
  const { closeSidePeek } = useCloseSidePeekEmitter();

  const handleSaved: NpcFormSidePeekProps['on_saved'] = (npc) => {
    onSaved(npc);
    closeSidePeek();
  };

  return (
    <NpcFormScreen
      game_map_id={gameMapId}
      npc_id={npcId}
      initial_x={null}
      initial_y={null}
      on_saved={handleSaved}
      on_cancel={closeSidePeek}
      embedded
    />
  );
};

export default NpcFormSidePeek;
