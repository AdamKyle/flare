import ItemDefinition from "../../../../game/components/items/deffinitions/item-definition";
import { ItemType } from "../../../../game/components/items/enums/item-type";

export default interface ShopState {
    loading: boolean;
    success_message: string | null;
    error_message: string | null;
    items: ItemDefinition[] | [];
    item_to_view: ItemDefinition | null;
    item_to_buy_many: ItemDefinition | null;
    item_to_compare: ItemDefinition | null;
    gold: number;
    inventory_count: number;
    inventory_max: number;
    is_merchant: boolean;
}
