import React, { ReactNode, useEffect, useMemo, useState } from 'react';

import QuestCard from './quest-card';
import QuestDetailProps from '../types/quest-detail-props';
import { resolveQuestTreeState } from '../utils/resolve-quest-tree-state';

import InfiniteScroll from 'ui/infinite-scroll/infinite-scroll';

const INITIAL_VISIBLE_COUNT = 10;
const REVEAL_BATCH_SIZE = 10;

const QuestChildQuestsSection = ({
  quest,
  navigation,
  completed_quest_ids: completedQuestIdsList,
}: QuestDetailProps): ReactNode => {
  const { structure } = quest;

  const completedQuestIds = useMemo(
    () => new Set(completedQuestIdsList ?? []),
    [completedQuestIdsList]
  );

  const childQuestCards = useMemo(
    () =>
      structure.child_quests.map((child) => ({
        id: child.id,
        name: child.name,
        state: resolveQuestTreeState(child, completedQuestIds),
      })),
    [structure.child_quests, completedQuestIds]
  );

  const [visibleCount, setVisibleCount] = useState<number>(
    Math.min(INITIAL_VISIBLE_COUNT, childQuestCards.length)
  );

  useEffect(() => {
    setVisibleCount(Math.min(INITIAL_VISIBLE_COUNT, childQuestCards.length));
  }, [childQuestCards.length]);

  if (childQuestCards.length === 0) {
    return null;
  }

  const hasMore = visibleCount < childQuestCards.length;
  const visibleCards = childQuestCards.slice(0, visibleCount);

  const handleScroll = (event: React.UIEvent<HTMLDivElement>): void => {
    const { scrollTop, scrollHeight, clientHeight } = event.currentTarget;

    if (scrollTop + clientHeight < scrollHeight - 10 || !hasMore) {
      return;
    }

    setVisibleCount((currentCount) =>
      Math.min(currentCount + REVEAL_BATCH_SIZE, childQuestCards.length)
    );
  };

  const renderCards = (): ReactNode => (
    <div className="flex flex-col gap-2">
      {visibleCards.map((card) => (
        <QuestCard
          key={card.id}
          quest_id={card.id}
          name={card.name}
          state={card.state}
          on_open_quest={navigation?.on_open_quest}
        />
      ))}
    </div>
  );

  return (
    <div>
      <h3 className="text-marigold-700 dark:text-marigold-500 mb-2 text-base font-semibold">
        Child Quests
      </h3>
      <div>
        {childQuestCards.length > INITIAL_VISIBLE_COUNT ? (
          <InfiniteScroll
            height_class="h-auto max-h-[500px]"
            handle_scroll={handleScroll}
          >
            {renderCards()}
          </InfiniteScroll>
        ) : (
          renderCards()
        )}
      </div>
    </div>
  );
};

export default QuestChildQuestsSection;
