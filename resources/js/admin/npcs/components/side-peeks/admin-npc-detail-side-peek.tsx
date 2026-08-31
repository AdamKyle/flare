import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import AdminNpcDetailSidePeekProps from './types/admin-npc-detail-side-peek-props';
import { resolveSidePeekComponent } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-mapper';
import { SidePeekComponentRegistrationEnum } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek as SidePeekEventType } from '../../../../game/components/side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../../game/components/side-peeks/base/hooks/use-side-peek-emitter';
import { NpcApiMessages } from '../../api/enums/npc-api-messages';
import { useNpcDetail } from '../../api/hooks/use-npc-detail';
import { useNpcQuests } from '../../api/hooks/use-npc-quests';
import { useNpcRewardItems } from '../../api/hooks/use-npc-reward-items';
import NpcDetailBody from '../npc-detail-body';
import { NpcNestedSelection } from '../types/npc-nested-selection';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import StackedCard from 'ui/cards/stacked-card';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

/**
 * Admin NPC detail side-peek: stacks the shared, permission-neutral factual
 * NPC presentation with an Admin-only Edit action and relationship
 * navigation into other modernized Admin resources. Reuses the exact same
 * `NpcDetailBody` the standalone NPC show screen and the Game Map NPC
 * side-peek already render, so every entry point shows identical content.
 * Relationship navigation opens the target's canonical detail inside a
 * `StackedCard` over this content (rather than replacing it through the
 * global SidePeek emitter), so this component can itself be reused as
 * nested `StackedCard` content and its own relationship clicks never
 * destroy an ancestor's stack. Edit still uses the global SidePeek
 * emitter, matching every other Admin NPC entry point.
 */
const AdminNpcDetailSidePeek = ({
  npc_id: npcId,
  on_npc_changed: onNpcChanged,
}: AdminNpcDetailSidePeekProps): ReactNode => {
  const sidePeekEmitter = useSidePeekEmitter();
  const { npc, loading, error, refresh } = useNpcDetail(npcId);
  const quests = useNpcQuests(npcId);
  const rewardItems = useNpcRewardItems(npcId);
  const [nestedSelection, setNestedSelection] =
    useState<NpcNestedSelection | null>(null);
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
        <StackedCard on_close={handleCloseNested} aria_label="Quest Details">
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
        <StackedCard on_close={handleCloseNested} aria_label="Item Details">
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
      <StackedCard on_close={handleCloseNested} aria_label="Game Map Details">
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
      {renderNestedDetail()}
    </>
  );
};

export default AdminNpcDetailSidePeek;
