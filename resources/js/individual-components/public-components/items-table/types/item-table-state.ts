import ItemDefinition from "../../../../game/components/items/deffinitions/item-definition";

export default interface ItemTableState {
    loading: boolean;
    items: ItemDefinition[] | [];
    item_to_view: ItemDefinition | null;
    error_message: string | null;
}
