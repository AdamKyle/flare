import React, { ReactNode, useMemo, useRef, useState } from 'react';

import QuestMobileList from './quest-mobile-list';
import QuestTreeDesktop from './quest-tree-desktop';
import QuestTreeNodeDefinition from '../api/definitions/quest-tree-node-definition';
import QuestTreeProps from '../types/quest-tree-props';

interface FlatEntry {
  id: number;
  parent_id: number | null;
}

const flattenVisible = (
  quests: QuestTreeNodeDefinition[],
  parentId: number | null = null
): FlatEntry[] => {
  const entries: FlatEntry[] = [];

  quests.forEach((quest) => {
    entries.push({ id: quest.id, parent_id: parentId });

    if (quest.children.length > 0) {
      entries.push(...flattenVisible(quest.children, quest.id));
    }
  });

  return entries;
};

/**
 * Shared, permission-neutral Quest tree. Desktop/tablet renders a real
 * branching hierarchy with keyboard tree navigation; mobile renders the
 * same underlying data as a flat, vertically scrolling Quest-card list
 * (see `QuestMobileList`) rather than a recursive indented tree, so the
 * presentation stays usable on narrow viewports for Admin, public
 * Information, and a future Character/player adapter alike.
 */
const QuestTree = ({
  quests,
  completed_quest_ids: completedQuestIdsList,
  navigation,
}: QuestTreeProps): ReactNode => {
  const completedQuestIds = useMemo(
    () => new Set(completedQuestIdsList),
    [completedQuestIdsList]
  );

  const [focusedId, setFocusedId] = useState<number | null>(
    quests[0]?.id ?? null
  );

  const desktopNodeRefs = useRef<Map<number, HTMLDivElement>>(new Map());

  const handleSelect = (id: number): void => {
    navigation?.on_open_quest?.(id);
  };

  const focusNode = (id: number): void => {
    setFocusedId(id);
    desktopNodeRefs.current.get(id)?.focus();
  };

  const handleDesktopKeyDown = (
    event: React.KeyboardEvent<HTMLUListElement>
  ): void => {
    if (focusedId === null) {
      return;
    }

    const flat = flattenVisible(quests);
    const currentIndex = flat.findIndex((entry) => entry.id === focusedId);

    if (currentIndex === -1) {
      return;
    }

    const currentEntry = flat[currentIndex];

    switch (event.key) {
      case 'ArrowDown': {
        event.preventDefault();
        const next = flat[currentIndex + 1];
        if (next) {
          focusNode(next.id);
        }
        break;
      }
      case 'ArrowUp': {
        event.preventDefault();
        const previous = flat[currentIndex - 1];
        if (previous) {
          focusNode(previous.id);
        }
        break;
      }
      case 'ArrowRight': {
        event.preventDefault();
        const next = flat[currentIndex + 1];
        if (next) {
          focusNode(next.id);
        }
        break;
      }
      case 'ArrowLeft': {
        event.preventDefault();
        if (currentEntry.parent_id !== null) {
          focusNode(currentEntry.parent_id);
        }
        break;
      }
      case 'Home': {
        event.preventDefault();
        const first = flat[0];
        if (first) {
          focusNode(first.id);
        }
        break;
      }
      case 'End': {
        event.preventDefault();
        const last = flat[flat.length - 1];
        if (last) {
          focusNode(last.id);
        }
        break;
      }
      default:
        break;
    }
  };

  return (
    <div>
      <QuestTreeDesktop
        quests={quests}
        completed_quest_ids={completedQuestIds}
        focused_id={focusedId}
        on_select={handleSelect}
        on_focus_node={setFocusedId}
        node_refs={desktopNodeRefs}
        on_key_down={handleDesktopKeyDown}
      />
      <div className="md:hidden">
        <QuestMobileList
          quests={quests}
          completed_quest_ids={completedQuestIdsList}
          navigation={navigation}
        />
      </div>
    </div>
  );
};

export default QuestTree;
