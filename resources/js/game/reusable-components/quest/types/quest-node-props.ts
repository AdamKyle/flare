import { MutableRefObject } from 'react';

import QuestTreeNodeDefinition from '../api/definitions/quest-tree-node-definition';

/**
 * Optional read-only navigation callbacks for the shared Quest tree/detail
 * presentation. Never checks Admin permission, imports Admin APIs, or
 * mutates data; the caller decides navigation behavior entirely.
 */
export interface QuestTreeNavigationDefinition {
  on_open_quest?: (id: number) => void;
  on_open_npc?: (id: number) => void;
  on_open_map?: (id: number) => void;
  on_open_item?: (id: number) => void;
  on_open_monster?: (id: number) => void;
  on_open_raid?: (id: number) => void;
  on_open_skill?: (id: number) => void;
  on_open_passive?: (id: number) => void;
}

export default interface QuestNodeProps {
  quest: QuestTreeNodeDefinition;
  depth: number;
  completed_quest_ids: ReadonlySet<number>;
  expanded_ids: ReadonlySet<number>;
  focused_id: number | null;
  on_toggle_expand: (id: number) => void;
  on_select: (id: number) => void;
  on_focus_node: (id: number) => void;
  node_refs: MutableRefObject<Map<number, HTMLDivElement>>;
  collapsible: boolean;
}
