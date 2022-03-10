export default interface GameState {

    view_port: number;

    show_size_message: boolean,

    hide_map: boolean,

    character_status: {
        is_dead: boolean,
        can_adventure: boolean,
    } | null;

    character_currencies?: {
        gold: number,
        shards: number,
        gold_dust: number,
        copper_coins: number,
    };
}
