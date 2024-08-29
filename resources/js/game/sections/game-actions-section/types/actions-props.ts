import { CharacterType } from "../../../lib/game/character/character-type";
import CharacterStatusType from "../../../lib/game/character/character-status-type";
import PositionType from "../../map/types/map/position-type";
import { GameActionState } from "../../../lib/game/types/game-state";
import { FameTasks } from "../../../components/faction-loyalty/deffinitions/faction-loaylaty";

export default interface ActionsProps {
    character: CharacterType;

    character_status: CharacterStatusType;

    character_position: PositionType | null;

    celestial_id: number;

    update_celestial: (celestialId: number | null) => void;

    can_engage_celestial: boolean;

    action_data: GameActionState | null;

    update_parent_state: (stateData: GameActionState) => void;

    fame_tasks: FameTasks[] | null;

    update_show_map_mobile: (showMap: boolean) => void;

    manage_show_new_ui: () => void;
}
