import MonsterNameListDefinition from '../deffinitions/monster-name-list-definition';

export default interface MonsterTopSectionProps {
  img_src: string;
  monster_name: string | null;
  total_monsters: number;
  current_index: number;
  next_action: (currentIndex: number) => void;
  prev_action: (currentIndex: number) => void;
  select_action: (index: number) => void;
  view_monster_stats: () => void;
  monsters: MonsterNameListDefinition[];
}
