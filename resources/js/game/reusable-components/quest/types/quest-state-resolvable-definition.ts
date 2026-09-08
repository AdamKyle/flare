export default interface QuestStateResolvableDefinition {
  id: number;
  parent_quest_id: number | null;
  required_quest_id: number | null;
  required_quest_chain_ids: number[];
}
