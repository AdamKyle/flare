export default interface ItemColorFieldsDefinition {
  is_cosmic: boolean;
  is_mythic: boolean;
  is_unique: boolean;
  holy_stacks_applied: number;
  affix_count: number;
  type: string;
  usable: boolean;
  holy_level: number | null;
  damages_kingdoms: boolean;
}
