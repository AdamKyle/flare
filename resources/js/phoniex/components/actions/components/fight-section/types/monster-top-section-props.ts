export default interface MonsterTopSectionProps {
    img_src: string;
    monster_name: string;
    next_action: (currentIndex: number) => {};
    prev_action: (currentIndex: number) => {};
    view_stats_action: (monsterId: number) => {};
}
