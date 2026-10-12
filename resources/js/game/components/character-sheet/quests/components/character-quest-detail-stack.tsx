import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import CharacterQuestHandInActions from './character-quest-hand-in-actions';
import CharacterQuestDetailStackProps from './types/character-quest-detail-stack-props';
import { QuestRelatedItemDefinition } from '../../../../reusable-components/quest/api/definitions/quest-detail-definition';
import QuestDetail from '../../../../reusable-components/quest/components/quest-detail';
import QuestItemDetailStack from '../../../side-peeks/character-inventory/inventory-item/quest-item-detail-stack';
import PlayerGameMapDetailStack from '../../../side-peeks/game-data/components/player-game-map-detail-stack';
import PlayerNpcDetailStack from '../../../side-peeks/game-data/components/player-npc-detail-stack';
import ItemDetailsStack from '../../../side-peeks/item-details/components/item-details-stack';
import { InventoryItemTypes } from '../../partials/character-inventory/enums/inventory-item-types';
import { useCharacterQuestDetail } from '../api/hooks/use-character-quest-detail';
import { useHandInCharacterQuest } from '../api/hooks/use-hand-in-character-quest';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import SidePeekOptionsFooter from 'ui/side-peek/options/components/side-peek-options-footer';
import SidePeekOptionDefinition from 'ui/side-peek/options/types/side-peek-option-definition';

const CharacterQuestDetailStack = ({
  character_id: characterId,
  quest_id: questId,
  completed_quest_ids: completedQuestIds,
  on_close: onClose,
  on_completed_quests_change: onCompletedQuestsChange,
}: CharacterQuestDetailStackProps): ReactNode => {
  const [nestedQuestId, setNestedQuestId] = useState<number | null>(null);
  const [nestedNpcId, setNestedNpcId] = useState<number | null>(null);
  const [nestedGameMapId, setNestedGameMapId] = useState<number | null>(null);
  const [nestedItemId, setNestedItemId] = useState<number | null>(null);
  const [nestedQuestItem, setNestedQuestItem] =
    useState<QuestRelatedItemDefinition | null>(null);

  const {
    quest,
    completedQuestIds: detailCompletedQuestIds,
    readiness,
    questItemOwnership,
    loading,
    error,
    refresh,
  } = useCharacterQuestDetail({ characterId, questId });

  const {
    handingIn,
    error: handInError,
    successMessage: handInSuccessMessage,
    handInQuest,
  } = useHandInCharacterQuest({ characterId, questId });

  const isCompleted =
    completedQuestIds.includes(questId) ||
    detailCompletedQuestIds.includes(questId);

  const handleHandIn = async (): Promise<void> => {
    const response = await handInQuest();

    if (!response) {
      return;
    }

    onCompletedQuestsChange(response.completed_quests);
    refresh();
  };

  const renderNestedQuest = (): ReactNode => {
    if (nestedQuestId === null) {
      return null;
    }

    return (
      <CharacterQuestDetailStack
        character_id={characterId}
        quest_id={nestedQuestId}
        completed_quest_ids={detailCompletedQuestIds}
        on_close={() => setNestedQuestId(null)}
        on_completed_quests_change={onCompletedQuestsChange}
      />
    );
  };

  const renderNestedNpc = (): ReactNode => {
    if (nestedNpcId === null) {
      return null;
    }

    return (
      <PlayerNpcDetailStack
        npc_id={nestedNpcId}
        on_close={() => setNestedNpcId(null)}
      />
    );
  };

  const renderNestedGameMap = (): ReactNode => {
    if (nestedGameMapId === null) {
      return null;
    }

    return (
      <PlayerGameMapDetailStack
        game_map_id={nestedGameMapId}
        on_close={() => setNestedGameMapId(null)}
      />
    );
  };

  const renderNestedItem = (): ReactNode => {
    if (nestedItemId === null) {
      return null;
    }

    return (
      <ItemDetailsStack
        item_id={nestedItemId}
        on_close={() => setNestedItemId(null)}
      />
    );
  };

  const renderNestedQuestItem = (): ReactNode => {
    if (!nestedQuestItem) {
      return null;
    }

    return (
      <QuestItemDetailStack
        quest_item={nestedQuestItem}
        on_close={() => setNestedQuestItem(null)}
      />
    );
  };

  const handleOpenItem = (itemId: number): void => {
    if (!quest) {
      return;
    }

    const questItems = [
      quest.requirements.primary_item,
      quest.requirements.secondary_item,
      quest.rewards.item?.type === InventoryItemTypes.QUEST
        ? quest.rewards.item
        : null,
    ].filter((item): item is QuestRelatedItemDefinition => item !== null);

    const questItem = questItems.find((item) => item.item_id === itemId);

    if (questItem) {
      setNestedQuestItem(questItem);
      return;
    }

    setNestedItemId(itemId);
  };

  const renderContent = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error || !quest || !readiness) {
      return (
        <ApiErrorAlert
          apiError={error?.message ?? 'Unable to load this Quest.'}
        />
      );
    }

    return (
      <div className="flex flex-col gap-4">
        <CharacterQuestHandInActions
          is_completed={isCompleted}
          readiness={readiness}
          handing_in={handingIn}
          success_message={handInSuccessMessage}
          error_message={handInError}
          on_hand_in={() => void handleHandIn()}
          show_hand_in_button={false}
        />
        <QuestDetail
          quest={quest}
          completed_quest_ids={detailCompletedQuestIds}
          presentation="side-peek"
          navigation={{
            on_open_quest: setNestedQuestId,
            on_open_npc: setNestedNpcId,
            on_open_map: setNestedGameMapId,
            on_open_item: handleOpenItem,
          }}
          quest_item_ownership={questItemOwnership}
        />
      </div>
    );
  };

  const footerOptions: SidePeekOptionDefinition[] =
    quest && readiness && !isCompleted
      ? [
          {
            id: 'hand-in-quest',
            label: 'Hand In Quest',
            loading_label: 'Handing In...',
            variant: ButtonVariant.PRIMARY,
            disabled: !readiness.can_hand_in,
            loading: handingIn,
            on_click: () => void handleHandIn(),
          },
        ]
      : [];

  return (
    <StackedCard
      on_close={onClose}
      aria_label="Quest Details"
      content_mode={StackedCardContentMode.FULL_BLEED}
    >
      <div className="relative flex h-full min-h-0 flex-col overflow-hidden">
        <div className="min-h-0 flex-1 overflow-y-auto px-4 py-4 sm:px-5">
          {renderContent()}
        </div>

        <SidePeekOptionsFooter options={footerOptions} />

        {renderNestedQuest()}
        {renderNestedNpc()}
        {renderNestedGameMap()}
        {renderNestedItem()}
        {renderNestedQuestItem()}
      </div>
    </StackedCard>
  );
};

export default CharacterQuestDetailStack;
