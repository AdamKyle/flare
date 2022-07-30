import CharacterCurrenciesDetails from "../character-currencies-details";

export default interface MapProps {

    user_id: number,

    character_id: number,

    view_port: number,

    currencies: CharacterCurrenciesDetails;

    is_dead: boolean;

    is_automaton_running: boolean;

    automation_completed_at: number;

    show_celestial_fight_button: (id: number | null) => void;

    set_character_position: (position: {x: number, y: number, game_map_id?: number}) => void;

    update_character_quests_plane: (plane: string) => void;
}
