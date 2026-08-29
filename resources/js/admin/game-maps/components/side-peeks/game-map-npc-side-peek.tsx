import React, { ReactNode, useState } from 'react';

import { GameMapSidePeekMessages } from './enums/game-map-side-peek-messages';
import GameMapNpcSidePeekProps from './types/game-map-npc-side-peek-props';
import NpcDefinition from '../../../npcs/api/definitions/npc-definition';
import { NpcApiMessages } from '../../../npcs/api/enums/npc-api-messages';
import { useNpcDetail } from '../../../npcs/api/hooks/use-npc-detail';
import { useNpcQuests } from '../../../npcs/api/hooks/use-npc-quests';
import { useNpcRewardItems } from '../../../npcs/api/hooks/use-npc-reward-items';
import NpcDetailBody from '../../../npcs/components/npc-detail-body';
import NpcFormScreen from '../../../npcs/screens/npc-form-screen';

import { SidePeekComponentRegistrationEnum } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek as SidePeekEventType } from '../../../../game/components/side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../../game/components/side-peeks/base/hooks/use-side-peek-emitter';

import ApiErrorAlert from 'api-handler/components/api-error-alert';
import StackedCard from 'ui/cards/stacked-card';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const GameMapNpcSidePeek = ({
  game_map_id: gameMapId,
  npc_id: npcId,
  on_editor_changed: onEditorChanged,
  on_move_requested: onMoveRequested,
}: GameMapNpcSidePeekProps): ReactNode => {
  const sidePeekEmitter = useSidePeekEmitter();
  const { npc, loading, error, refresh } = useNpcDetail(npcId);
  const quests = useNpcQuests(npcId);
  const rewardItems = useNpcRewardItems(npcId);
  const [showEdit, setShowEdit] = useState(false);
  const [announcement, setAnnouncement] = useState('');

  const handleEdit = (): void => {
    setShowEdit(true);
  };

  const handleCloseEdit = (): void => {
    setShowEdit(false);
  };

  const handleSaved = (updated: NpcDefinition): void => {
    void onEditorChanged();
    refresh();
    setShowEdit(false);
    setAnnouncement(`${updated.real_name ?? GameMapSidePeekMessages.NpcSaved}`);
  };

  const handleMove = (): void => {
    onMoveRequested(npcId);
  };

  const handleOpenItem = (itemId: number, itemName: string): void => {
    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_ITEM_DETAIL,
      {
        is_open: true,
        title: itemName,
        allow_clicking_outside: true,
        item_id: itemId,
        on_item_changed: () => {
          quests.refresh();
          rewardItems.refresh();
        },
      }
    );
  };

  const renderContent = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error || !npc) {
      return <ApiErrorAlert apiError={error?.message ?? NpcApiMessages.Load} />;
    }

    return (
      <div className="space-y-4 px-4">
        <div className="flex items-center gap-3">
          <button
            type="button"
            onClick={handleEdit}
            className="focus-visible:ring-danube-400 bg-danube-600 hover:bg-danube-500 rounded-md px-3 py-2 text-sm font-medium text-white focus:outline-none focus-visible:ring-2"
          >
            Edit
          </button>
          <button
            type="button"
            onClick={handleMove}
            className="focus-visible:ring-glacier-400 border-glacier-300 text-glacier-700 hover:bg-glacier-50 dark:border-glacier-700 dark:text-glacier-200 dark:hover:bg-glacier-900 rounded-md border px-3 py-2 text-sm font-medium focus:outline-none focus-visible:ring-2"
          >
            Move
          </button>
        </div>

        <NpcDetailBody
          npc={npc}
          quests={quests}
          reward_items={rewardItems}
          on_open_item={handleOpenItem}
        />
      </div>
    );
  };

  const renderEdit = (): ReactNode => {
    if (!showEdit) {
      return null;
    }

    return (
      <StackedCard on_close={handleCloseEdit}>
        <NpcFormScreen
          game_map_id={gameMapId}
          npc_id={npcId}
          initial_x={null}
          initial_y={null}
          on_saved={handleSaved}
          on_cancel={handleCloseEdit}
          embedded
        />
      </StackedCard>
    );
  };

  return (
    <>
      <p className="sr-only" role="status" aria-live="polite">
        {announcement}
      </p>
      {renderContent()}
      {renderEdit()}
    </>
  );
};

export default GameMapNpcSidePeek;
