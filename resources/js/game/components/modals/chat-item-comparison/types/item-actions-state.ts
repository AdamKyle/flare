
import ItemToEquip from "../../../item-comparison/deffinitions/item-to-equip";

export default interface ItemActionsState {
    show_equip_modal: boolean;

    show_move_modal: boolean;

    show_sell_modal: boolean;

    show_list_item_modal: boolean;

    show_item_details: boolean;

    item_to_sell: ItemToEquip|null;

    item_to_show: ItemToEquip|null;

    show_loading_label: boolean,

    loading_label: string|null,

    error_message: string|null,
}
