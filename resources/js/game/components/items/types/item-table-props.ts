import ItemDefinition from "../deffinitions/item-definition";
import { ItemType } from "../enums/item-type";

export default interface ItemTableProps {
    items: ItemDefinition[] | [];
    item_to_view: ItemDefinition | null;
    close_view_item_label: string;
    table_columns: any[];
    close_view_item_action: () => void;
    custom_filter?: { label: string; value: ItemType }[];
    set_item_filter: (shopFilter: {
        filter: string | null;
        search_text: string | null;
    }) => void;
}
