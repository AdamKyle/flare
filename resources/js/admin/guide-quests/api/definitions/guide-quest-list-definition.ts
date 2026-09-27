export interface GuideQuestListRelationshipDefinition {
  id: number;
  name: string;
}

export interface GuideQuestListEventDefinition {
  value: number;
  label: string;
}

export default interface GuideQuestListDefinition {
  id: number;
  name: string;
  required_level: number | null;
  unlock_at_level: number | null;
  parent: GuideQuestListRelationshipDefinition | null;
  event: GuideQuestListEventDefinition | null;
}
