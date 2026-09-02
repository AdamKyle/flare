export default interface MonsterCardProps {
  monster_id: number;
  name: string;
  is_celestial_entity: boolean;
  is_raid_boss: boolean;
  is_raid_monster: boolean;
  only_for_location_type: number | null;
  on_open_monster: (id: number) => void;
}
