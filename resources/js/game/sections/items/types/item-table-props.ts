import ItemDefinition from "../deffinitions/item-definition";

export default interface ItemTableProps {
    items: ItemDefinition[]|[]
    item_to_view: ItemDefinition | null;
    close_view_item_label: string;
    table_columns: any[];
    close_view_item_action: () => void;
}
