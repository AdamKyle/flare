export default interface Raid {
    id: number;
    corrupted_location_ids: string[]|[],
    name: string;
    raid_boss_id: number;
    raid_monster_ids: string[]|[];
    story: string;
}
