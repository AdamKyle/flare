export default interface CraftableItemDefinition {
  id: number;
  name: string;
  cost: number;
  type: string;
  crafting_type: string;
  default_position: string | null;
  skill_level_required: number;
  skill_level_trivial: number;
}
