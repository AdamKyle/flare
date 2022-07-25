import {CharacterType} from "../character/character-type";
import CharacterCurrenciesType from "../character/character-currencies-type";
import CharacterStatusType from "../character/character-status-type";
import QuestType from "./quests/quest-type";
import KingdomDetails from "../kingdoms/kingdom-details";

export default interface GameState {

    view_port: number;

    character_status: CharacterStatusType | null;

    character_currencies?: CharacterCurrenciesType;

    loading: boolean;

    finished_loading: boolean;

    secondary_loading_title: string;

    percentage_loaded: number;

    character: CharacterType | null;

    kingdoms: KingdomDetails[] | [];

    quests: QuestType | null;

    celestial_id: number;

    position: {x: number, y: number, game_map_id?: number} | null,

    disable_tabs: boolean;
}
