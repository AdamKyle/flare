export default interface GameMapRelatedMonsterDefinition {
  id: number;
  name: string;
  is_celestial_entity: boolean;
  is_raid_monster: boolean;
  is_raid_boss: boolean;
  only_for_location_type: number | null;
}
