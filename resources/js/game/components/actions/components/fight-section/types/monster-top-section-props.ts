export default interface MonsterTopSectionProps {
  img_src: string;
  monster_name: string | null;
  total_monsters: number;
  current_index: number;
  next_action: (currentIndex: number) => void;
  prev_action: (currentIndex: number) => void;
  view_monster_stats: () => void;
}
