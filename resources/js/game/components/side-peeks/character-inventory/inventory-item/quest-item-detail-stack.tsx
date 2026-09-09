import React, { ReactNode, useState } from 'react';

import QuestItem from './quest-item';
import QuestItemRelationshipIdentityStack from './quest-item-relationship-identity-stack';
import QuestItemDetailStackProps from './types/quest-item-detail-stack-props';
import { QuestItemRelationshipIdentityKind } from './types/quest-item-relationship-identity-stack-props';
import CharacterQuestDetailStack from '../../../character-sheet/quests/components/character-quest-detail-stack';

import { useGameData } from 'game-data/hooks/use-game-data';

import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';

interface RelationshipIdentity {
  kind: QuestItemRelationshipIdentityKind;
  id: number;
}

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
          <CharacterQuestDetailStack
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
