import ItemToEquip from "../../../../../../components/item-comparison/deffinitions/item-to-equip";

export default interface SellModalProps {

    is_open: boolean;

    manage_modal: () => void;

    sell_item: () => void;

    item: ItemToEquip;
}
