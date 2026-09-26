import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import PlayerGameMapDetailStack from './player-game-map-detail-stack';
import PlayerNpcDetailBodyProps from './types/player-npc-detail-body-props';
import { usePlayerNpcDetail } from '../../../../reusable-components/npc/api/hooks/use-player-npc-detail';
import NpcDetail from '../../../../reusable-components/npc/components/npc-detail';

import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const PlayerNpcDetailBody = ({
  npc_id: npcId,
}: PlayerNpcDetailBodyProps): ReactNode => {
  const { npc, loading, error } = usePlayerNpcDetail(npcId);

  const [nestedGameMapId, setNestedGameMapId] = useState<number | null>(null);

  const handleCloseGameMap = (): void => {
    setNestedGameMapId(null);
  };

  const renderGameMapDetail = (): ReactNode => {
    if (nestedGameMapId === null) {
      return null;
    }

    return (
      <PlayerGameMapDetailStack
        game_map_id={nestedGameMapId}
        on_close={handleCloseGameMap}
      />
    );
  };

  if (loading) {
    return (
      <div className="px-4">
        <InfiniteLoader />
      </div>
    );
  }

  if (error || !npc) {
    return (
      <div className="px-4">
        <ApiErrorAlert
          apiError={error?.message ?? 'Unable to load this NPC.'}
        />
      </div>
    );
  }

  return (
    <div className="flex flex-col gap-4 px-4">
      <h1 className="text-glacier-900 dark:text-glacier-100 text-xl font-semibold">
        {npc.real_name}
      </h1>
      <NpcDetail npc={npc} navigation={{ on_open_map: setNestedGameMapId }} />
      {renderGameMapDetail()}
    </div>
  );
};

export default PlayerNpcDetailBody;
