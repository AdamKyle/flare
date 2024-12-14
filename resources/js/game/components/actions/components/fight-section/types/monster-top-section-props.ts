export default interface MonsterTopSectionProps {
    img_src: string;
    monster_name: string;
    next_action: (currentIndex: number) => void;
    prev_action: (currentIndex: number) => void;
    view_stats_action: (monsterId: number) => void;
}
