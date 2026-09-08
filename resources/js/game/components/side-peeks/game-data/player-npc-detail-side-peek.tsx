import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode } from 'react';

import PlayerNpcDetailSidePeekProps from './types/player-npc-detail-side-peek-props';
import { usePlayerNpcDetail } from '../../../reusable-components/npc/api/hooks/use-player-npc-detail';
import NpcDetail from '../../../reusable-components/npc/components/npc-detail';
import { SidePeekComponentRegistrationEnum } from '../base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../base/event-types/side-peek';
import { useSidePeekEmitter } from '../base/hooks/use-side-peek-emitter';

import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const PlayerNpcDetailSidePeek = ({
  npc_id: npcId,
}: PlayerNpcDetailSidePeekProps): ReactNode => {
  const { npc, loading, error } = usePlayerNpcDetail(npcId);
  const sidePeekEmitter = useSidePeekEmitter();

  const handleOpenMap = (gameMapId: number): void => {
    sidePeekEmitter.emit(
      SidePeek.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.PLAYER_GAME_MAP_DETAIL,
      {
        is_open: true,
        title: 'Game Map Details',
        allow_clicking_outside: true,
        game_map_id: gameMapId,
      }
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
      <NpcDetail npc={npc} navigation={{ on_open_map: handleOpenMap }} />
    </div>
  );
};

export default PlayerNpcDetailSidePeek;
