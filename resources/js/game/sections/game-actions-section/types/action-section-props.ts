import SmallActionsProps from "./small-actions-props";
import PositionType from "../../map/types/map/position-type";

export default interface ActionSectionProps extends SmallActionsProps {
    update_show_map_mobile: (showMap: boolean) => void;
}
