import ItemDefinition from "../../../../game/components/items/deffinitions/item-definition";

export default interface BuyMultipleProps {
    character_id: number;
    close_view_buy_many: (item: ItemDefinition) => void;
    inventory_count: number;
    inventory_max: number;
    character_gold: number;
    is_merchant: boolean;
    item: ItemDefinition;
}
