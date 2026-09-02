import ApiErrorAlert from 'api-handler/components/api-error-alert';
import clsx from 'clsx';
import React, { ReactNode, useState } from 'react';

import AdminQuestDetailSidePeekProps from './types/admin-quest-detail-side-peek-props';
import { resolveSidePeekComponent } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-mapper';
import { SidePeekComponentRegistrationEnum } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import QuestDetail from '../../../../game/reusable-components/quest/components/quest-detail';
import { QuestApiMessages } from '../../api/enums/quest-api-messages';
import { useQuestDetail } from '../../api/hooks/use-quest-detail';
import QuestFormContent from '../forms/quest-form-content';
import { QuestNestedSelection } from '../types/quest-nested-selection';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

/**
 * Admin Quest detail side-peek: stacks the shared, permission-neutral factual Quest
 * presentation with Admin-only Edit/Add-Child actions and relationship navigation into other
 * modernized Admin resources. Relationship navigation opens the target's canonical detail
 * inside a `StackedCard` over this content (rather than replacing it through the global
 * SidePeek emitter), so this component can itself be reused as nested `StackedCard` content
 * and its own relationship clicks never destroy an ancestor's stack.
 */
const AdminQuestDetailSidePeek = ({
  quest_id: questId,
  on_quest_changed: onQuestChanged,
}: AdminQuestDetailSidePeekProps): ReactNode => {
  const { quest, loading, error, refresh } = useQuestDetail(questId);
  const [formMode, setFormMode] = useState<'edit' | 'add-child' | null>(null);
  const [nestedSelection, setNestedSelection] =
    useState<QuestNestedSelection | null>(null);
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

  const handleCloseNested = (): void => {
    setNestedSelection(null);
  };

  const renderNestedDetail = (): ReactNode => {
    if (!nestedSelection) {
      return null;
    }

    switch (nestedSelection.type) {
      case 'quest': {
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

      case 'item': {
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
            />
          </StackedCard>
        );
      }

      case 'monster': {
        const NestedMonsterDetail = resolveSidePeekComponent(
          SidePeekComponentRegistrationEnum.ADMIN_MONSTER_DETAIL
        );

        return (
          <StackedCard
            on_close={handleCloseNested}
            aria_label="Monster Details"
            content_mode={StackedCardContentMode.FULL_BLEED}
          >
            <NestedMonsterDetail
              is_open
              title="Monster Details"
              monster_id={nestedSelection.id}
            />
          </StackedCard>
        );
      }

      case 'npc': {
        const NestedNpcDetail = resolveSidePeekComponent(
          SidePeekComponentRegistrationEnum.ADMIN_NPC_DETAIL
        );

        return (
          <StackedCard
            on_close={handleCloseNested}
            aria_label="NPC Details"
            content_mode={StackedCardContentMode.FULL_BLEED}
          >
            <NestedNpcDetail
              is_open
              title="NPC Details"
              npc_id={nestedSelection.id}
            />
          </StackedCard>
        );
      }

      default: {
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
      }
    }
  };

  const renderContent = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error || !quest) {
      return (
        <ApiErrorAlert apiError={error?.message ?? QuestApiMessages.Load} />
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
            on_open_quest: (id) => setNestedSelection({ type: 'quest', id }),
            on_open_item: (id) => setNestedSelection({ type: 'item', id }),
            on_open_monster: (id) =>
              setNestedSelection({ type: 'monster', id }),
            on_open_npc: (id) => setNestedSelection({ type: 'npc', id }),
            on_open_map: (id) => setNestedSelection({ type: 'map', id }),
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

  const isStackActive = formMode !== null || nestedSelection !== null;

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
      {renderForm()}
      {renderNestedDetail()}
    </div>
  );
};

export default AdminQuestDetailSidePeek;
