/**
 * The exact structural state facts `resolveQuestTreeState` requires. Both
 * `QuestTreeNodeDefinition` (Quest tree nodes) and `QuestDependencyDefinition`
 * (Required Quest / Required Quest Chain entries) structurally satisfy this
 * contract, so the same resolver powers Tree nodes and dependency cards
 * without a second state algorithm.
 */
export default interface QuestStateResolvableDefinition {
  id: number;
  parent_quest_id: number | null;
  required_quest_id: number | null;
  required_quest_chain_ids: number[];
}
