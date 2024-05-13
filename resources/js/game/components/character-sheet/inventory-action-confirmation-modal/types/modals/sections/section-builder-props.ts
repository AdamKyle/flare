import { InventoryActionConfirmationType } from "../../../helpers/enums/inventory-action-confirmation-type";

export default interface SectionBuilderProps {
    type: InventoryActionConfirmationType;

    item_names?: string[] | [];
}
