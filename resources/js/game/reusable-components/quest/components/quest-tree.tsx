import React, { ReactNode, useMemo, useRef, useState } from 'react';

import QuestTreeDesktop from './quest-tree-desktop';
import QuestTreeMobile from './quest-tree-mobile';
import QuestTreeNodeDefinition from '../api/definitions/quest-tree-node-definition';
import QuestTreeProps from '../types/quest-tree-props';

interface FlatEntry {
  id: number;
  parent_id: number | null;
}

const flattenVisible = (
  quests: QuestTreeNodeDefinition[],
  expandedIds: ReadonlySet<number>,
  collapsible: boolean,
  parentId: number | null = null
): FlatEntry[] => {
  const entries: FlatEntry[] = [];

  quests.forEach((quest) => {
    entries.push({ id: quest.id, parent_id: parentId });

    const isExpanded = !collapsible || expandedIds.has(quest.id);

    if (quest.children.length > 0 && isExpanded) {
      entries.push(
        ...flattenVisible(quest.children, expandedIds, collapsible, quest.id)
      );
    }
  });

  return entries;
};

const findNode = (
  quests: QuestTreeNodeDefinition[],
  id: number
): QuestTreeNodeDefinition | null => {
  for (const quest of quests) {
    if (quest.id === id) {
      return quest;
    }

    const found = findNode(quest.children, id);

    if (found) {
      return found;
    }
  }

  return null;
};

/**
 * Shared, permission-neutral, accessible Quest tree. Renders a desktop
 * branching layout and a mobile vertical layout of the same underlying
 * data; both consume the same node building blocks and the same keyboard
 * navigation managed here.
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

  const [expandedIds, setExpandedIds] = useState<Set<number>>(new Set());
  const [focusedId, setFocusedId] = useState<number | null>(
    quests[0]?.id ?? null
  );

  const desktopNodeRefs = useRef<Map<number, HTMLDivElement>>(new Map());
  const mobileNodeRefs = useRef<Map<number, HTMLDivElement>>(new Map());

  const handleToggleExpand = (id: number): void => {
    setExpandedIds((previous) => {
      const next = new Set(previous);

      if (next.has(id)) {
        next.delete(id);
      } else {
        next.add(id);
      }

      return next;
    });
  };

  const handleSelect = (id: number): void => {
    navigation?.on_open_quest?.(id);
  };

  const focusNode = (
    id: number,
    nodeRefs: React.MutableRefObject<Map<number, HTMLDivElement>>
  ): void => {
    setFocusedId(id);
    nodeRefs.current.get(id)?.focus();
  };

  const buildKeyboardHandler =
    (
      collapsible: boolean,
      nodeRefs: React.MutableRefObject<Map<number, HTMLDivElement>>
    ) =>
    (event: React.KeyboardEvent<HTMLUListElement>): void => {
      if (focusedId === null) {
        return;
      }

      const flat = flattenVisible(quests, expandedIds, collapsible);
      const currentIndex = flat.findIndex((entry) => entry.id === focusedId);

      if (currentIndex === -1) {
        return;
      }

      const currentEntry = flat[currentIndex];
      const currentNode = findNode(quests, focusedId);

      switch (event.key) {
        case 'ArrowDown': {
          event.preventDefault();
          const next = flat[currentIndex + 1];
          if (next) {
            focusNode(next.id, nodeRefs);
          }
          break;
        }
        case 'ArrowUp': {
          event.preventDefault();
          const previous = flat[currentIndex - 1];
          if (previous) {
            focusNode(previous.id, nodeRefs);
          }
          break;
        }
        case 'ArrowRight': {
          event.preventDefault();
          if (!currentNode) {
            break;
          }
          if (currentNode.children.length === 0) {
            break;
          }
          const isExpanded = !collapsible || expandedIds.has(currentNode.id);
          if (collapsible && !isExpanded) {
            handleToggleExpand(currentNode.id);
          } else {
            const next = flat[currentIndex + 1];
            if (next) {
              focusNode(next.id, nodeRefs);
            }
          }
          break;
        }
        case 'ArrowLeft': {
          event.preventDefault();
          const isExpanded =
            collapsible &&
            currentNode &&
            currentNode.children.length > 0 &&
            expandedIds.has(currentNode.id);
          if (isExpanded && currentNode) {
            handleToggleExpand(currentNode.id);
          } else if (currentEntry.parent_id !== null) {
            focusNode(currentEntry.parent_id, nodeRefs);
          }
          break;
        }
        case 'Home': {
          event.preventDefault();
          const first = flat[0];
          if (first) {
            focusNode(first.id, nodeRefs);
          }
          break;
        }
        case 'End': {
          event.preventDefault();
          const last = flat[flat.length - 1];
          if (last) {
            focusNode(last.id, nodeRefs);
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
        expanded_ids={expandedIds}
        focused_id={focusedId}
        on_toggle_expand={handleToggleExpand}
        on_select={handleSelect}
        on_focus_node={setFocusedId}
        node_refs={desktopNodeRefs}
        on_key_down={buildKeyboardHandler(false, desktopNodeRefs)}
      />
      <QuestTreeMobile
        quests={quests}
        completed_quest_ids={completedQuestIds}
        expanded_ids={expandedIds}
        focused_id={focusedId}
        on_toggle_expand={handleToggleExpand}
        on_select={handleSelect}
        on_focus_node={setFocusedId}
        node_refs={mobileNodeRefs}
        on_key_down={buildKeyboardHandler(true, mobileNodeRefs)}
      />
    </div>
  );
};

export default QuestTree;
