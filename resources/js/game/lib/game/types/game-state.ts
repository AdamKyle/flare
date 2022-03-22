import {CharacterType} from "../actions/types/character/character-type";
import KingdomDetails from "../map/types/kingdom-details";
import {QuestDetails} from "../map/types/quest-details";

export default interface GameState {

    view_port: number;

    show_size_message: boolean,

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

    loading: boolean;

    secondary_loading_title: string;

    percentage_loaded: number;

    character: CharacterType | null;

    kingdoms: KingdomDetails[] | [];

    quests: {
        quests: QuestDetails[]|[],
        completed_quests: number[],
        player_plane: string,
    } | null
}
