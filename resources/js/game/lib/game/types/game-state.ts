import {CharacterType} from "../character/character-type";
import CharacterCurrenciesType from "../character/character-currencies-type";
import CharacterStatusType from "../character/character-status-type";
import QuestType from "./quests/quest-type";
import KingdomDetails from "../kingdoms/kingdom-details";
import PositionType from "./map/position-type";
import KingdomLogDetails from "../kingdoms/kingdom-log-details";

export default interface GameState {

    view_port: number;

    character_status: CharacterStatusType | null;

    character_currencies: CharacterCurrenciesType | null;

    loading: boolean;

    finished_loading: boolean;

    secondary_loading_title: string;

    percentage_loaded: number;

    character: CharacterType | null;

    kingdoms: KingdomDetails[] | [];

    kingdom_logs: KingdomLogDetails[] | [];

    quests: QuestType | null;

    celestial_id: number;

    position: PositionType | null,

    disable_tabs: boolean;

    tabs: {name: string, key: string, has_logs?: boolean}[]|[]
}
