import MapState from "../../../sections/map/types/map-state";
import PositionType from "../../../sections/map/types/map/position-type";
import CharacterCurrenciesType from "../character/character-currencies-type";
import CharacterStatusType from "../character/character-status-type";
import { CharacterType } from "../character/character-type";
import MonsterType from "./actions/monster/monster-type";
import RaidMonsterType from "./actions/monster/raid-monster-type";
import QuestType from "./quests/quest-type";
import KingdomDetails from "../../../components/kingdoms/deffinitions/kingdom-details";
import KingdomLogDetails from "../../../components/kingdoms/deffinitions/kingdom-log-details";
import { FameTasks } from "../../../components/faction-loyalty/deffinitions/faction-loaylaty";

export type GameActionState = {
    monsters: MonsterType[];
    raid_monsters: RaidMonsterType[] | [];
};

export type MapTimerData = {
    time_left: number;
    time_left_started: number;
    automation_time_out: number;
    automation_time_out_started: number;
    celestial_time_out: number;
    celestial_time_out_started: number;
};

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

    position: PositionType | null;

    disable_tabs: boolean;

    show_global_timeout: boolean;

    tabs: { name: string; key: string; has_logs?: boolean }[] | [];

    action_data: GameActionState | null;

    map_data: MapState | null;

    fame_action_tasks: FameTasks[] | null;

    show_guide_quest_completed: boolean;

    hide_donation_alert: boolean;
}
