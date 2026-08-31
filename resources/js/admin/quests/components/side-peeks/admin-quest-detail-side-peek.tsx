import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import AdminQuestDetailSidePeekProps from './types/admin-quest-detail-side-peek-props';
import { SidePeekComponentRegistrationEnum } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek as SidePeekEventType } from '../../../../game/components/side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../../game/components/side-peeks/base/hooks/use-side-peek-emitter';
import QuestDetail from '../../../../game/reusable-components/quest/components/quest-detail';
import { QuestApiMessages } from '../../api/enums/quest-api-messages';
import { useQuestDetail } from '../../api/hooks/use-quest-detail';
import QuestFormContent from '../forms/quest-form-content';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import StackedCard from 'ui/cards/stacked-card';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

/**
 * Admin Quest detail side-peek: stacks the shared, permission-neutral factual Quest
 * presentation with Admin-only Edit/Add-Child actions and relationship navigation into other
 * modernized Admin resources.
 */
const AdminQuestDetailSidePeek = ({
  quest_id: questId,
  on_quest_changed: onQuestChanged,
}: AdminQuestDetailSidePeekProps): ReactNode => {
  const sidePeekEmitter = useSidePeekEmitter();
  const { quest, loading, error, refresh } = useQuestDetail(questId);
  const [formMode, setFormMode] = useState<'edit' | 'add-child' | null>(null);
  const [announcement, setAnnouncement] = useState('');

  const handleEdit = (): void => {
    setFormMode('edit');
  };

  const handleAddChild = (): void => {
    setFormMode('add-child');
  };

  const handleCloseForm = (): void => {
    setFormMode(null);
  };

  const handleSaved = (): void => {
    refresh();
    onQuestChanged?.();
    setFormMode(null);
    setAnnouncement('Quest saved.');
  };

  const handleOpenQuest = (id: number): void => {
    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_QUEST_DETAIL,
      {
        is_open: true,
        title: 'Quest Details',
        allow_clicking_outside: true,
        quest_id: id,
      }
    );
  };

  const handleOpenItem = (id: number): void => {
    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_ITEM_DETAIL,
      {
        is_open: true,
        title: 'Item Details',
        allow_clicking_outside: true,
        item_id: id,
      }
    );
  };

  const handleOpenMonster = (id: number): void => {
    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_MONSTER_DETAIL,
      {
        is_open: true,
        title: 'Monster Details',
        allow_clicking_outside: true,
        monster_id: id,
      }
    );
  };

  const handleOpenNpc = (id: number): void => {
    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_NPC_DETAIL,
      {
        is_open: true,
        title: 'NPC Details',
        allow_clicking_outside: true,
        npc_id: id,
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

    if (error || !quest) {
      return (
        <div className="px-4">
          <ApiErrorAlert apiError={error?.message ?? QuestApiMessages.Load} />
        </div>
      );
    }

    return (
      <div className="space-y-4">
        <div className="flex justify-center gap-3 py-2">
          <Button
            label="Edit Quest"
            variant={ButtonVariant.PRIMARY}
            additional_css="text-sm px-3 py-1.5"
            on_click={handleEdit}
          />
          <Button
            label="Add Child Quest"
            variant={ButtonVariant.PRIMARY}
            additional_css="text-sm px-3 py-1.5"
            on_click={handleAddChild}
          />
        </div>

        <QuestDetail
          quest={quest}
          navigation={{
            on_open_quest: handleOpenQuest,
            on_open_item: handleOpenItem,
            on_open_monster: handleOpenMonster,
            on_open_npc: handleOpenNpc,
            on_open_map: handleOpenMap,
          }}
        />
      </div>
    );
  };

  const renderForm = (): ReactNode => {
    if (formMode === null) {
      return null;
    }

    return (
      <StackedCard
        on_close={handleCloseForm}
        aria_label={formMode === 'edit' ? 'Edit Quest' : 'Add Child Quest'}
      >
        <QuestFormContent
          quest_id={formMode === 'edit' ? questId : null}
          parent_quest_id={formMode === 'add-child' ? questId : null}
          on_saved={handleSaved}
          on_cancel={handleCloseForm}
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
      {renderForm()}
    </>
  );
};

export default AdminQuestDetailSidePeek;
