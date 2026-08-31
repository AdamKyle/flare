import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import AdminNpcDetailSidePeekProps from './types/admin-npc-detail-side-peek-props';
import { SidePeekComponentRegistrationEnum } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek as SidePeekEventType } from '../../../../game/components/side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../../game/components/side-peeks/base/hooks/use-side-peek-emitter';
import { NpcApiMessages } from '../../api/enums/npc-api-messages';
import { useNpcDetail } from '../../api/hooks/use-npc-detail';
import { useNpcQuests } from '../../api/hooks/use-npc-quests';
import { useNpcRewardItems } from '../../api/hooks/use-npc-reward-items';
import NpcDetailBody from '../npc-detail-body';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

/**
 * Admin NPC detail side-peek: stacks the shared, permission-neutral factual
 * NPC presentation with an Admin-only Edit action and relationship
 * navigation into other modernized Admin resources. Reuses the exact same
 * `NpcDetailBody` the standalone NPC show screen and the Game Map NPC
 * side-peek already render, so every entry point shows identical content.
 */
const AdminNpcDetailSidePeek = ({
  npc_id: npcId,
  on_npc_changed: onNpcChanged,
}: AdminNpcDetailSidePeekProps): ReactNode => {
  const sidePeekEmitter = useSidePeekEmitter();
  const { npc, loading, error, refresh } = useNpcDetail(npcId);
  const quests = useNpcQuests(npcId);
  const rewardItems = useNpcRewardItems(npcId);
  const [announcement, setAnnouncement] = useState('');

  const handleEdit = (): void => {
    if (!npc) {
      return;
    }

    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_NPC_FORM,
      {
        is_open: true,
        title: 'Edit NPC',
        allow_clicking_outside: true,
        game_map_id: npc.game_map.id,
        npc_id: npcId,
        on_saved: () => {
          refresh();
          onNpcChanged?.();
          setAnnouncement('NPC saved.');
        },
      }
    );
  };

  const handleOpenItem = (itemId: number, _itemName: string): void => {
    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_ITEM_DETAIL,
      {
        is_open: true,
        title: 'Item Details',
        allow_clicking_outside: true,
        item_id: itemId,
        on_item_changed: () => {
          quests.refresh();
          rewardItems.refresh();
        },
      }
    );
  };

  const handleOpenQuest = (questId: number): void => {
    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_QUEST_DETAIL,
      {
        is_open: true,
        title: 'Quest Details',
        allow_clicking_outside: true,
        quest_id: questId,
      }
    );
  };

  const handleOpenMap = (id: number): void => {
    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_DETAIL,
      {
        is_open: true,
        title: 'Game Map Details',
        allow_clicking_outside: true,
        game_map_id: id,
      }
    );
  };

  const renderContent = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error || !npc) {
      return (
        <div className="px-4">
          <ApiErrorAlert apiError={error?.message ?? NpcApiMessages.Load} />
        </div>
      );
    }

    return (
      <div className="space-y-4 px-4">
        <div className="flex justify-center py-2">
          <Button
            label="Edit NPC"
            variant={ButtonVariant.PRIMARY}
            additional_css="text-sm px-3 py-1.5"
            on_click={handleEdit}
          />
        </div>

        <NpcDetailBody
          npc={npc}
          quests={quests}
          reward_items={rewardItems}
          on_open_item={handleOpenItem}
          on_open_quest={handleOpenQuest}
          on_open_map={handleOpenMap}
        />
      </div>
    );
  };

  return (
    <>
      <p className="sr-only" role="status" aria-live="polite">
        {announcement}
      </p>
      {renderContent()}
    </>
  );
};

export default AdminNpcDetailSidePeek;
