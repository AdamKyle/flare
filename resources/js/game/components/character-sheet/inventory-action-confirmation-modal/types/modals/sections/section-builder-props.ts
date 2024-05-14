import { InventoryActionConfirmationType } from "../../../helpers/enums/inventory-action-confirmation-type";
import SetDetails from "../../../../../../lib/game/character-sheet/types/inventory/set-details";

export default interface SectionBuilderProps {
    type: InventoryActionConfirmationType;

    item_names?: string[] | [];

    usable_sets: SetDetails[] | [];

    update_api_params: (params: any) => void;
}
