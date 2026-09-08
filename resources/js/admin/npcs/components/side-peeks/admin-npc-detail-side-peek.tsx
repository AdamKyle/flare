import ApiErrorAlert from 'api-handler/components/api-error-alert';
import clsx from 'clsx';
import React, { ReactNode, useState } from 'react';

import AdminNpcDetailSidePeekProps from './types/admin-npc-detail-side-peek-props';
import { resolveSidePeekComponent } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-mapper';
import { SidePeekComponentRegistrationEnum } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { NpcApiMessages } from '../../api/enums/npc-api-messages';
import { useNpcDetail } from '../../api/hooks/use-npc-detail';
import { useNpcQuests } from '../../api/hooks/use-npc-quests';
import { useNpcRewardItems } from '../../api/hooks/use-npc-reward-items';
import NpcFormScreen from '../../screens/npc-form-screen';
import NpcDetailBody from '../npc-detail-body';
import { NpcNestedSelection } from '../types/npc-nested-selection';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const AdminNpcDetailSidePeek = ({
  npc_id: npcId,
  on_npc_changed: onNpcChanged,
}: AdminNpcDetailSidePeekProps): ReactNode => {
  const { npc, loading, error, refresh } = useNpcDetail(npcId);
  const quests = useNpcQuests(npcId);
  const rewardItems = useNpcRewardItems(npcId);
  const [showEdit, setShowEdit] = useState(false);
  const [nestedSelection, setNestedSelection] =
    useState<NpcNestedSelection | null>(null);
  const [announcement, setAnnouncement] = useState('');

  const handleEdit = (): void => {
    setShowEdit(true);
  };

  const handleCloseEdit = (): void => {
    setShowEdit(false);
  };

  const handleSaved = (): void => {
    refresh();
    onNpcChanged?.();
    setShowEdit(false);
    setAnnouncement('NPC saved.');
  };

  const handleOpenItem = (itemId: number): void => {
    setNestedSelection({
      type: 'item',
      id: itemId,
      on_changed: () => {
        quests.refresh();
        rewardItems.refresh();
      },
    });
  };

  const handleOpenQuest = (questId: number): void => {
    setNestedSelection({ type: 'quest', id: questId });
  };

  const handleOpenMap = (id: number): void => {
    setNestedSelection({ type: 'map', id });
  };

  const handleCloseNested = (): void => {
    setNestedSelection(null);
  };

  const renderNestedDetail = (): ReactNode => {
    if (!nestedSelection) {
      return null;
    }

    if (nestedSelection.type === 'quest') {
      const NestedQuestDetail = resolveSidePeekComponent(
        SidePeekComponentRegistrationEnum.ADMIN_QUEST_DETAIL
      );

      return (
        <StackedCard
          on_close={handleCloseNested}
          aria_label="Quest Details"
          content_mode={StackedCardContentMode.FULL_BLEED}
        >
          <NestedQuestDetail
            is_open
            title="Quest Details"
            quest_id={nestedSelection.id}
          />
        </StackedCard>
      );
    }

    if (nestedSelection.type === 'item') {
      const NestedItemDetail = resolveSidePeekComponent(
        SidePeekComponentRegistrationEnum.ADMIN_ITEM_DETAIL
      );

      return (
        <StackedCard
          on_close={handleCloseNested}
          aria_label="Item Details"
          content_mode={StackedCardContentMode.FULL_BLEED}
        >
          <NestedItemDetail
            is_open
            title="Item Details"
            item_id={nestedSelection.id}
            on_item_changed={nestedSelection.on_changed}
          />
        </StackedCard>
      );
    }

    const NestedGameMapDetail = resolveSidePeekComponent(
      SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_DETAIL
    );

    return (
      <StackedCard
        on_close={handleCloseNested}
        aria_label="Game Map Details"
        content_mode={StackedCardContentMode.FULL_BLEED}
      >
        <NestedGameMapDetail
          is_open
          title="Game Map Details"
          game_map_id={nestedSelection.id}
        />
      </StackedCard>
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
      <div className="space-y-4">
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

  const renderEdit = (): ReactNode => {
    if (!showEdit || !npc) {
      return null;
    }

    return (
      <StackedCard on_close={handleCloseEdit} aria_label="Edit NPC">
        <NpcFormScreen
          game_map_id={npc.game_map.id}
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

  const isStackActive = showEdit || nestedSelection !== null;

  return (
    <div className="relative flex h-full min-h-0 flex-col overflow-hidden">
      <p className="sr-only" role="status" aria-live="polite">
        {announcement}
      </p>
      <div
        className={clsx(
          'min-h-0 flex-1 px-4 py-4 sm:px-5',
          isStackActive ? 'overflow-hidden' : 'overflow-y-auto'
        )}
      >
        {renderContent()}
      </div>
      {renderEdit()}
      {renderNestedDetail()}
    </div>
  );
};

export default AdminNpcDetailSidePeek;
