import { KeyboardEvent, MutableRefObject } from 'react';

import QuestTreeNodeDefinition from '../api/definitions/quest-tree-node-definition';

export default interface QuestTreeLayoutProps {
  quests: QuestTreeNodeDefinition[];
  completed_quest_ids: ReadonlySet<number>;
  focused_id: number | null;
  on_select: (id: number) => void;
  on_focus_node: (id: number) => void;
  node_refs: MutableRefObject<Map<number, HTMLDivElement>>;
  on_key_down: (event: KeyboardEvent<HTMLUListElement>) => void;
}
