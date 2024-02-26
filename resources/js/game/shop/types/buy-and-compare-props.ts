import ItemDefinition from "../../sections/items/deffinitions/item-definition";

export default interface BuyAndCompareProps {

    character_id: number;

    item: ItemDefinition;

    close_view_buy_and_compare: () => void;
}
