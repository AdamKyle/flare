import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import CharacterQuestHandInActions from './character-quest-hand-in-actions';
import CharacterQuestDetailStackProps from './types/character-quest-detail-stack-props';
import QuestDetail from '../../../../reusable-components/quest/components/quest-detail';
import { SidePeekComponentRegistrationEnum } from '../../../side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../../side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../side-peeks/base/hooks/use-side-peek-emitter';
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
  const sidePeekEmitter = useSidePeekEmitter();

  const [nestedQuestId, setNestedQuestId] = useState<number | null>(null);

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

  const handleOpenNpc = (npcId: number): void => {
    sidePeekEmitter.emit(
      SidePeek.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.PLAYER_NPC_DETAIL,
      {
        is_open: true,
        title: 'NPC Details',
        allow_clicking_outside: true,
        npc_id: npcId,
      }
    );
  };

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

  const handleOpenItem = (itemId: number): void => {
    sidePeekEmitter.emit(
      SidePeek.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ITEM_DETAILS,
      {
        is_open: true,
        title: 'Item Details',
        allow_clicking_outside: true,
        item_id: itemId,
      }
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
            on_open_npc: handleOpenNpc,
            on_open_map: handleOpenMap,
            on_open_item: handleOpenItem,
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

        {nestedQuestId !== null && (
          <CharacterQuestDetailStack
            character_id={characterId}
            quest_id={nestedQuestId}
            completed_quest_ids={detailCompletedQuestIds}
            on_close={() => setNestedQuestId(null)}
            on_completed_quests_change={onCompletedQuestsChange}
          />
        )}
      </div>
    </StackedCard>
  );
};

export default CharacterQuestDetailStack;
