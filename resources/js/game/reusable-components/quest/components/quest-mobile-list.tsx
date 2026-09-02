import React, { ReactNode, useMemo } from 'react';

import QuestCard from './quest-card';
import QuestMobileListProps from '../types/quest-mobile-list-props';
import { flattenQuestTreeForList } from '../utils/flatten-quest-tree-for-list';
import { resolveQuestTreeState } from '../utils/resolve-quest-tree-state';

/**
 * Mobile Quest presentation: a flat, vertically scrolling, factual
 * Quest-card list, suitable for Admin, public Information, and a future
 * Character/player adapter alike. It intentionally does not render
 * connector lines, indentation, or disclosure chevrons; hierarchy is
 * conveyed as short readable metadata on each card instead. The adapter
 * controls selected plane, completed Quest ids, and navigation only — this
 * component never imports Admin/Information code or inspects permissions.
 */
const QuestMobileList = ({
  quests,
  completed_quest_ids: completedQuestIdsList,
  navigation,
}: QuestMobileListProps): ReactNode => {
  const completedQuestIds = useMemo(
    () => new Set(completedQuestIdsList),
    [completedQuestIdsList]
  );

  const entries = useMemo(() => flattenQuestTreeForList(quests), [quests]);

  const resolveContextLabel = (
    parentName: string | null,
    rootName: string | null
  ): string | undefined => {
    if (!parentName) {
      return undefined;
    }

    if (!rootName || rootName === parentName) {
      return `Part of ${parentName}`;
    }

    return `Part of ${rootName} — Parent: ${parentName}`;
  };

  return (
    <ul aria-label="Quest list" className="space-y-3">
      {entries.map(
        ({ quest, parent_name: parentName, root_name: rootName }) => (
          <li key={quest.id}>
            <QuestCard
              quest_id={quest.id}
              name={quest.name}
              state={resolveQuestTreeState(quest, completedQuestIds)}
              npc_name={quest.npc?.name ?? null}
              child_count={quest.children.length}
              context_label={resolveContextLabel(parentName, rootName)}
              on_open_quest={(id) => navigation?.on_open_quest?.(id)}
            />
          </li>
        )
      )}
    </ul>
  );
};

export default QuestMobileList;
