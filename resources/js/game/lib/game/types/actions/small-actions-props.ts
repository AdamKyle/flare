import CharacterCurrenciesDetails from "../character-currencies-details";
import ActionsProps from "./actions-props";
import PositionType from "../map/position-type";

export default interface SmallActionsProps extends ActionsProps {
    character_currencies: CharacterCurrenciesDetails;

    update_plane_quests: (plane: string) => void;

    update_character_position: (position: PositionType) => void;

    view_port: number;
}
