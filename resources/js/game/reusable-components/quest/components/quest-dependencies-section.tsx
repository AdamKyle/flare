import React, { ReactNode, useMemo } from 'react';

import QuestCard from './quest-card';
import QuestDetailProps from '../types/quest-detail-props';
import { resolveQuestTreeState } from '../utils/resolve-quest-tree-state';

import InfiniteScroll from 'ui/infinite-scroll/infinite-scroll';

const fieldLabelClassName =
  'text-glacier-600 dark:text-glacier-400 text-xs font-semibold tracking-wide uppercase';

/**
 * No-op scroll handler for the Required Quest Chain's `InfiniteScroll`: the chain is already
 * fully loaded factual detail data, so there is no pagination/loading behavior to trigger.
 */
const handleRequiredQuestChainScroll = (): void => {};

const QuestDependenciesSection = ({
  quest,
  navigation,
  completed_quest_ids: completedQuestIdsList,
}: QuestDetailProps): ReactNode => {
  const { structure } = quest;

  const completedQuestIds = useMemo(
    () => new Set(completedQuestIdsList ?? []),
    [completedQuestIdsList]
  );

  const hasRows =
    Boolean(structure.parent_quest) ||
    structure.child_quests.length > 0 ||
    Boolean(structure.required_quest) ||
    structure.required_quest_chain.length > 0;

  if (!hasRows) {
    return null;
  }

  const parentQuestState = structure.parent_quest
    ? resolveQuestTreeState(structure.parent_quest, completedQuestIds)
    : undefined;

  const childQuestCards = structure.child_quests.map((child) => ({
    id: child.id,
    name: child.name,
    state: resolveQuestTreeState(child, completedQuestIds),
  }));

  const requiredQuestState = structure.required_quest
    ? resolveQuestTreeState(structure.required_quest, completedQuestIds)
    : undefined;

  const requiredQuestChainCards = structure.required_quest_chain.map(
    (required) => ({
      id: required.id,
      name: required.name,
      state: resolveQuestTreeState(required, completedQuestIds),
    })
  );

  const renderParentQuest = (): ReactNode => {
    if (!structure.parent_quest) {
      return null;
    }

    return (
      <div>
        <p className={fieldLabelClassName}>Parent Quest</p>
        <div className="mt-1">
          <QuestCard
            quest_id={structure.parent_quest.id}
            name={structure.parent_quest.name}
            state={parentQuestState}
            on_open_quest={navigation?.on_open_quest}
          />
        </div>
      </div>
    );
  };

  const renderChildQuests = (): ReactNode => {
    if (childQuestCards.length === 0) {
      return null;
    }

    return (
      <div>
        <p className={fieldLabelClassName}>Child Quests</p>
        <div className="mt-1 flex flex-col gap-2">
          {childQuestCards.map((card) => (
            <QuestCard
              key={card.id}
              quest_id={card.id}
              name={card.name}
              state={card.state}
              on_open_quest={navigation?.on_open_quest}
            />
          ))}
        </div>
      </div>
    );
  };

  const renderRequiredQuest = (): ReactNode => {
    if (!structure.required_quest) {
      return null;
    }

    return (
      <div>
        <p className={fieldLabelClassName}>Required Quest</p>
        <div className="mt-1">
          <QuestCard
            quest_id={structure.required_quest.id}
            name={structure.required_quest.name}
            state={requiredQuestState}
            on_open_quest={navigation?.on_open_quest}
          />
        </div>
      </div>
    );
  };

  const renderRequiredQuestChain = (): ReactNode => {
    if (requiredQuestChainCards.length === 0) {
      return null;
    }

    return (
      <div>
        <p className={fieldLabelClassName}>Required Quest Chain</p>
        <div className="mt-1">
          <InfiniteScroll
            height_class="h-auto max-h-[500px]"
            handle_scroll={handleRequiredQuestChainScroll}
          >
            <div className="flex flex-col gap-2">
              {requiredQuestChainCards.map((card) => (
                <QuestCard
                  key={card.id}
                  quest_id={card.id}
                  name={card.name}
                  state={card.state}
                  on_open_quest={navigation?.on_open_quest}
                />
              ))}
            </div>
          </InfiniteScroll>
        </div>
      </div>
    );
  };

  return (
    <div className="flex flex-col gap-4">
      {renderParentQuest()}
      {renderChildQuests()}
      {renderRequiredQuest()}
      {renderRequiredQuestChain()}
    </div>
  );
};

export default QuestDependenciesSection;
