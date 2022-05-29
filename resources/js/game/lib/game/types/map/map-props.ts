export default interface MapProps {

    user_id: number,

    character_id: number,

    view_port: number,

    currencies?: {
        gold: number,
        shards: number,
        gold_dust: number,
        copper_coins: number,
    };

    is_dead: boolean;

    is_automaton_running: boolean;

    automation_completed_at: number;

    show_celestial_fight_button: (id: number | null) => void;

    set_character_position: (position: {x: number, y: number, game_map_id?: number}) => void;
}
