export default interface QuestDependencyDefinition {
  id: number;
  name: string;
  parent_quest_id: number | null;
  required_quest_id: number | null;
  required_quest_chain_ids: number[];
}
