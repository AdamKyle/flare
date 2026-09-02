import ApiErrorAlert from 'api-handler/components/api-error-alert';
import clsx from 'clsx';
import React, { ReactNode, useState } from 'react';

import { GameMapSidePeekMessages } from './enums/game-map-side-peek-messages';
import GameMapNpcSidePeekProps from './types/game-map-npc-side-peek-props';
import { resolveSidePeekComponent } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-mapper';
import { SidePeekComponentRegistrationEnum } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import NpcDefinition from '../../../npcs/api/definitions/npc-definition';
import { NpcApiMessages } from '../../../npcs/api/enums/npc-api-messages';
import { useNpcDetail } from '../../../npcs/api/hooks/use-npc-detail';
import { useNpcQuests } from '../../../npcs/api/hooks/use-npc-quests';
import { useNpcRewardItems } from '../../../npcs/api/hooks/use-npc-reward-items';
import NpcDetailBody from '../../../npcs/components/npc-detail-body';
import { NpcNestedSelection } from '../../../npcs/components/types/npc-nested-selection';
import NpcFormScreen from '../../../npcs/screens/npc-form-screen';

import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

/**
 * Game Map NPC side-peek: stacks the shared, permission-neutral factual NPC
 * presentation with Game-Map-context Edit/Move actions. Relationship
 * navigation opens the target's canonical detail inside a local
 * `StackedCard` over this content instead of replacing this contextual
 * SidePeek through the global emitter, so this SidePeek's own Move/Edit
 * affordances and scroll position stay intact underneath.
 */
const GameMapNpcSidePeek = ({
  game_map_id: gameMapId,
  npc_id: npcId,
  on_editor_changed: onEditorChanged,
  on_move_requested: onMoveRequested,
}: GameMapNpcSidePeekProps): ReactNode => {
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

  const handleSaved = (updated: NpcDefinition): void => {
    void onEditorChanged();
    refresh();
    setShowEdit(false);
    setAnnouncement(`${updated.real_name ?? GameMapSidePeekMessages.NpcSaved}`);
  };

  const handleMove = (): void => {
    onMoveRequested(npcId);
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
          on_open_quest={handleOpenQuest}
          on_open_map={handleOpenMap}
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

  const isStackActive = showEdit || nestedSelection !== null;

  return (
    <div className="relative flex h-full min-h-0 flex-col overflow-hidden">
      <p className="sr-only" role="status" aria-live="polite">
        {announcement}
      </p>
      <div
        className={clsx(
          'min-h-0 flex-1 px-4 py-4',
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

export default GameMapNpcSidePeek;
