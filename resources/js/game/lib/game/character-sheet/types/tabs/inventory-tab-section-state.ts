import InventoryDetails from "../inventory/inventory-details";
import UsableItemsDetails from "../inventory/usable-items-details";
import { InventoryActionConfirmationType } from "../../../../../components/character-sheet/inventory-action-confirmation-modal/helpers/enums/inventory-action-confirmation-type";

export default interface InventoryTabSectionState {
    table: string;

    data: InventoryDetails[] | [];

    usable_items: UsableItemsDetails[] | [];

    show_destroy_all: boolean;

    show_destroy_all_alchemy: boolean;

    show_disenchant_all: boolean;

    show_sell_all: boolean;

    show_use_many: boolean;

    show_equip_best: boolean;

    success_message: string | null;

    search_string: string;

    selected_items: SelectItems[] | [];

    show_action_confirmation_modal: boolean;

    action_confirmation_type: InventoryActionConfirmationType | null;
}

export interface SelectItems {
    slot_id: number;
    item_name: string;
}
