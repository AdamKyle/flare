export interface ItemUsageRelatedEntityDefinition {
  id: number;
  name: string;
  resource: string;
}

export interface ItemUsageBlockerDefinition {
  key: string;
  label: string;
  count: number;
  related_entities?: ItemUsageRelatedEntityDefinition[];
}

export default interface ItemUsageDefinition {
  deletable: boolean;
  total_blocker_categories: number;
  blockers: ItemUsageBlockerDefinition[];
}
