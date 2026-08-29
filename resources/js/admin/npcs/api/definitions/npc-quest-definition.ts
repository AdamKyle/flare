export interface NpcQuestRelatedItemDefinition {
  id: number;
  name: string;
}

export default interface NpcQuestDefinition {
  id: number;
  name: string;
  required_item: NpcQuestRelatedItemDefinition | null;
  secondary_required_item: NpcQuestRelatedItemDefinition | null;
  reward_item: NpcQuestRelatedItemDefinition | null;
}
