export default interface DelveQuestItemDefinition {
  id: number;
  name: string;
  type: string;
  drop_chance: number | null;
  monster_name: string | null;
  slot_id: number | null;
  have: boolean;
  had: boolean;
}
