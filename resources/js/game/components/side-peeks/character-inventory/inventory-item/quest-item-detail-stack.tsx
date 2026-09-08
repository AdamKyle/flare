import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import QuestItem from './quest-item';
import QuestItemRelationshipIdentityStack from './quest-item-relationship-identity-stack';
import QuestItemDetailStackProps from './types/quest-item-detail-stack-props';
import { QuestItemRelationshipIdentityKind } from './types/quest-item-relationship-identity-stack-props';
import QuestDetail from '../../../../reusable-components/quest/components/quest-detail';
import { useCharacterQuestDetail } from '../../../character-sheet/quests/api/hooks/use-character-quest-detail';
import { useHandInCharacterQuest } from '../../../character-sheet/quests/api/hooks/use-hand-in-character-quest';
import CharacterQuestHandInActions from '../../../character-sheet/quests/components/character-quest-hand-in-actions';

import { useGameData } from 'game-data/hooks/use-game-data';

import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

interface RelationshipIdentity {
  kind: QuestItemRelationshipIdentityKind;
  id: number;
}

interface NestedQuestDetailStackProps {
  character_id: number;
  quest_id: number;
  completed_quest_ids: number[];
  on_close: () => void;
  on_completed_quests_change: (ids: number[]) => void;
}

/**
 * Self-contained nested Quest detail overlay for the Quest Item relationship
 * drill-down. Kept local (rather than reusing the Character Quest screen's
 * normal-flow detail view) because this stack recurses inside its own
 * StackedCard layer, matching the sibling relationship-identity overlay here.
 */
const NestedQuestDetailStack = ({
  character_id: characterId,
  quest_id: questId,
  completed_quest_ids: completedQuestIds,
  on_close: onClose,
  on_completed_quests_change: onCompletedQuestsChange,
}: NestedQuestDetailStackProps): ReactNode => {
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
          navigation={{ on_open_quest: setNestedQuestId }}
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
          <NestedQuestDetailStack
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

const QuestItemDetailStack = ({
  quest_item: questItem,
  on_close: onClose,
}: QuestItemDetailStackProps): ReactNode => {
  const { gameData } = useGameData();
  const characterId = gameData?.character?.id ?? 0;

  const [nestedQuestId, setNestedQuestId] = useState<number | null>(null);
  const [nestedIdentity, setNestedIdentity] =
    useState<RelationshipIdentity | null>(null);
  const [completedQuestIds, setCompletedQuestIds] = useState<number[]>([]);

  const handleCloseNestedQuest = (): void => setNestedQuestId(null);
  const handleCloseNestedIdentity = (): void => setNestedIdentity(null);

  return (
    <StackedCard
      on_close={onClose}
      aria_label="Quest Item Details"
      content_mode={StackedCardContentMode.FULL_BLEED}
    >
      <div className="relative flex h-full min-h-0 flex-col overflow-hidden">
        <div className="min-h-0 flex-1 overflow-y-auto py-4">
          <QuestItem
            quest_item={questItem}
            navigation={{
              on_open_quest: setNestedQuestId,
              on_open_location: (id: number) =>
                setNestedIdentity({ kind: 'location', id }),
              on_open_map: (id: number) =>
                setNestedIdentity({ kind: 'map', id }),
              on_open_monster: (id: number) =>
                setNestedIdentity({ kind: 'monster', id }),
            }}
          />
        </div>

        {nestedQuestId !== null && (
          <NestedQuestDetailStack
            character_id={characterId}
            quest_id={nestedQuestId}
            completed_quest_ids={completedQuestIds}
            on_close={handleCloseNestedQuest}
            on_completed_quests_change={setCompletedQuestIds}
          />
        )}

        {nestedIdentity !== null && (
          <QuestItemRelationshipIdentityStack
            quest_item={questItem}
            kind={nestedIdentity.kind}
            id={nestedIdentity.id}
            on_close={handleCloseNestedIdentity}
            on_open_map={(id) => setNestedIdentity({ kind: 'map', id })}
          />
        )}
      </div>
    </StackedCard>
  );
};

export default QuestItemDetailStack;
