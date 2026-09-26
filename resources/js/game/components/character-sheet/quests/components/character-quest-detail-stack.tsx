import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import CharacterQuestHandInActions from './character-quest-hand-in-actions';
import CharacterQuestDetailStackProps from './types/character-quest-detail-stack-props';
import QuestDetail from '../../../../reusable-components/quest/components/quest-detail';
import PlayerGameMapDetailStack from '../../../side-peeks/game-data/components/player-game-map-detail-stack';
import PlayerNpcDetailStack from '../../../side-peeks/game-data/components/player-npc-detail-stack';
import ItemDetailsStack from '../../../side-peeks/item-details/components/item-details-stack';
import { useCharacterQuestDetail } from '../api/hooks/use-character-quest-detail';
import { useHandInCharacterQuest } from '../api/hooks/use-hand-in-character-quest';

import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

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
        />
        <QuestDetail
          quest={quest}
          completed_quest_ids={detailCompletedQuestIds}
          presentation="side-peek"
          navigation={{
            on_open_quest: setNestedQuestId,
            on_open_npc: setNestedNpcId,
            on_open_map: setNestedGameMapId,
            on_open_item: setNestedItemId,
          }}
          quest_item_ownership={questItemOwnership}
        />
      </div>
    );
  };

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

        {renderNestedQuest()}
        {renderNestedNpc()}
        {renderNestedGameMap()}
        {renderNestedItem()}
      </div>
    </StackedCard>
  );
};

export default CharacterQuestDetailStack;
