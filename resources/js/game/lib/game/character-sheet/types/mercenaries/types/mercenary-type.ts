export default interface MercenaryType {

    at_max_level: boolean;

    bonus: number;

    can_reincarnate: boolean;

    current_xp: number;

    id: number;

    level: number;

    max_level: number;

    name: string;

    times_reincarnated: number;

    xp_increase: number;

    xp_buff: number | null;

    xp_required: number;
}
