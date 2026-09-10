import ApiErrorAlert from 'api-handler/components/api-error-alert';
import { Screens } from 'configuration/screen-manager/screen-manager-constants';
import { useScreenNavigation } from 'configuration/screen-manager/screen-manager-kit';
import React, { ReactNode } from 'react';

import QuestDetailScreenProps from './types/quest-detail-screen-props';
import QuestDetail from '../../reusable-components/quest/components/quest-detail';
import { useCharacterQuestDetail } from '../character-sheet/quests/api/hooks/use-character-quest-detail';
import { useHandInCharacterQuest } from '../character-sheet/quests/api/hooks/use-hand-in-character-quest';
import CharacterQuestHandInActions from '../character-sheet/quests/components/character-quest-hand-in-actions';
import { SidePeekComponentRegistrationEnum } from '../side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../side-peeks/base/hooks/use-side-peek-emitter';

import Card from 'ui/cards/card';
import ContainerWithTitle from 'ui/container/container-with-title';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const QuestDetailScreen = ({
  character_id: characterId,
  quest_id: questId,
  completed_quest_ids: completedQuestIds,
  on_completed_quests_change: onCompletedQuestsChange,
}: QuestDetailScreenProps): ReactNode => {
  const { navigateTo, pop } = useScreenNavigation();
  const sidePeekEmitter = useSidePeekEmitter();

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

  const handleOpenRelatedQuest = (relatedQuestId: number): void => {
    navigateTo(Screens.QUEST_DETAIL, {
      character_id: characterId,
      quest_id: relatedQuestId,
      completed_quest_ids: detailCompletedQuestIds,
      on_completed_quests_change: onCompletedQuestsChange,
    });
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
          navigation={{
            on_open_quest: handleOpenRelatedQuest,
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
    <ContainerWithTitle
      title="Quest Details"
      manageSectionVisibility={() => pop()}
    >
      <Card>{renderContent()}</Card>
    </ContainerWithTitle>
  );
};

export default QuestDetailScreen;
