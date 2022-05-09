import {CharacterType} from "../character/character-type";
import KingdomDetails from "../map/types/kingdom-details";
import {QuestDetails} from "../map/types/quest-details";
import CharacterCurrenciesType from "../character/character-currencies-type";
import CharacterStatusType from "../character/character-status-type";
import QuestType from "./quests/quest-type";

export default interface GameState {

    view_port: number;

    show_size_message: boolean,

    character_status: CharacterStatusType | null;

    character_currencies?: CharacterCurrenciesType;

    loading: boolean;

    secondary_loading_title: string;

    percentage_loaded: number;

    character: CharacterType | null;

    kingdoms: KingdomDetails[] | [];

    quests: QuestType | null;
}
