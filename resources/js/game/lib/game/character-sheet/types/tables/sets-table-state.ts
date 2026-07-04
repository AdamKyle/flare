import InventoryDetails from "../inventory/inventory-details";
import { InventoryActionConfirmationType } from "../../../../../components/character-sheet/inventory-action-confirmation-modal/helpers/enums/inventory-action-confirmation-type";

export default interface SetsTableState {
    data: InventoryDetails[] | [];

    drop_down_labels: string[];

    selected_set: string | null;

    selected_set_index: number | null;

    success_message: string | null;

    show_rename_set: boolean;

    loading: boolean;

    search_string: string;

    item_id: number | null;

    view_item: boolean;

    loading_label: string | null;

    show_loading_label: boolean;

    error_message: string | null;

    selected_slots: number[];

    show_action_confirmation_modal: boolean;

    action_confirmation_type: InventoryActionConfirmationType | null;
}
