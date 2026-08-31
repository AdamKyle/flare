import { MonsterListCategory } from '../../enums/monster-list-category';

export default interface MonsterListFiltersDefinition {
  game_map_id: number | null;
  category: MonsterListCategory;
  location_type: number | null;
  [key: string]: number | MonsterListCategory | null;
}
