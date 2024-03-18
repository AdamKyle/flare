import InventoryComparisonAdjustment from "../../definitions/inventory-comparison-adjustment";
import ItemToEquip from "../../../../../../components/item-comparison/deffinitions/item-to-equip";

export default interface EquipModalProps {

    is_open: boolean;

    manage_modal: () => void;

    item_to_equip: ItemToEquip;

    equip_item: (type: string, position?: string) => void;

    is_hammer_equipped: boolean;

    is_bow_equipped: boolean;

    is_stave_equipped: boolean;
}
